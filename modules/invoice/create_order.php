<?php
$pageTitle = 'Create Order - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

// Initialize cart and customer in session
if (!isset($_SESSION['invoice_cart'])) {
    $_SESSION['invoice_cart'] = [];
}

if (!isset($_SESSION['invoice_customer'])) {
    $_SESSION['invoice_customer'] = null;
}

// Handle customer lock
if (isset($_POST['lock_customer'])) {
    $mobile = sanitize($_POST['mobile']);
    $name = sanitize($_POST['party_name']);
    $notes = sanitize($_POST['party_notes'] ?? '');
    
    $_SESSION['invoice_customer'] = [
        'mobile' => $mobile,
        'name' => $name,
        'notes' => $notes
    ];
    
    setFlashMessage('success', 'Customer locked! Now add products.');
    header('Location: create_order.php');
    exit();
}

// Handle change customer
if (isset($_GET['change_customer'])) {
    $_SESSION['invoice_customer'] = null;
    setFlashMessage('info', 'Customer unlocked. Search new customer.');
    header('Location: create_order.php');
    exit();
}

// Handle clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['invoice_cart'] = [];
    setFlashMessage('success', 'Cart cleared');
    header('Location: create_order.php');
    exit();
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $cartId = $_GET['remove'];
    unset($_SESSION['invoice_cart'][$cartId]);
    setFlashMessage('success', 'Item removed from cart');
    header('Location: create_order.php');
    exit();
}

// Handle add to cart with barcode
if (isset($_POST['add_to_cart'])) {
    $barcode = sanitize($_POST['barcode'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if (!empty($barcode) && $quantity > 0) {
        // Parse barcode: 5001A000001
        // Extract size_code (position 5, index 4)
        // Extract product_number (remaining digits)
        
        if (strlen($barcode) >= 6) {
            $sizeCode = $barcode[4]; // Position 5 (0-indexed)
            $productNumberStr = substr($barcode, 5); // Remaining digits
            $productNumber = intval($productNumberStr);
            
            // Get size by size_code
            $sizeSql = "SELECT id, size_name FROM sizes WHERE size_code = ?";
            $sizeStmt = $conn->prepare($sizeSql);
            $sizeStmt->bind_param('s', $sizeCode);
            $sizeStmt->execute();
            $sizeResult = $sizeStmt->get_result();
            $size = $sizeResult->fetch_assoc();
            
            if (!$size) {
                setFlashMessage('error', 'Invalid size code in barcode!');
            } else {
                // Get product by product_number
                $prodSql = "SELECT p.*, c.category_name FROM products p 
                           LEFT JOIN categories c ON p.category_id = c.id 
                           WHERE p.product_number = ?";
                $prodStmt = $conn->prepare($prodSql);
                $prodStmt->bind_param('i', $productNumber);
                $prodStmt->execute();
                $prodResult = $prodStmt->get_result();
                $product = $prodResult->fetch_assoc();
                
                if (!$product) {
                    setFlashMessage('error', 'Product not found with code: ' . $barcode);
                } else {
                    // Check stock
                    $stockSql = "SELECT quantity FROM product_stock WHERE product_id = ? AND size_id = ?";
                    $stockStmt = $conn->prepare($stockSql);
                    $stockStmt->bind_param('ii', $product['id'], $size['id']);
                    $stockStmt->execute();
                    $stockResult = $stockStmt->get_result();
                    $stock = $stockResult->fetch_assoc();
                    
                    if (!$stock || $stock['quantity'] < $quantity) {
                        setFlashMessage('error', 'Insufficient stock! Available: ' . ($stock['quantity'] ?? 0));
                    } else {
                        // Add to cart
                        $cartId = $product['id'] . '_' . $size['id'];
                        $_SESSION['invoice_cart'][$cartId] = [
                            'product_id' => $product['id'],
                            'size_id' => $size['id'],
                            'product_code' => $product['product_code'],
                            'product_name' => $product['product_name'],
                            'category_name' => $product['category_name'],
                            'size_name' => $size['size_name'],
                            'quantity' => $quantity,
                            'mrp' => $product['mrp'],
                            'gst_rate' => $product['gst_rate'],
                            'discount' => 0
                        ];
                        setFlashMessage('success', '✓ ' . $product['product_code'] . ' (' . $size['size_name'] . ') added!');
                    }
                }
            }
        } else {
            setFlashMessage('error', 'Invalid barcode format!');
        }
    } else {
        setFlashMessage('error', 'Please enter barcode and quantity');
    }
    
    header('Location: create_order.php');
    exit();
}

// Calculate cart summary
$cartSummary = ['subtotal' => 0, 'discount' => 0];
foreach ($_SESSION['invoice_cart'] as $item) {
    $itemTotal = ($item['mrp'] * $item['quantity']) - $item['discount'];
    $cartSummary['subtotal'] += $itemTotal;
    $cartSummary['discount'] += $item['discount'];
}

// Handle invoice generation
if (isset($_POST['generate_invoice'])) {
    $paymentMode = sanitize($_POST['payment_mode']);
    $billDiscount = floatval($_POST['bill_discount'] ?? 0);
    
    // Get customer from session
    $customer = $_SESSION['invoice_customer'];
    
    if (empty($customer) || empty($_SESSION['invoice_cart'])) {
        setFlashMessage('error', 'Please lock customer and add items to cart');
    } else {
        $mobile = $customer['mobile'];
        $partyName = $customer['name'];
        $notes = $customer['notes'];
        
        $conn->begin_transaction();
        
        try {
            // Get or create party
            $partySql = "SELECT id FROM parties WHERE mobile = ?";
            $partyStmt = $conn->prepare($partySql);
            $partyStmt->bind_param('s', $mobile);
            $partyStmt->execute();
            $partyResult = $partyStmt->get_result();
            $partyRow = $partyResult->fetch_assoc();
            
            if ($partyRow) {
                $partyId = $partyRow['id'];
                // Update party name and notes
                $updatePartySql = "UPDATE parties SET party_name = ?, notes = ? WHERE id = ?";
                $updatePartyStmt = $conn->prepare($updatePartySql);
                $updatePartyStmt->bind_param('ssi', $partyName, $notes, $partyId);
                $updatePartyStmt->execute();
            } else {
                // Create new party
                $insertPartySql = "INSERT INTO parties (party_name, mobile, notes) VALUES (?, ?, ?)";
                $insertPartyStmt = $conn->prepare($insertPartySql);
                $insertPartyStmt->bind_param('sss', $partyName, $mobile, $notes);
                $insertPartyStmt->execute();
                $partyId = $conn->insert_id;
            }
            
            // Calculate totals
            $subtotal = 0;
            $totalDiscount = $billDiscount;
            $totalCGST = 0;
            $totalSGST = 0;
            $grandTotal = 0;
            
            foreach ($_SESSION['invoice_cart'] as $item) {
                $itemTotal = ($item['mrp'] * $item['quantity']) - $item['discount'];
                $subtotal += $itemTotal;
                $totalDiscount += $item['discount'];
                
                $gstBreakdown = calculateGSTBreakdown($item['mrp'], $item['gst_rate']);
                $basePerUnit = $gstBreakdown['base_amount'];
                
                $itemBase = $basePerUnit * $item['quantity'];
                $itemDiscounted = $itemTotal;
                $discountedBase = $itemDiscounted / (1 + ($item['gst_rate'] / 100));
                
                $cgst = ($discountedBase * ($item['gst_rate'] / 2)) / 100;
                $sgst = ($discountedBase * ($item['gst_rate'] / 2)) / 100;
                
                $totalCGST += $cgst;
                $totalSGST += $sgst;
                $grandTotal += $itemDiscounted;
            }
            
            // Generate invoice number
            $invoiceNo = generateInvoiceNumber($conn);
            $invoiceDate = date('Y-m-d');
            $userId = getUserId();
            
            // Insert invoice
            $invoiceSql = "INSERT INTO invoices (invoice_no, invoice_date, party_id, subtotal, discount_amount, cgst_amount, sgst_amount, total_amount, payment_mode, notes, created_by) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $invoiceStmt = $conn->prepare($invoiceSql);
            $invoiceStmt->bind_param('ssidddddssi', $invoiceNo, $invoiceDate, $partyId, $subtotal, $totalDiscount, $totalCGST, $totalSGST, $grandTotal, $paymentMode, $notes, $userId);
            $invoiceStmt->execute();
            $invoiceId = $conn->insert_id;
            
            // Insert invoice items and update stock
            foreach ($_SESSION['invoice_cart'] as $cartItem) {
                $itemSql = "INSERT INTO invoice_items (invoice_id, product_id, size_id, quantity, mrp, gst_rate, discount_amount, base_amount, cgst_amount, sgst_amount, total_amount) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $itemStmt = $conn->prepare($itemSql);
                $itemStmt->bind_param('iiiiddddddd', $invoiceId, $cartItem['product_id'], $cartItem['size_id'], $cartItem['quantity'], $cartItem['mrp'], $cartItem['gst_rate'], $cartItem['discount'], $cartItem['base_amount'], $cartItem['cgst'], $cartItem['sgst'], $cartItem['total']);
                $itemStmt->execute();
                
                // Update stock
                updateStock($conn, $cartItem['product_id'], $cartItem['size_id'], $cartItem['quantity'], 'subtract');
                
                // Record transaction
                recordStockTransaction($conn, $cartItem['product_id'], $cartItem['size_id'], $cartItem['quantity'], 'OUT', 'SALE', $invoiceId, 'Invoice: ' . $invoiceNo);
            }
            
            $conn->commit();
            
            // Clear cart and customer session
            $_SESSION['invoice_cart'] = [];
            $_SESSION['invoice_customer'] = null;
            
            setFlashMessage('success', 'Invoice generated successfully!');
            header('Location: view.php?invoice=' . $invoiceNo);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            setFlashMessage('error', 'Error creating invoice: ' . $e->getMessage());
        }
    }
}

$customerLocked = !empty($_SESSION['invoice_customer']);
$customer = $_SESSION['invoice_customer'];
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-receipt"></i> Create Order (Barcode System)</h2>
    </div>
</div>

<div class="row">
    <!-- Left Side - Customer & Product -->
    <div class="col-md-7">
        
        <!-- Customer Section -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person"></i> Customer Details</span>
                <?php if ($customerLocked): ?>
                <a href="?change_customer=1" class="btn btn-sm btn-warning">
                    <i class="bi bi-pencil"></i> Change Customer
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                
                <?php if (!$customerLocked): ?>
                <!-- Customer Search Form -->
                <form method="POST" action="" id="customerSearchForm">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                <input type="text" class="form-control mobile-input number-only" id="mobile" 
                                       name="mobile" placeholder="10-digit mobile" maxlength="10" required autofocus>
                                <button type="button" class="btn btn-primary" id="searchCustomerBtn">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="customerDetailsForm" style="display:none;">
                        <input type="hidden" name="lock_customer" value="1">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="party_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="party_name" name="party_name" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="party_notes" class="form-label">Notes (Optional)</label>
                            <textarea class="form-control" id="party_notes" name="party_notes" rows="2"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-lock"></i> Lock Customer & Start Order
                        </button>
                    </div>
                </form>
                
                <?php else: ?>
                <!-- Customer Locked - Show Details -->
                <div class="alert alert-success mb-0">
                    <h5 class="alert-heading"><i class="bi bi-check-circle"></i> Customer Locked</h5>
                    <hr>
                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($customer['name']); ?></p>
                    <p class="mb-1"><strong>Mobile:</strong> <?php echo $customer['mobile']; ?></p>
                    <?php if (!empty($customer['notes'])): ?>
                    <p class="mb-0"><strong>Notes:</strong> <?php echo htmlspecialchars($customer['notes']); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
        
        <!-- Product Section - Only show if customer locked -->
        <?php if ($customerLocked): ?>
        <div class="card">
            <div class="card-header">
                <i class="bi bi-barcode"></i> Add Products by Barcode
            </div>
            <div class="card-body">
                <form method="POST" action="" id="addProductForm">
                    <input type="hidden" name="add_to_cart" value="1">
                    
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label for="barcode" class="form-label">Product Barcode <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-barcode"></i></span>
                                <input type="text" class="form-control text-uppercase" id="barcode" name="barcode" 
                                       placeholder="A00001 (or 5001A00001)" required autofocus>
                                <button type="button" class="btn btn-primary" id="searchProductBtn">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                            <small class="text-muted">
                                Format: Size Code (A-G) + Product Number (5 digits)<br>
                                Example: A00001, E00001, C00099999
                            </small>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label for="quantity" class="form-label">Qty <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   value="1" min="1" required>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-cart-plus"></i> Add to Order
                            </button>
                        </div>
                    </div>
                    
                    <!-- Product Info Display -->
                    <div id="productInfo" style="display:none;">
                        <div class="alert alert-info">
                            <p class="mb-1"><strong>Product:</strong> <span id="productInfoName"></span></p>
                            <p class="mb-1"><strong>Size:</strong> <span id="productInfoSize"></span> (Auto-selected)</p>
                            <p class="mb-1"><strong>Price:</strong> ₹<span id="productInfoPrice"></span></p>
                            <p class="mb-0"><strong>Stock Available:</strong> <span id="productInfoStock"></span></p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Right Side - Cart -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cart"></i> Order (<?php echo count($_SESSION['invoice_cart']); ?> items)</span>
                <?php if (!empty($_SESSION['invoice_cart'])): ?>
                <a href="?clear_cart=1" class="btn btn-sm btn-danger" onclick="return confirm('Clear all items?')">
                    <i class="bi bi-trash"></i> Clear
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($_SESSION['invoice_cart'])): ?>
                <div class="text-center p-4 text-muted">
                    <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                    <p class="mt-2">Order is empty</p>
                    <?php if (!$customerLocked): ?>
                    <small>Lock customer first, then add products</small>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Amount</th>
                            <th width="40"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['invoice_cart'] as $cartId => $item): 
                            $itemTotal = ($item['mrp'] * $item['quantity']) - $item['discount'];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $item['product_code']; ?></strong><br>
                                <small><?php echo $item['product_name']; ?></small><br>
                                <span class="badge bg-secondary"><?php echo $item['size_name']; ?></span>
                                x <?php echo $item['quantity']; ?>
                            </td>
                            <td class="text-end">
                                <strong><?php echo formatCurrency($itemTotal); ?></strong><br>
                                <small class="text-muted"><?php echo $item['gst_rate']; ?>% GST</small>
                            </td>
                            <td>
                                <a href="?remove=<?php echo $cartId; ?>" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($_SESSION['invoice_cart']) && $customerLocked): ?>
        <!-- Generate Invoice -->
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-calculator"></i> Order Summary
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="generate_invoice" value="1">
                    
                    <table class="table table-sm mb-3">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-end"><strong><?php echo formatCurrency($cartSummary['subtotal']); ?></strong></td>
                        </tr>
                        <?php if ($cartSummary['discount'] > 0): ?>
                        <tr>
                            <td>Discount:</td>
                            <td class="text-end text-danger">-<?php echo formatCurrency($cartSummary['discount']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-success">
                            <td><strong>Total:</strong></td>
                            <td class="text-end"><strong><?php echo formatCurrency($cartSummary['subtotal'] - $cartSummary['discount']); ?></strong></td>
                        </tr>
                    </table>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="payment_mode" id="cash" value="Cash" checked>
                            <label class="btn btn-outline-success" for="cash"><i class="bi bi-cash"></i> Cash</label>
                            
                            <input type="radio" class="btn-check" name="payment_mode" id="card" value="Card">
                            <label class="btn btn-outline-primary" for="card"><i class="bi bi-credit-card"></i> Card</label>
                            
                            <input type="radio" class="btn-check" name="payment_mode" id="upi" value="UPI">
                            <label class="btn btn-outline-info" for="upi"><i class="bi bi-phone"></i> UPI</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-lg w-100">
                        <i class="bi bi-receipt-cutoff"></i> Generate Invoice
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Get base URL
    let currentUrl = window.location.href;
    let baseUrl = currentUrl.split('/modules/')[0];
    
    // Search customer
    $('#searchCustomerBtn').on('click', searchCustomer);
    $('#mobile').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            searchCustomer();
        }
    });
    
    function searchCustomer() {
        let mobile = $('#mobile').val().trim();
        
        if (mobile.length !== 10) {
            alert('Please enter 10-digit mobile number');
            $('#mobile').focus();
            return;
        }
        
        $.ajax({
            url: baseUrl + '/modules/party/ajax_search.php',
            method: 'POST',
            data: { mobile: mobile },
            dataType: 'json',
            success: function(data) {
                $('#customerDetailsForm').show();
                $('#party_name').focus();
                
                if (data.found) {
                    $('#party_name').val(data.name);
                    $('#party_notes').val(data.notes || '');
                    alert('✓ Customer found: ' + data.name + '\n\nClick "Lock Customer" to continue.');
                } else {
                    $('#party_name').val('');
                    $('#party_notes').val('');
                    alert('New customer! Enter name and click "Lock Customer".');
                }
            },
            error: function() {
                alert('Error searching customer');
            }
        });
    }
    
    // Search product by barcode
    $('#searchProductBtn').on('click', searchProduct);
    $('#barcode').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            searchProduct();
        }
    });
    
    function searchProduct() {
        let barcode = $('#barcode').val().toUpperCase().trim();
        
        if (barcode.length < 6) {
            alert('Enter barcode (minimum 6 characters)');
            return;
        }
        
        // Parse barcode - handle both formats:
        // A00001 or 5001A00001
        let sizeCode, productNumber;
        
        if (barcode.startsWith('5001')) {
            // Format: 5001A00001
            sizeCode = barcode[4];
            productNumber = barcode.substring(5);
        } else {
            // Format: A00001
            sizeCode = barcode[0];
            productNumber = barcode.substring(1);
        }
        
        // Validate
        if (!/^[A-G]$/.test(sizeCode)) {
            alert('Invalid size code! Use A-G');
            return;
        }
        
        if (!/^\d{5,6}$/.test(productNumber)) {
            alert('Invalid product number! Use 5 or 6 digits');
            return;
        }
        
        // Send to server
        $.ajax({
            url: baseUrl + '/api/search_barcode.php',
            method: 'POST',
            data: { 
                size_code: sizeCode,
                product_number: productNumber
            },
            dataType: 'json',
            success: function(data) {
                if (data.found) {
                    $('#productInfoName').text(data.product_name + ' (' + data.category_name + ')');
                    $('#productInfoSize').text(data.size_name);
                    $('#productInfoPrice').text(data.mrp);
                    $('#productInfoStock').text(data.stock);
                    $('#productInfo').show();
                    
                    if (data.stock < 1) {
                        alert('⚠️ Out of stock!');
                        return;
                    }
                    
                    $('#quantity').focus();
                } else {
                    $('#productInfo').hide();
                    alert('❌ Product not found: ' + sizeCode + productNumber);
                }
            },
            error: function() {
                alert('Error searching product!');
            }
        });
    }
});
</script>
