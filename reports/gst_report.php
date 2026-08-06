<?php
$pageTitle = 'GST Report - Payal Arban Stichis';
require_once __DIR__ . '/../includes/header.php';

$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-d');

$sql = "SELECT i.invoice_date, i.invoice_no, p.party_name, i.subtotal, i.discount_amount, 
               i.cgst_amount, i.sgst_amount, i.total_amount
        FROM invoices i
        INNER JOIN parties p ON i.party_id = p.id
        WHERE i.invoice_date BETWEEN ? AND ?
        ORDER BY i.invoice_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $fromDate, $toDate);
$stmt->execute();
$result = $stmt->get_result();

$totals = ['invoices' => 0, 'subtotal' => 0, 'discount' => 0, 'cgst' => 0, 'sgst' => 0, 'total' => 0];
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-file-earmark-text"></i> GST Report</h2>
    </div>
</div>

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">From Date</label>
                        <input type="date" class="form-control" name="from_date" value="<?php echo $fromDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">To Date</label>
                        <input type="date" class="form-control" name="to_date" value="<?php echo $toDate; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> View
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" onclick="window.print()" class="btn btn-success w-100">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        GST Summary: <?php echo formatDate($fromDate); ?> to <?php echo formatDate($toDate); ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Invoice No</th>
                        <th>Party</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">CGST</th>
                        <th class="text-end">SGST</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): 
                        $totals['invoices']++;
                        $totals['subtotal'] += $row['subtotal'];
                        $totals['discount'] += $row['discount_amount'];
                        $totals['cgst'] += $row['cgst_amount'];
                        $totals['sgst'] += $row['sgst_amount'];
                        $totals['total'] += $row['total_amount'];
                    ?>
                    <tr>
                        <td><?php echo formatDate($row['invoice_date']); ?></td>
                        <td><?php echo $row['invoice_no']; ?></td>
                        <td><?php echo $row['party_name']; ?></td>
                        <td class="text-end"><?php echo formatCurrency($row['subtotal']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($row['discount_amount']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($row['cgst_amount']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($row['sgst_amount']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($row['total_amount']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot class="table-primary">
                    <tr>
                        <th colspan="3">TOTAL (<?php echo $totals['invoices']; ?> invoices)</th>
                        <th class="text-end"><?php echo formatCurrency($totals['subtotal']); ?></th>
                        <th class="text-end"><?php echo formatCurrency($totals['discount']); ?></th>
                        <th class="text-end"><?php echo formatCurrency($totals['cgst']); ?></th>
                        <th class="text-end"><?php echo formatCurrency($totals['sgst']); ?></th>
                        <th class="text-end"><?php echo formatCurrency($totals['total']); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="alert alert-info mt-3">
            <h6>GST Summary:</h6>
            <ul class="mb-0">
                <li>Total CGST: <strong><?php echo formatCurrency($totals['cgst']); ?></strong></li>
                <li>Total SGST: <strong><?php echo formatCurrency($totals['sgst']); ?></strong></li>
                <li>Total GST: <strong><?php echo formatCurrency($totals['cgst'] + $totals['sgst']); ?></strong></li>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
