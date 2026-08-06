<?php
ob_start();
$pageTitle = 'View Invoice - Payal Arban Stichis';
require_once __DIR__ . '/../../includes/header.php';

$invoiceNo = $_GET['invoice'] ?? '';

if (empty($invoiceNo)) {
    setFlashMessage('error', 'Invoice not found');
    header('Location: list.php');
    exit();
}

// Get invoice details
$sql = "SELECT i.*, p.party_name, p.mobile, p.address 
        FROM invoices i
        INNER JOIN parties p ON i.party_id = p.id
        WHERE i.invoice_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $invoiceNo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Invoice not found');
    header('Location: list.php');
    exit();
}

$invoice = $result->fetch_assoc();

// Get invoice items
$itemsSql = "SELECT ii.*, p.product_code, p.product_name, s.size_name 
             FROM invoice_items ii
             INNER JOIN products p ON ii.product_id = p.id
             INNER JOIN sizes s ON ii.size_id = s.id
             WHERE ii.invoice_id = ?";
$itemsStmt = $conn->prepare($itemsSql);
$itemsStmt->bind_param('i', $invoice['id']);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();
?>

<div class="no-print mb-3">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer"></i> Print Invoice
    </button>
    <a href="create_invoice.php" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> New Invoice
    </a>
    <a href="list.php" class="btn btn-secondary">
        <i class="bi bi-list"></i> All Invoices
    </a>
</div>

<div class="print-area" style="max-width: 148mm; margin: 0 auto;">
    <style>
        @media print {
            @page { size: A5; margin: 10mm; }
            body { font-size: 10pt; }
        }
        .invoice-box { border: 1px solid #ddd; padding: 15px; }
    </style>
    
    <div class="invoice-box">
        <!-- Header -->
        <div class="text-center mb-3">
            <h3>Payal Arban Stichis</h3>
            <p class="mb-0">Ready-made Garments</p>
        </div>
        
        <hr>
        
        <!-- Invoice Details -->
        <div class="row mb-3">
            <div class="col-6">
                <strong>Invoice No:</strong> <?php echo $invoice['invoice_no']; ?><br>
                <strong>Date:</strong> <?php echo formatDate($invoice['invoice_date']); ?>
            </div>
            <div class="col-6 text-end">
                <strong>Payment:</strong> <?php echo $invoice['payment_mode']; ?>
            </div>
        </div>
        
        <!-- Party Details -->
        <div class="mb-3">
            <strong>Customer Details:</strong><br>
            <?php echo $invoice['party_name']; ?><br>
            Mobile: <?php echo $invoice['mobile']; ?><br>
            <?php if (!empty($invoice['address'])): ?>
            <?php echo $invoice['address']; ?>
            <?php endif; ?>
        </div>
        
        <!-- Items Table -->
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $sr = 1;
                while ($item = $itemsResult->fetch_assoc()): 
                ?>
                <tr>
                    <td><?php echo $sr++; ?></td>
                    <td>
                        <?php echo $item['product_code']; ?> - <?php echo $item['product_name']; ?><br>
                        <small>Size: <?php echo $item['size_name']; ?></small>
                    </td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td class="text-end"><?php echo formatCurrency($item['mrp']); ?></td>
                    <td class="text-end"><?php echo formatCurrency($item['total_amount']); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- Summary -->
        <div class="row">
            <div class="col-6 offset-6">
                <table class="table table-sm mb-0">
                    <tr>
                        <td>Subtotal:</td>
                        <td class="text-end"><?php echo formatCurrency($invoice['subtotal']); ?></td>
                    </tr>
                    <?php if ($invoice['discount_amount'] > 0): ?>
                    <tr>
                        <td>Discount:</td>
                        <td class="text-end">-<?php echo formatCurrency($invoice['discount_amount']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td>CGST:</td>
                        <td class="text-end"><?php echo formatCurrency($invoice['cgst_amount']); ?></td>
                    </tr>
                    <tr>
                        <td>SGST:</td>
                        <td class="text-end"><?php echo formatCurrency($invoice['sgst_amount']); ?></td>
                    </tr>
                    <tr class="fw-bold">
                        <td>Total:</td>
                        <td class="text-end"><?php echo formatCurrency($invoice['total_amount']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p class="mb-0"><small>Thank you for your business!</small></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
