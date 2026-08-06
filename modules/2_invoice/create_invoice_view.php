<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-receipt"></i> Create Invoice</h2>
    </div>
</div>

<div class="row">
    <!-- Left Side - Party & Product Selection -->
    <div class="col-md-7">
        <!-- Party Details - Search Once -->
        <div class="card mb-3" id="customerCard">
            <div class="card-header">
                <i class="bi bi-person"></i> Customer Details
                <button type="button" class="btn btn-sm btn-warning float-end" id="changeCustomer" style="display:none;">
                    <i class="bi bi-pencil"></i> Change Customer
                </button>
            </div>
            <div class="card-body">
                <div id="customerSearch">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-phone"></i></span>
                                <input type="text" class="form-control mobile-input number-only" id="mobile" 
                                       placeholder="10-digit mobile" maxlength="10" required>
                                <button type="button" class="btn btn-primary" id="searchParty">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="partyDetails" style="display:none;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="party_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="party_name" required readonly>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="party_mobile_display" class="form-label">Mobile</label>
                            <input type="text" class="form-control" id="party_mobile_display" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="party_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="party_notes" rows="2" readonly></textarea>
                    </div>
                    
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Customer locked! Now add products below.
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Product Selection - Can Add Multiple -->
        <div class="card" id="productCard" style="display:none;">
            <div class="card-header">
                <i class="bi bi-box-seam"></i> Add Products
            </div>
            <div class="card-body">
                <form method="POST" action="" id="addProductForm">
                    <input type="hidden" name="add_to_cart" value="1">
                    <input type="hidden" name="product_id" id="product_id">
                    <input type="hidden" name="size_id" id="size_id_hidden">
                    
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="product_code" class="form-label">Product Code</label>
                            <div class="input-group">
                                <input type="text" class="form-control text-uppercase" id="product_code" 
                                       placeholder="PY000001" autocomplete="off">
                                <button type="button" class="btn btn-primary" id="searchProduct">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-7 mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="product_name" readonly placeholder="Search by code">
                        </div>
                    </div>
                    
                    <div class="row" id="productDetails" style="display:none;">
                        <div class="col-md-3 mb-3">
                            <label for="size_select" class="form-label">Size <span class="text-danger">*</span></label>
                            <select class="form-select" id="size_select" required>
                                <option value="">Select Size</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2 mb-3">
                            <label for="quantity" class="form-label">Qty <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   min="1" value="1" required>
                        </div>
                        
                        <div class="col-md-3 mb-3">
                            <label for="mrp_display" class="form-label">MRP</label>
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
                    
                    <button type="submit" class="btn btn-success" id="addToCartBtn" style="display:none;">
                        <i class="bi bi-cart-plus"></i> Add to Cart
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Right Side - Cart -->
    <div class="col-md-5">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cart"></i> Cart (<?php echo count($_SESSION['invoice_cart']); ?> items)</span>
                <?php if (!empty($_SESSION['invoice_cart'])): ?>
                <a href="?clear_cart=1" class="btn btn-sm btn-danger">Clear</a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($_SESSION['invoice_cart'])): ?>
                <div class="text-center p-4 text-muted">
                    <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                    <p class="mt-2">Cart is empty</p>
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
                                <?php if ($item['discount'] > 0): ?>
                                <br><small class="text-danger">Disc: -<?php echo formatCurrency($item['discount']); ?></small>
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
        
        <?php if (!empty($_SESSION['invoice_cart'])): ?>
        <!-- Invoice Summary -->
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-calculator"></i> Summary
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="generate_invoice" value="1">
                    <input type="hidden" name="mobile" id="invoice_mobile">
                    <input type="hidden" name="party_name" id="invoice_party_name">
                    <input type="hidden" name="notes" id="invoice_notes">
                    
                    <table class="table table-sm mb-3">
                        <tr>
                            <td>Subtotal:</td>
                            <td class="text-end"><?php echo formatCurrency($cartSummary['subtotal']); ?></td>
                        </tr>
                        <tr>
                            <td>
                                Bill Discount:
                                <input type="number" class="form-control form-control-sm mt-1" 
                                       name="bill_discount" id="bill_discount" value="0" min="0" step="0.01">
                            </td>
                            <td class="text-end">-<?php echo formatCurrency($cartSummary['discount']); ?></td>
                        </tr>
                        <tr class="table-primary">
                            <td><strong>Grand Total:</strong></td>
                            <td class="text-end"><strong><?php echo formatCurrency($cartSummary['subtotal'] - $cartSummary['discount']); ?></strong></td>
                        </tr>
                    </table>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="payment_mode" id="cash" value="Cash" checked>
                            <label class="btn btn-outline-primary" for="cash">Cash</label>
                            
                            <input type="radio" class="btn-check" name="payment_mode" id="card" value="Card">
                            <label class="btn btn-outline-primary" for="card">Card</label>
                            
                            <input type="radio" class="btn-check" name="payment_mode" id="upi" value="UPI">
                            <label class="btn btn-outline-primary" for="upi">UPI</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-lg w-100" id="generateInvoiceBtn">
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
    let customerLocked = false;
    let selectedProduct = null;
    
    // Get base URL dynamically
    let currentUrl = window.location.href;
    let baseUrl = currentUrl.split('/modules/')[0];
    
    // Search party by mobile
    $('#searchParty').on('click', searchCustomer);
    $('#mobile').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            searchCustomer();
        }
    });
    
    function searchCustomer() {
        let mobile = $('#mobile').val().trim();
        
        if (mobile.length !== 10) {
            alert('Please enter a valid 10-digit mobile number');
            return;
        }
        
        
        $.ajax({
            url: baseUrl + '/modules/party/ajax_search.php',
            method: 'POST',
            data: { mobile: mobile },
            dataType: 'json',
            success: function(data) {
                if (data.found) {
                    // Customer found - fill and lock
                    $('#party_name').val(data.name);
                    $('#party_mobile_display').val(mobile);
                    $('#party_notes').val(data.notes || '');
                    lockCustomer();
                    alert('✓ Customer found: ' + data.name + '\n\nNow you can add multiple products!');
                } else {
                    // New customer - ask for name
                    let name = prompt('New Customer!\n\nEnter customer name:', '');
                    if (name && name.trim() !== '') {
                        $('#party_name').val(name.trim());
                        $('#party_mobile_display').val(mobile);
                        $('#party_notes').val('');
                        lockCustomer();
                        alert('✓ New customer saved!\n\nNow you can add multiple products!');
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('Error:', error);
                alert('Error searching customer. Please check:\n1. Database connection\n2. ajax_search.php file exists');
            }
        });
    }
    
    function lockCustomer() {
        customerLocked = true;
        $('#customerSearch').hide();
        $('#partyDetails').show();
        $('#changeCustomer').show();
        $('#productCard').show();
        $('#product_code').focus();
    }
    
    // Change customer button
    $('#changeCustomer').on('click', function() {
        if (confirm('Change customer?\n\nNote: Cart will NOT be cleared.')) {
            customerLocked = false;
            $('#customerSearch').show();
            $('#partyDetails').hide();
            $('#changeCustomer').hide();
            $('#mobile').val('').focus();
            $('#productDetails').hide();
            $('#addToCartBtn').hide();
        }
    });
    
    // Search product by code
    $('#searchProduct').on('click', searchProductByCode);
    $('#product_code').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            searchProductByCode();
        }
    });
    
    function searchProductByCode() {
        let code = $('#product_code').val().toUpperCase().trim();
        $('#product_code').val(code);
        
        if (code.length < 6) {
            alert('Please enter complete product code (e.g., PY000001)');
            return;
        }
        
        $.ajax({
            url: baseUrl + '/api/get_product.php',
            method: 'POST',
            data: { code: code },
            dataType: 'json',
            success: function(data) {
                if (data.found) {
                    selectedProduct = data;
                    $('#product_id').val(data.id);
                    $('#product_name').val(data.name);
                    $('#mrp_display').val('₹' + parseFloat(data.mrp).toFixed(2));
                    
                    // Populate sizes
                    let options = '<option value="">Select Size</option>';
                    if (data.sizes && data.sizes.length > 0) {
                        data.sizes.forEach(function(size) {
                            if (size.quantity > 0) {
                                options += '<option value="' + size.id + '">' + size.name + ' (' + size.quantity + ' available)</option>';
                            }
                        });
                        $('#size_select').html(options);
                        $('#productDetails').show();
                        $('#addToCartBtn').show();
                        $('#size_select').focus();
                    } else {
                        alert('Product found but no stock available!');
                        $('#productDetails').hide();
                        $('#addToCartBtn').hide();
                    }
                } else {
                    $('#product_name').val('');
                    $('#productDetails').hide();
                    $('#addToCartBtn').hide();
                    alert('Product "' + code + '" not found!\n\nCheck:\n• Code is correct\n• Product exists in master\n• Product is active');
                }
            },
            error: function(xhr, status, error) {
                console.log('Error:', error);
                $('#product_name').val('');
                $('#productDetails').hide();
                $('#addToCartBtn').hide();
                alert('Error searching product!\n\nURL: ' + baseUrl + '/api/get_product.php\n\nCheck:\n• File exists\n• Database connection');
            }
        });
    }
    
    // Update size_id when size selected
    $('#size_select').on('change', function() {
        $('#size_id_hidden').val($(this).val());
    });
    
    // After adding to cart, reset product form
    <?php if (isset($_SESSION['product_added']) && $_SESSION['product_added']): ?>
    $('#product_code').val('');
    $('#product_name').val('');
    $('#product_id').val('');
    $('#size_id_hidden').val('');
    $('#quantity').val('1');
    $('#discount').val('0');
    $('#productDetails').hide();
    $('#addToCartBtn').hide();
    $('#product_code').focus();
    <?php unset($_SESSION['product_added']); ?>
    <?php endif; ?>
    
    // Before generating invoice, copy party details
    $('#generateInvoiceBtn').on('click', function(e) {
        let mobile = $('#party_mobile_display').val();
        let name = $('#party_name').val();
        
        if (!mobile || !name) {
            e.preventDefault();
            alert('Customer details missing!\n\nPlease search customer first.');
            return false;
        }
        
        if (!confirm('Generate invoice with ' + <?php echo count($_SESSION['invoice_cart']); ?> + ' items?')) {
            e.preventDefault();
            return false;
        }
        
        $('#invoice_mobile').val(mobile);
        $('#invoice_party_name').val(name);
        $('#invoice_notes').val($('#party_notes').val());
    });
});
</script>
