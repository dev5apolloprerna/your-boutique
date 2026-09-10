<?php
ob_start();
$pageTitle = 'All Credit Notes - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

$sql = "SELECT cn.*, p.party_name, i.invoice_no
        FROM credit_notes cn
        INNER JOIN parties p ON cn.party_id = p.id
        INNER JOIN invoices i ON cn.invoice_id = i.id
        ORDER BY cn.created_at DESC LIMIT 100";
$result = $conn->query($sql);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-arrow-return-left"></i> All Credit Notes</h2>
            <a href="create_credit_note.php" class="btn btn-danger">
                <i class="bi bi-plus-circle"></i> Create Credit Note
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Credit Note No</th>
                        <th>Date</th>
                        <th>Original Invoice</th>
                        <th>Party</th>
                        <th>Refund Mode</th>
                        <th class="text-end">Amount</th>
                        <th width="100">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $row['credit_note_no']; ?></strong></td>
                            <td><?php echo formatDate($row['credit_date']); ?></td>
                            <td>
                                <a href="<?= $web_url ?>/modules/invoice/view.php?invoice=<?php echo $row['invoice_no']; ?>">
                                    <?php echo $row['invoice_no']; ?>
                                </a>
                            </td>
                            <td><?php echo $row['party_name']; ?></td>
                            <td><span class="badge bg-danger"><?php echo $row['refund_mode']; ?></span></td>
                            <td class="text-end text-danger">-<?php echo formatCurrency($row['total_amount']); ?></td>
                            <td>
                                <a href="view_credit_note.php?cn=<?php echo $row['credit_note_no']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                            <p class="mt-2">No credit notes yet</p>
                        </td>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
