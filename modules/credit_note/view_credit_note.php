<?php
ob_start();
$pageTitle = 'View Credit Note - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

$creditNoteNo = $_GET['cn'] ?? '';

if (empty($creditNoteNo)) {
    setFlashMessage('error', 'Credit Note not found');
    header('Location: list.php');
    exit();
}

// Get credit note details
$sql = "SELECT cn.*, p.party_name, p.mobile, p.address, i.invoice_no
        FROM credit_notes cn
        INNER JOIN parties p ON cn.party_id = p.id
        INNER JOIN invoices i ON cn.invoice_id = i.id
        WHERE cn.credit_note_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $creditNoteNo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Credit Note not found');
    header('Location: list.php');
    exit();
}

$creditNote = $result->fetch_assoc();

// Get credit note items
$itemsSql = "SELECT cni.*, p.product_code, p.product_name, s.size_name
             FROM credit_note_items cni
             INNER JOIN products p ON cni.product_id = p.id
             INNER JOIN sizes s ON cni.size_id = s.id
             WHERE cni.credit_note_id = ?";
$itemsStmt = $conn->prepare($itemsSql);
$itemsStmt->bind_param('i', $creditNote['id']);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();
?>

<div class="no-print mb-3">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer"></i> Print Credit Note
    </button>
    <a href="create_credit_note.php" class="btn btn-danger">
        <i class="bi bi-plus-circle"></i> New Credit Note
    </a>
    <a href="list.php" class="btn btn-secondary">
        <i class="bi bi-list"></i> All Credit Notes
    </a>
</div>

<div class="print-area" style="max-width: 148mm; margin: 0 auto;">
    <style>
        @media print {
            @page { size: A5; margin: 10mm; }
            body { font-size: 10pt; }
        }
        .credit-note-box { border: 2px solid #dc3545; padding: 15px; }
    </style>
    
    <div class="credit-note-box">
        <!-- Header -->
        <div class="text-center mb-3">
            <h3>Payal Urban Stitch</h3>
            <p class="mb-0 text-danger"><strong>CREDIT NOTE</strong></p>
        </div>
        
        <hr>
        
        <!-- Credit Note Details -->
        <div class="row mb-3">
            <div class="col-6">
                <strong>Credit Note No:</strong> <?php echo $creditNote['credit_note_no']; ?><br>
                <strong>Date:</strong> <?php echo formatDate($creditNote['credit_date']); ?><br>
                <strong>Original Invoice:</strong> <?php echo $creditNote['invoice_no']; ?>
            </div>
            <div class="col-6 text-end">
                <strong>Refund Mode:</strong> <?php echo $creditNote['refund_mode']; ?>
            </div>
        </div>
        
        <!-- Party Details -->
        <div class="mb-3">
            <strong>Customer Details:</strong><br>
            <?php echo $creditNote['party_name']; ?><br>
            Mobile: <?php echo $creditNote['mobile']; ?><br>
            <?php if (!empty($creditNote['address'])): ?>
            <?php echo $creditNote['address']; ?>
            <?php endif; ?>
        </div>
        
        <!-- Items Table -->
        <table class="table table-sm table-bordered">
            <thead class="table-danger">
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
                        <td class="text-end"><?php echo formatCurrency($creditNote['subtotal']); ?></td>
                    </tr>
                    <tr>
                        <td>CGST:</td>
                        <td class="text-end"><?php echo formatCurrency($creditNote['cgst_amount']); ?></td>
                    </tr>
                    <tr>
                        <td>SGST:</td>
                        <td class="text-end"><?php echo formatCurrency($creditNote['sgst_amount']); ?></td>
                    </tr>
                    <tr class="fw-bold table-danger">
                        <td>Credit Amount:</td>
                        <td class="text-end"><?php echo formatCurrency($creditNote['total_amount']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php if (!empty($creditNote['notes'])): ?>
        <div class="mt-3">
            <strong>Notes:</strong><br>
            <?php echo nl2br(htmlspecialchars($creditNote['notes'])); ?>
        </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <p class="mb-0"><small>This is a system generated credit note</small></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
