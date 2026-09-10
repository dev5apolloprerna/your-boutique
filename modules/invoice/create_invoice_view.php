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
                            <!--<label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>-->
                            <label for="customer_search" class="form-label">Customer Name <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <!--<span class="input-group-text"><i class="bi bi-phone"></i></span>-->
                                <!--<input type="text" class="form-control mobile-input number-only" id="mobile" -->
                                <!--       name="mobile" placeholder="10-digit mobile" maxlength="10" required autofocus>-->
                                <span class="input-group-text"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="customer_search"
                                       placeholder="Enter at least 2 letters" required autofocus>
                                <button type="button" class="btn btn-primary" id="searchCustomerBtn">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="customerSearchResults" class="list-group mb-3"></div>
                    
                    <div id="customerDetailsForm" style="display:none;">
                        <input type="hidden" name="lock_customer" value="1">
                        <input type="hidden" name="party_id" id="party_id" value="0">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="party_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="party_name" name="party_name" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mobile" class="form-label">Mobile Number (Optional)</label>
                                <input type="text" class="form-control mobile-input number-only" id="mobile" name="mobile"
                                       placeholder="10-digit mobile" maxlength="10">
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
                    <!--<p class="mb-1"><strong>Mobile:</strong> <?php echo $customer['mobile']; ?></p>-->
                    <p class="mb-1"><strong>Mobile:</strong> <?php echo !empty($customer['mobile']) ? htmlspecialchars($customer['mobile']) : 'Not provided'; ?></p>
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
                                       placeholder="E000001" required autofocus>
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
                                <!--<label for="discount" class="form-label">Discount</label>-->
                                <label for="discount_percent" class="form-label">Item Discount (%)</label>
                                <div class="input-group">
                                    <!--<span class="input-group-text">₹</span>-->
                                    <!--<input type="number" class="form-control" id="discount" name="discount" -->
                                    <!--       value="0" min="0" step="0.01">-->
                                    <span class="input-group-text">%</span>
                                    <input type="number" class="form-control" id="discount_percent" name="discount_percent"
                                           value="0" min="0" max="100" step="0.01">
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
                            // $itemTotal = ($item['mrp'] * $item['quantity']) - floatval($item['discount']);
                            $itemGross = $item['mrp'] * $item['quantity'];
                            $itemDiscount = $itemGross * (floatval($item['discount_percent'] ?? 0) / 100);
                            $itemTotal = $itemGross - $itemDiscount;
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo $item['product_code']; ?></strong><br>
                                <small><?php echo $item['size_code'].$item['product_name']; ?></small><br>
                                <span class="badge bg-secondary"><?php echo $item['size_name']; ?></span>
                                x <?php echo $item['quantity']; ?>
                                <?php  //if ($item['discount'] > 0): ?>
                                <!--<br><small class="text-danger">-<?php echo formatCurrency($item['discount']); ?></small>-->
                                <?php if (!empty($item['discount_percent'])): ?>
                                <br><small class="text-danger">-<?php echo number_format($item['discount_percent'], 2); ?>%</small>
                                <?php   endif; ?>
                                
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
                                     data-subtotal="<?php echo $cartSummary['subtotal']; ?>"
                                    data-item-discount="<?php
                                        $itemDiscountOnly = 0;
                                        foreach ($_SESSION['invoice_cart'] as $summaryItem) {
                                            $summaryGross = $summaryItem['mrp'] * $summaryItem['quantity'];
                                            $itemDiscountOnly += round($summaryGross * (floatval($summaryItem['discount_percent'] ?? 0) / 100), 2);
                                        }
                                        echo $itemDiscountOnly;
                                    ?>">
                                    <!--data-subtotal="<?php echo $cartSummary['subtotal']; ?>">-->
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
                            <!--<td><strong>Bill Discount</strong></td>-->
                            <!--<td><input type="number" class="form-control" id="bill_discount" name="bill_discount" value="<?php echo $_SESSION['bill_discount'] ?? 0; ?>" min="0" step="0.01"></td>-->
                            <td><strong>Bill Discount (%)</strong></td>
                            <td><input type="number" class="form-control" id="bill_discount_percent" name="bill_discount_percent" value="<?php echo htmlspecialchars($_SESSION['bill_discount_percent'] ?? 0); ?>" min="0" max="100" step="0.01"></td>
                        </tr>
                        <tr class="table-success">
                            <td><strong>Total:</strong></td>
                            <td class="text-end"><strong id="grand_total"><?php echo number_format($cartSummary['subtotal'] - $cartSummary['discount'],2); ?></strong></td>
                        </tr>
                    </table>

                    <?php if (!empty($availableCreditNotes)): ?>
                    <div class="mb-3">
                        <label for="credit_note_id" class="form-label">Apply Credit Note</label>
                        <select class="form-select" id="credit_note_id" name="credit_note_id">
                            <option value="" data-balance="0">Do not apply</option>
                            <?php foreach ($availableCreditNotes as $availableCreditNote): ?>
                            <option value="<?php echo (int) $availableCreditNote['id']; ?>"
                                    data-balance="<?php echo number_format($availableCreditNote['available_amount'], 2, '.', ''); ?>">
                                <?php echo htmlspecialchars($availableCreditNote['credit_note_no']); ?>
                                (available: <?php echo formatCurrency($availableCreditNote['available_amount']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="input-group mt-2" id="credit_note_amount_group" style="display:none;">
                            <span class="input-group-text">Use ₹</span>
                            <input type="number" class="form-control" id="credit_note_amount" name="credit_note_amount"
                                   min="0.01" step="0.01" value="0">
                        </div>
                        <div class="form-text">Unused balance remains available for another invoice.</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3" id="payments_section">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Payments <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add_payment">
                                <i class="bi bi-plus-circle"></i> Add Payment Mode
                            </button>
                        </div>
                        <div id="payment_rows">
                            <div class="payment-row input-group mb-2">
                                <select class="form-select payment-mode" name="payment_modes[]" required>
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="UPI">UPI</option>
                                </select>
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control payment-amount" name="payment_amounts[]"
                                       min="0" step="0.01" required aria-label="Payment amount">
                                <button type="button" class="btn btn-outline-danger remove-payment" aria-label="Remove payment" disabled>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div id="payment_balance" class="form-text text-end"></div>
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
    // $('#mobile').on('keypress', function(e) {
    $('#customer_search').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            searchCustomer();
        }
    });
    
    function searchCustomer() {
        // let mobile = $('#mobile').val().trim();
        let name = $('#customer_search').val().trim();
        
        // if (mobile.length !== 10) {
        //     alert('Please enter 10-digit mobile number');
        //     $('#mobile').focus();
        //     return;
        // }
        if (name.length < 2) {
            alert('Please enter at least 2 letters of the customer name');
            $('#customer_search').focus();
            return;
        }
        
        $.ajax({
            url: baseUrl + '/modules/party/ajax_search.php',
            method: 'POST',
            // data: { mobile: mobile },
            data: { name: name },
            dataType: 'json',
            success: function(data) {
                // $('#customerDetailsForm').show();
                // $('#party_name').focus();
                console.log(data.found);
                if (data.found) {
                    // $('#party_name').val(data.name);
                    // $('#party_notes').val(data.notes || '');
                    let results = '';
                    data.customers.forEach(function(customer) {
                        let mobile = customer.mobile || 'No mobile';
                        results += '<button type="button" class="list-group-item list-group-item-action customer-result" data-customer=\'' + JSON.stringify(customer).replace(/'/g, '&#39;') + '\'><strong>' + escapeHtml(customer.name) + '</strong><br><small>' + escapeHtml(mobile) + '</small></button>';
                    });
                    results += '<button type="button" class="list-group-item list-group-item-action list-group-item-success new-customer-result"><i class="bi bi-person-plus"></i> <strong>Create new customer</strong><br><small>None of the customers above is the person you want</small></button>';
                    $('#customerSearchResults').html(results);
                    // alert('✓ Customer found: ' + data.name + '\n\nClick "Lock Customer" to continue.');
                } else {
                    // $('#party_name').val('');
                    // $('#party_notes').val('');
                    // alert('New customer! Enter name and click "Lock Customer".');
                    prepareNewCustomer(name);
                    $('#customerSearchResults').html('<div class="alert alert-info">No customer found. Complete the details below to create a new customer.</div>');
                }
            },
            error: function() {
                alert('Error searching customer');
            }
        });
    }
    
    $(document).on('click', '.customer-result', function() {
        let customer = $(this).data('customer');
        $('#party_id').val(customer.id);
        $('#party_name').val(customer.name).prop('readonly', true);
        $('#mobile').val(customer.mobile || '').prop('readonly', true);
        $('#party_notes').val(customer.notes || '').prop('readonly', true);
        $('#customerDetailsForm').show();
        $('.customer-result').removeClass('active');
        $(this).addClass('active');
    });

    $(document).on('click', '.new-customer-result', function() {
        prepareNewCustomer($('#customer_search').val().trim());
        $('.customer-result, .new-customer-result').removeClass('active');
        $(this).addClass('active');
    });


    function prepareNewCustomer(name) {
                    $('#party_id').val('0');
                    $('#party_name').val(name).prop('readonly', false);
                    $('#mobile').val('').prop('readonly', false);
                    $('#party_notes').val('').prop('readonly', false);
                    $('#customerDetailsForm').show();
                    $('#party_name').focus();
    }

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
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
        // var bill_discount = $('#bill_discount').val();
        
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
                    // Reload the cart; the pageshow handler below restores focus.
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
    // $('#discount').val('0');
    // $('#bill_discount').val(bill_discount);
    $('#discount_percent').val('0');
    $('#productDetailsSection').hide();
    $('#product_code').focus();
    <?php endif; ?>
    
    // Browsers can restore focus to the search button after a reload. Always
    // return the cursor to the barcode/product-code box when billing is active.
    <?php if ($customerLocked): ?>
    window.setTimeout(function() {
        $('#product_code').trigger('focus').select();
    }, 0);
    <?php endif; ?>
});

<?php if ($customerLocked): ?>
window.addEventListener('pageshow', function() {
    window.setTimeout(function() {
        document.getElementById('product_code')?.focus();
    }, 0);
});
<?php endif; ?>
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
    // let billDiscount = parseFloat($("#bill_discount").val()) || 0;
    let itemDiscount = parseFloat($("#subtotal_amount").data("item-discount")) || 0;
    let billDiscountPercent = Math.min(100, Math.max(0, parseFloat($("#bill_discount_percent").val()) || 0));
    let billDiscount = (subtotal - itemDiscount) * billDiscountPercent / 100;
    
    // let total = subtotal - billDiscount;
    let total = subtotal - itemDiscount - billDiscount;
    
    if (total < 0)
        total = 0;
    
    $("#grand_total").html("₹" + total.toFixed(2));
    if ($('.payment-row').length === 1) {
        $('.payment-amount').val(total.toFixed(2));
    }
    updatePaymentBalance();
}

// $("#bill_discount").on("input", updateBillDiscount);
$("#bill_discount_percent").on("input", updateBillDiscount);

updateBillDiscount();

function invoiceTotal() {
    return parseFloat($('#grand_total').text().replace(/[^0-9.-]/g, '')) || 0;
}

function updatePaymentBalance() {
    let paid = 0;
    $('.payment-amount').each(function() {
        paid += parseFloat($(this).val()) || 0;
    });
    let credit = parseFloat($('#credit_note_amount').val()) || 0;
    let balance = invoiceTotal() - paid - credit;
    let balanced = Math.abs(balance) < 0.01;
    $('#payment_balance')
        .toggleClass('text-success', balanced)
        .toggleClass('text-danger', !balanced)
        .text(balanced ? 'Payment total matched' : 'Remaining: ₹' + balance.toFixed(2));
    $('.remove-payment').prop('disabled', $('.payment-row').length === 1);
    $('#add_payment').prop('disabled', $('.payment-row').length >= 3);
}

$('#credit_note_id').on('change', function() {
    let balance = parseFloat($(this).find(':selected').data('balance')) || 0;
    let amount = Math.min(balance, invoiceTotal());
    $('#credit_note_amount_group').toggle(balance > 0);
    $('#credit_note_amount').attr('max', amount.toFixed(2)).val(balance > 0 ? amount.toFixed(2) : '0');
    if ($('.payment-row').length === 1) {
        $('.payment-amount').val(Math.max(0, invoiceTotal() - amount).toFixed(2));
    }
    updatePaymentBalance();
});

$(document).on('input', '#credit_note_amount', function() {
    if ($('.payment-row').length === 1) {
        let credit = parseFloat($(this).val()) || 0;
        $('.payment-amount').val(Math.max(0, invoiceTotal() - credit).toFixed(2));
    }
    updatePaymentBalance();
});

$('#add_payment').on('click', function() {
    let remaining = invoiceTotal();
    $('.payment-amount').each(function() {
        remaining -= parseFloat($(this).val()) || 0;
    });
    if (remaining < 0.01) {
        let largestInput = $('.payment-amount').first();
        $('.payment-amount').each(function() {
            if ((parseFloat($(this).val()) || 0) > (parseFloat(largestInput.val()) || 0)) {
                largestInput = $(this);
            }
        });
        let largestAmount = parseFloat(largestInput.val()) || 0;
        remaining = Math.floor((largestAmount / 2) * 100) / 100;
        largestInput.val((largestAmount - remaining).toFixed(2));
    }
    let row = $('.payment-row').first().clone();
    let usedModes = $('.payment-mode').map(function() { return $(this).val(); }).get();
    let nextMode = ['Cash', 'Card', 'UPI'].find(function(mode) { return !usedModes.includes(mode); });
    row.find('.payment-mode').val(nextMode || 'UPI');
    row.find('.payment-amount').val(Math.max(0, remaining).toFixed(2));
    row.find('.remove-payment').prop('disabled', false);
    $('#payment_rows').append(row);
    updatePaymentBalance();
});

$(document).on('input change', '.payment-amount, .payment-mode', updatePaymentBalance);
$(document).on('click', '.remove-payment', function() {
    if ($('.payment-row').length > 1) {
        $(this).closest('.payment-row').remove();
        updatePaymentBalance();
    }
});
</script>
