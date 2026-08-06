<?php
ob_start();
// Check if customer is locked in session
$customerLocked = !empty($_SESSION['invoice_customer']);
$customer = $_SESSION['invoice_customer'];
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-receipt"></i> Create Invoice</h2>
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
                            <i class="bi bi-lock"></i> Lock Customer & Start Billing
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
                <i class="bi bi-box-seam"></i> Add Products
            </div>
            <div class="card-body">
                <form method="POST" action="" id="addProductForm">
                    <input type="hidden" name="add_to_cart" value="1">
                    <input type="hidden" name="product_id" id="product_id">
                    <input type="hidden" name="size_id" id="size_id">
                    
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="product_code" class="form-label">Product Code <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control text-uppercase" id="product_code" 
                                       placeholder="E000001" required>
                                <button type="button" class="btn btn-primary" id="searchProductBtn">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-7 mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="product_name" readonly>
                        </div>
                    </div>
                    
                    <div id="productDetailsSection" style="display:none;">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="size_select" class="form-label">Size <span class="text-danger">*</span></label>
                                <select class="form-select" id="size_select" required>
                                    <option value="">Select</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2 mb-3">
                                <label for="quantity" class="form-label">Qty <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="quantity" name="quantity" 
                                       value="1" min="1" required>
                            </div>
                            
                            <div class="col-md-3 mb-3">
                                <label class="form-label">MRP</label>
                                <input type="text" class="form-control" id="mrp_display" readonly>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="discount" class="form-label">Discount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="number" class="form-control" id="discount" name="discount" 
                                           value="0" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-cart-plus"></i> Add to Cart
                        </button>
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
                <span><i class="bi bi-cart"></i> Cart (<?php echo count($_SESSION['invoice_cart']); ?> items)</span>
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
                    <p class="mt-2">Cart is empty</p>
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
                            $itemTotal = ($item['mrp'] * $item['quantity']) - floatval($item['discount']);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $item['product_code']; ?></strong><br>
                                <small><?php echo $item['size_code'].$item['product_name']; ?></small><br>
                                <span class="badge bg-secondary"><?php echo $item['size_name']; ?></span>
                                x <?php echo $item['quantity']; ?>
                                <?php if ($item['discount'] > 0): ?>
                                <br><small class="text-danger">-<?php echo formatCurrency($item['discount']); ?></small>
                                <?php endif; ?>
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
                <i class="bi bi-calculator"></i> Invoice Summary
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="generate_invoice" value="1">
                    
                    <table class="table table-sm mb-3">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-end">
                                <strong
                                    id="subtotal_amount"
                                    data-subtotal="<?php echo $cartSummary['subtotal']; ?>">
                                    <?php echo number_format($cartSummary['subtotal'],2,'.',''); ?>
                                </strong>
                            </td>
                        </tr>
                        <?php if ($cartSummary['discount'] > 0): ?>
                        <tr>
                            <td>Discount:</td>
                            <td class="text-end text-danger">-<?php echo formatCurrency($cartSummary['discount']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td><strong>Bill Discount</strong></td>
                            <td><input type="number" class="form-control" id="bill_discount" name="bill_discount" value="<?php echo $_SESSION['bill_discount'] ?? 0; ?>" min="0" step="0.01"></td>
                        </tr>
                        <tr class="table-success">
                            <td><strong>Total:</strong></td>
                            <td class="text-end"><strong id="grand_total"><?php echo number_format($cartSummary['subtotal'] - $cartSummary['discount'],2); ?></strong></td>
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
    
    // Search product
    $('#searchProductBtn').on('click', searchProduct);
    $('#product_code').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            searchProduct();
        }
    });
    
    function searchProduct() {
        let code = $('#product_code').val().toUpperCase().trim();
        $('#product_code').val(code);
        var bill_discount = $('#bill_discount').val();
        
        if (code.length < 6) {
            alert('Enter complete product code (e.g., PY000001)');
            return;
        }
        
        $.ajax({
            url: baseUrl + '/api/get_product.php',
            method: 'POST',
            data: { code: code },
            dataType: 'json',
            success: function(data) {
                if (data.found) {
                    // $('#product_id').val(data.id);
                    // $('#product_name').val(data.name);
                    // $('#mrp_display').val('₹' + parseFloat(data.mrp).toFixed(2));
                    
                    // // Populate sizes
                    // let options = '<option value="">Select Size</option>';
                    // if (data.sizes && data.sizes.length > 0) {
                    //     data.sizes.forEach(function(size) {
                    //         if (size.quantity > 0) {
                    //             options += '<option value="' + size.id + '">' + size.name + ' (' + size.quantity + ' stock)</option>';
                    //         }
                    //     });
                    //     $('#size_select').html(options);
                    //     $('#productDetailsSection').show();
                    //     $('#size_select').focus();
                    // } else {
                    //     alert('No stock available for this product!');
                    //     $('#productDetailsSection').hide();
                    // }
                    
                       $('#product_code').val('').focus();

        // Reload page so cart refreshes
        location.reload();
                } else {
                    alert('Product Stock "' + code + '" not available!');
                    $('#product_name').val('');
                    $('#productDetailsSection').hide();
                }
            },
            error: function() {
                alert('Error searching product!');
            }
        });
    }
    
    // Update hidden size_id
    $('#size_select').on('change', function() {
        $('#size_id').val($(this).val());
    });
    
    // Reset product form after adding (if coming from redirect)
    <?php if (isset($_SESSION['flash_message']['type']) && $_SESSION['flash_message']['type'] === 'success' && strpos($_SESSION['flash_message']['message'], 'added') !== false): ?>
    $('#product_code').val('');
    $('#product_name').val('');
    $('#product_id').val('');
    $('#size_id').val('');
    $('#size_select').val('');
    $('#quantity').val('1');
    $('#discount').val('0');
    $('#bill_discount').val(bill_discount);
    $('#productDetailsSection').hide();
    $('#product_code').focus();
    <?php endif; ?>
});

function updateBillDiscount() {

    // let subtotal = parseFloat($("#subtotal_amount").data("subtotal"));

    // let itemDiscount = <?php echo (float)$cartSummary['discount']; ?>;

    // let billDiscount = parseFloat($("#bill_discount").val()) || 0;

    // let total = subtotal - itemDiscount - billDiscount;

    // if(total < 0){
    //     total = 0;
    // }

    // $("#grand_total").html("₹" + total.toFixed(2));
    let subtotal = parseFloat($("#subtotal_amount").data("subtotal"));
    let billDiscount = parseFloat($("#bill_discount").val()) || 0;
    
    let total = subtotal - billDiscount;
    
    if (total < 0)
        total = 0;
    
    $("#grand_total").html("₹" + total.toFixed(2));
}

$("#bill_discount").on("input", updateBillDiscount);

updateBillDiscount();
</script>
