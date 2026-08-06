<?php
ob_start();
$pageTitle = 'Delete Invoice - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

$invoiceNo = $_GET['invoice'] ?? '';

if (empty($invoiceNo)) {
    setFlashMessage('error', 'Invoice not found');
    header('Location: list.php');
    exit();
}

// Get invoice details
$sql = "SELECT i.*, p.party_name 
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

// Check if invoice has credit notes
$cnCheckSql = "SELECT COUNT(*) as cn_count FROM credit_notes WHERE invoice_id = ?";
$cnStmt = $conn->prepare($cnCheckSql);
$cnStmt->bind_param('i', $invoice['id']);
$cnStmt->execute();
$cnResult = $cnStmt->get_result();
$cnCheck = $cnResult->fetch_assoc();

if ($cnCheck['cn_count'] > 0) {
    setFlashMessage('error', 'Cannot delete invoice with credit notes. Delete credit notes first.');
    header('Location: view.php?invoice=' . $invoiceNo);
    exit();
}

// Handle delete confirmation
if (isset($_POST['confirm_delete'])) {
    $conn->begin_transaction();
    
    try {
        // Get all invoice items to restore stock
        $itemsSql = "SELECT * FROM invoice_items WHERE invoice_id = ?";
        $itemsStmt = $conn->prepare($itemsSql);
        $itemsStmt->bind_param('i', $invoice['id']);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        
        // Restore stock for each item
        while ($item = $itemsResult->fetch_assoc()) {
            // Add stock back
            updateStock($conn, $item['product_id'], $item['size_id'], $item['quantity'], 'add');
            
            // Record stock transaction
            recordStockTransaction(
                $conn, 
                $item['product_id'], 
                $item['size_id'], 
                $item['quantity'], 
                'IN', 
                'RETURN', 
                $invoice['id'], 
                'Invoice Deleted: ' . $invoiceNo
            );
        }
        
        // Delete invoice items
        $deleteItemsSql = "DELETE FROM invoice_items WHERE invoice_id = ?";
        $deleteItemsStmt = $conn->prepare($deleteItemsSql);
        $deleteItemsStmt->bind_param('i', $invoice['id']);
        $deleteItemsStmt->execute();
        
        // Delete invoice
        $deleteInvoiceSql = "DELETE FROM invoices WHERE id = ?";
        $deleteInvoiceStmt = $conn->prepare($deleteInvoiceSql);
        $deleteInvoiceStmt->bind_param('i', $invoice['id']);
        $deleteInvoiceStmt->execute();
        
        $conn->commit();
        
        setFlashMessage('success', 'Invoice deleted successfully and stock restored');
        header('Location: list.php');
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        setFlashMessage('error', 'Error deleting invoice: ' . $e->getMessage());
        header('Location: view.php?invoice=' . $invoiceNo);
        exit();
    }
}

// Get invoice items for display
$itemsSql = "SELECT ii.*, p.product_code, p.product_name, s.size_name
             FROM invoice_items ii
             INNER JOIN products p ON ii.product_id = p.id
             INNER JOIN sizes s ON ii.size_id = s.id
             WHERE ii.invoice_id = ?";
$itemsStmt = $conn->prepare($itemsSql);
$itemsStmt->bind_param('i', $invoice['id']);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();
$items = [];
while ($row = $itemsResult->fetch_assoc()) {
    $items[] = $row;
}
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="list.php">Invoices</a></li>
                <li class="breadcrumb-item"><a href="view.php?invoice=<?php echo $invoiceNo; ?>"><?php echo $invoiceNo; ?></a></li>
                <li class="breadcrumb-item active">Delete</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Delete Invoice Confirmation</h5>
            </div>
            <div class="card-body">
                
                <div class="alert alert-danger">
                    <h5 class="alert-heading"><i class="bi bi-trash"></i> WARNING: Permanent Deletion</h5>
                    <p class="mb-0">You are about to permanently delete this invoice. This action <strong>CANNOT be undone!</strong></p>
                </div>
                
                <!-- Invoice Details -->
                <h6 class="mb-3">Invoice to be Deleted:</h6>
                <table class="table table-bordered">
                    <tr>
                        <th width="200">Invoice No:</th>
                        <td><strong><?php echo $invoice['invoice_no']; ?></strong></td>
                    </tr>
                    <tr>
                        <th>Date:</th>
                        <td><?php echo formatDate($invoice['invoice_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Customer:</th>
                        <td><?php echo $invoice['party_name']; ?></td>
                    </tr>
                    <tr>
                        <th>Payment Mode:</th>
                        <td><?php echo $invoice['payment_mode']; ?></td>
                    </tr>
                    <tr>
                        <th>Total Amount:</th>
                        <td class="text-danger"><strong><?php echo formatCurrency($invoice['total_amount']); ?></strong></td>
                    </tr>
                </table>
                
                <!-- Items -->
                <h6 class="mb-3">Items (Stock will be restored):</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th>Size</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sr = 1;
                            foreach ($items as $item): 
                            ?>
                            <tr>
                                <td><?php echo $sr++; ?></td>
                                <td>
                                    <?php echo $item['product_code']; ?><br>
                                    <small><?php echo $item['product_name']; ?></small>
                                </td>
                                <td><?php echo $item['size_name']; ?></td>
                                <td class="text-center">
                                    <span class="badge bg-success">+<?php echo $item['quantity']; ?></span>
                                    <small class="d-block text-muted">Will be restored</small>
                                </td>
                                <td class="text-end"><?php echo formatCurrency($item['total_amount']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-warning">
                    <h6 class="alert-heading"><i class="bi bi-info-circle"></i> What will happen:</h6>
                    <ul class="mb-0">
                        <li>Invoice will be permanently deleted</li>
                        <li>All invoice items will be removed</li>
                        <li><strong>Stock will be restored</strong> for all items (quantities added back)</li>
                        <li>Stock transactions will be recorded</li>
                        <li>This action cannot be reversed</li>
                    </ul>
                </div>
                
                <form method="POST" action="" class="mt-4">
                    <input type="hidden" name="confirm_delete" value="1">
                    
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="view.php?invoice=<?php echo $invoiceNo; ?>" class="btn btn-secondary btn-lg">
                            <i class="bi bi-x-circle"></i> Cancel - Go Back
                        </a>
                        <button type="submit" class="btn btn-danger btn-lg" 
                                onclick="return confirm('FINAL CONFIRMATION\n\nAre you absolutely sure you want to DELETE this invoice?\n\nInvoice: <?php echo $invoiceNo; ?>\nAmount: <?php echo formatCurrency($invoice['total_amount']); ?>\n\nType YES to confirm:') && prompt('Type DELETE to confirm:', '') === 'DELETE';">
                            <i class="bi bi-trash"></i> Yes, Delete Invoice Permanently
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
