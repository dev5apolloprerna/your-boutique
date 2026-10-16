<?php
$pageTitle = 'Sales Report - Payal Arban Stichis';
require_once __DIR__ . '/../includes/header.php';

$fromDate = $_GET['from_date'] ?? date('Y-m-01');
$toDate = $_GET['to_date'] ?? date('Y-m-d');

$sql = "SELECT DATE(i.invoice_date) as sale_date,
               COUNT(*) as invoice_count,
               SUM(i.total_amount) as gross_sales,
               SUM(COALESCE(ca.credit_note_amount, 0)) as credit_note_total,
               SUM(i.total_amount - COALESCE(ca.credit_note_amount, 0)) as total_sales
        FROM invoices i
        LEFT JOIN (
            SELECT invoice_id, SUM(amount) as credit_note_amount
            FROM credit_note_applications
            GROUP BY invoice_id
        ) ca ON ca.invoice_id = i.id
        WHERE i.invoice_date BETWEEN ? AND ?
        GROUP BY DATE(i.invoice_date)
        ORDER BY i.invoice_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $fromDate, $toDate);
$stmt->execute();
$result = $stmt->get_result();

$grandTotal = 0;
$totalInvoices = 0;
$grossTotal = 0;
$creditNoteTotal = 0;
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-graph-up"></i> Sales Report</h2>
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
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        Sales from <?php echo formatDate($fromDate); ?> to <?php echo formatDate($toDate); ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th class="text-center">Invoices</th>
                        <th class="text-end">Gross Sales</th>
                        <th class="text-end">Credit Note Used</th>
                        <th class="text-end">Net Sales</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()):
                        $grandTotal += $row['total_sales'];
                        $grossTotal += $row['gross_sales'];
                        $creditNoteTotal += $row['credit_note_total'];
                        $totalInvoices += $row['invoice_count'];
                    ?>
                    <tr>
                        <td><?php echo formatDate($row['sale_date']); ?></td>
                        <td class="text-center"><?php echo $row['invoice_count']; ?></td>
                        <td class="text-end"><?php echo formatCurrency($row['gross_sales']); ?></td>
                        <td class="text-end text-danger">-<?php echo formatCurrency($row['credit_note_total']); ?></td>
                        <td class="text-end fw-bold"><?php echo formatCurrency($row['total_sales']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot class="table-primary">
                    <tr>
                        <th>TOTAL</th>
                        <th class="text-center"><?php echo $totalInvoices; ?></th>
                        <th class="text-end"><?php echo formatCurrency($grossTotal); ?></th>
                        <th class="text-end text-danger">-<?php echo formatCurrency($creditNoteTotal); ?></th>
                        <th class="text-end"><?php echo formatCurrency($grandTotal); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
