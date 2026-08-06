<?php
ob_start();
$pageTitle = 'All Invoices - Payal Arban Stichis';
require_once __DIR__ . '/../../includes/header.php';

$sql = "SELECT i.*, p.party_name FROM invoices i
        INNER JOIN parties p ON i.party_id = p.id
        ORDER BY i.created_at DESC LIMIT 100";
$result = $conn->query($sql);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-receipt"></i> All Invoices</h2>
            <a href="create_invoice.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Create Invoice
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
                        <th>Invoice No</th>
                        <th>Date</th>
                        <th>Party</th>
                        <th>Payment</th>
                        <th class="text-end">Amount</th>
                        <th width="100">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo $row['invoice_no']; ?></strong></td>
                        <td><?php echo formatDate($row['invoice_date']); ?></td>
                        <td><?php echo $row['party_name']; ?></td>
                        <td><span class="badge bg-info"><?php echo $row['payment_mode']; ?></span></td>
                        <td class="text-end text-currency"><?php echo formatCurrency($row['total_amount']); ?></td>
                        <td>
                            <a href="view.php?invoice=<?php echo $row['invoice_no']; ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
