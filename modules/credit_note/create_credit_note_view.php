<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-arrow-return-left"></i> Create Credit Note (Return/Exchange)</h2>
    </div>
</div>

<!-- Step 1: Select Invoice -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-1-circle"></i> Select Invoice
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-6">
                    <label for="invoice" class="form-label">Invoice Number <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="invoice" id="invoice" 
                               value="<?php echo $selectedInvoice['invoice_no'] ?? ''; ?>" 
                               placeholder="INV000001" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Load Invoice
                        </button>
                    </div>
                </div>
                <?php if ($selectedInvoice): ?>
                <div class="col-md-6">
                    <label class="form-label">Customer Details</label>
                    <div class="alert alert-info mb-0">
                        <strong><?php echo $selectedInvoice['party_name']; ?></strong><br>
                        Mobile: <?php echo $selectedInvoice['mobile']; ?><br>
                        Date: <?php echo formatDate($selectedInvoice['invoice_date']); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedInvoice): ?>
<!-- Step 2: Select Items to Return -->
<div class="row">
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-2-circle"></i> Select Items to Return
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th>Size</th>
                                <th class="text-center">Original Qty</th>
                                <th class="text-center">Available</th>
                                <th class="text-end">Amount</th>
                                <th width="150">Return Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoiceItems as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $item['product_code']; ?></strong><br>
                                    <small><?php echo $item['product_name']; ?></small>
                                </td>
                                <td><span class="badge bg-secondary"><?php echo $item['size_name']; ?></span></td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo $item['available_qty'] > 0 ? 'success' : 'secondary'; ?>">
                                        <?php echo $item['available_qty']; ?>
                                    </span>
                                </td>
                                <td class="text-end"><?php echo formatCurrency($item['total_amount']); ?></td>
                                <td>
                                    <?php if ($item['available_qty'] > 0): ?>
                                    <form method="POST" action="" class="d-flex gap-1">
                                        <input type="hidden" name="add_return_item" value="1">
                                        <input type="hidden" name="invoice_no" value="<?php echo $selectedInvoice['invoice_no']; ?>">
                                        <input type="hidden" name="invoice_item_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" class="form-control form-control-sm" name="return_quantity" 
                                               min="1" max="<?php echo $item['available_qty']; ?>" value="<?php echo $item['available_qty']; ?>" required>
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="bi bi-plus"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-muted small">Fully returned</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Step 3: Add Exchange Items (Optional) -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-3-circle"></i> Add Exchange Items (Optional)
            </div>
            <div class="card-body">
                <form method="POST" action="" id="exchangeForm">
                    <input type="hidden" name="add_exchange_item" value="1">
                    <input type="hidden" name="invoice_no" value="<?php echo $selectedInvoice['invoice_no']; ?>">
                    <input type="hidden" name="product_id" id="ex_product_id">
                    <input type="hidden" name="size_id" id="ex_size_id">
                    
                    <div class="row">
                        <div class="col-md-5 mb-2">
                            <input type="text" class="form-control form-control-sm" id="ex_product_code" 
                                   placeholder="Product Code">
                        </div>
                        <div class="col-md-3 mb-2">
                            <select class="form-select form-select-sm" id="ex_size_select" name="size_id" disabled>
                                <option value="">Size</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <input type="number" class="form-control form-control-sm" name="quantity" 
                                   min="1" value="1" required>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-sm btn-success w-100">Add</button>
                        </div>
                    </div>
                    <small class="text-muted">Add items customer wants to exchange with</small>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Right Side: Return Summary -->
    <div class="col-md-5">
        <!-- Return Items List -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-arrow-return-left"></i> Items to Return</span>
                <?php if (!empty($_SESSION['credit_note_items'])): ?>
                <span class="badge bg-primary"><?php echo count($_SESSION['credit_note_items']); ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                <?php if (empty($_SESSION['credit_note_items'])): ?>
                <div class="text-center p-4 text-muted">
                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                    <p class="mt-2">No return items added</p>
                </div>
                <?php else: ?>
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php foreach ($_SESSION['credit_note_items'] as $returnId => $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo $item['product_code']; ?></strong><br>
                                <small><?php echo $item['product_name']; ?></small><br>
                                <span class="badge bg-secondary"><?php echo $item['size_name']; ?></span> x <?php echo $item['quantity']; ?>
                            </td>
                            <td class="text-end">
                                <?php echo formatCurrency($item['total_per_unit'] * $item['quantity']); ?><br>
                                <a href="?invoice=<?php echo $selectedInvoice['invoice_no']; ?>&remove_return=<?php echo $returnId; ?>" 
                                   class="btn btn-sm btn-danger mt-1">
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
        
        <!-- Exchange Items List -->
        <?php if (isset($_SESSION['exchange_items']) && !empty($_SESSION['exchange_items'])): ?>
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-arrow-repeat"></i> Exchange Items
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <?php foreach ($_SESSION['exchange_items'] as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo $item['product_code']; ?></strong><br>
                                <small><?php echo $item['product_name']; ?></small><br>
                                <span class="badge bg-info"><?php echo $item['size_name']; ?></span> x <?php echo $item['quantity']; ?>
                            </td>
                            <td class="text-end">
                                <?php echo formatCurrency($item['mrp'] * $item['quantity']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Summary and Generate -->
        <?php if (!empty($_SESSION['credit_note_items'])): ?>
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calculator"></i> Summary
            </div>
            <div class="card-body">
                <table class="table table-sm mb-3">
                    <tr>
                        <td>Return Amount:</td>
                        <td class="text-end text-danger">-<?php echo formatCurrency($returnSummary['total']); ?></td>
                    </tr>
                    <?php if ($exchangeSummary['total'] > 0): ?>
                    <tr>
                        <td>Exchange Amount:</td>
                        <td class="text-end text-success">+<?php echo formatCurrency($exchangeSummary['total']); ?></td>
                    </tr>
                    <tr class="table-primary">
                        <td><strong>Balance:</strong></td>
                        <td class="text-end">
                            <strong>
                                <?php if ($balanceAmount > 0): ?>
                                    Customer Pays: <?php echo formatCurrency($balanceAmount); ?>
                                <?php elseif ($balanceAmount < 0): ?>
                                    Refund: <?php echo formatCurrency(abs($balanceAmount)); ?>
                                <?php else: ?>
                                    No Balance
                                <?php endif; ?>
                            </strong>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <form method="POST" action="">
                    <input type="hidden" name="generate_credit_note" value="1">
                    <input type="hidden" name="invoice_no" value="<?php echo $selectedInvoice['invoice_no']; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Refund Mode <span class="text-danger">*</span></label>
                        <select class="form-select" name="refund_mode" required>
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="UPI">UPI</option>
                            <option value="Exchange" <?php echo ($exchangeSummary['total'] > 0) ? 'selected' : ''; ?>>Exchange</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-danger btn-lg w-100">
                        <i class="bi bi-receipt-cutoff"></i> Generate Credit Note
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php 
require_once __DIR__ . '/../../includes/footer.php'; 
?>

<script>
$(document).ready(function() {
    // Exchange product search
    $('#ex_product_code').on('input', function() {
        let currentUrl = window.location.href;
        let baseUrl = currentUrl.split('/modules/')[0];
        let code = $(this).val().toUpperCase();
        $(this).val(code);
        alert(baseUrl + '/api/get_product.php');
        if (code.length >= 3) {
            $.ajax({
                url: baseUrl + '/api/get_product.php',
                method: 'POST',
                data: { code: code },
                dataType: 'json',
                success: function(data) {
                    if (data.found) {
                        $('#ex_product_id').val(data.id);
                        
                        let options = '<option value="">Select Size</option>';
                        data.sizes.forEach(function(size) {
                            if (size.quantity > 0) {
                                options += `<option value="${size.id}">${size.name} (${size.quantity} available)</option>`;
                            }
                        });
                        $('#ex_size_select').html(options).prop('disabled', false);
                    }
                }
            });
        }
    });
    
    $('#ex_size_select').on('change', function() {
        $('#ex_size_id').val($(this).val());
    });
});
</script>



