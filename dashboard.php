<?php
$pageTitle = 'Dashboard - Your-boutique';
require_once __DIR__ . '/includes/header.php';

// Get dashboard statistics
$stats = [];

// Total Products
$result = $conn->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
$stats['total_products'] = $result->fetch_assoc()['count'];

// Total Stock Value
$result = $conn->query("
    SELECT SUM(ps.quantity * p.mrp) as total_value 
    FROM product_stock ps 
    INNER JOIN products p ON ps.product_id = p.id 
    WHERE p.is_active = 1
");
$row = $result->fetch_assoc();
$stats['stock_value'] = $row['total_value'] ?? 0;

// Today's Sales
$today = date('Y-m-d');
$result = $conn->query("
    SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
    FROM invoices 
    WHERE invoice_date = '$today'
");
$row = $result->fetch_assoc();
$stats['today_invoices'] = $row['count'];
$stats['today_sales'] = $row['total'];

// This Month Sales
$firstDay = date('Y-m-01');
$result = $conn->query("
    SELECT COUNT(*) as count, COALESCE(SUM(total_amount), 0) as total 
    FROM invoices 
    WHERE invoice_date >= '$firstDay'
");
$row = $result->fetch_assoc();
$stats['month_invoices'] = $row['count'];
$stats['month_sales'] = $row['total'];

// Low Stock Products (less than 5 pieces)
$result = $conn->query("
    SELECT p.product_name, s.size_name, ps.quantity 
    FROM product_stock ps
    INNER JOIN products p ON ps.product_id = p.id
    INNER JOIN sizes s ON ps.size_id = s.id
    WHERE ps.quantity < 5 AND p.is_active = 1
    ORDER BY ps.quantity ASC
    LIMIT 10
");
$lowStockProducts = [];
while ($row = $result->fetch_assoc()) {
    $lowStockProducts[] = $row;
}

// Recent Invoices
$result = $conn->query("
    SELECT i.invoice_no, i.invoice_date, i.total_amount, p.party_name 
    FROM invoices i
    INNER JOIN parties p ON i.party_id = p.id
    ORDER BY i.created_at DESC
    LIMIT 5
");
$recentInvoices = [];
while ($row = $result->fetch_assoc()) {
    $recentInvoices[] = $row;
}
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="bi bi-speedometer2"></i> Dashboard
        </h2>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card border-start-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Products</h6>
                        <h3 class="mb-0"><?php echo $stats['total_products']; ?></h3>
                    </div>
                    <i class="bi bi-box-seam dashboard-icon text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card border-start-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Stock Value</h6>
                        <h3 class="mb-0"><?php echo formatCurrency($stats['stock_value']); ?></h3>
                    </div>
                    <i class="bi bi-currency-rupee dashboard-icon text-success"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card border-start-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Today's Sales</h6>
                        <h3 class="mb-0"><?php echo formatCurrency($stats['today_sales']); ?></h3>
                        <small class="text-muted"><?php echo $stats['today_invoices']; ?> invoices</small>
                    </div>
                    <i class="bi bi-cart-check dashboard-icon text-warning"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card dashboard-card border-start-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Month Sales</h6>
                        <h3 class="mb-0"><?php echo formatCurrency($stats['month_sales']); ?></h3>
                        <small class="text-muted"><?php echo $stats['month_invoices']; ?> invoices</small>
                    </div>
                    <i class="bi bi-graph-up dashboard-icon text-danger"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning-charge"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="<?= $web_url ?>modules/invoice/create_invoice.php" class="btn btn-primary w-100">
                            <i class="bi bi-receipt"></i> Create Invoice
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= $web_url ?>modules/products/add.php" class="btn btn-success w-100">
                            <i class="bi bi-plus-circle"></i> Add Product
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= $web_url ?>modules/stock/add_stock.php" class="btn btn-info w-100 text-white">
                            <i class="bi bi-box-arrow-in-down"></i> Add Stock
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="<?= $web_url ?>reports/gst_report.php" class="btn btn-warning w-100">
                            <i class="bi bi-file-earmark-text"></i> GST Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Invoices -->
    <div class="col-md-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-receipt"></i> Recent Invoices</span>
                <a href="<?= $web_url ?>modules/invoice/list.php" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (count($recentInvoices) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Date</th>
                                <th>Party</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentInvoices as $invoice): ?>
                            <tr>
                                <td>
                                    <a href="<?= $web_url ?>modules/invoice/view.php?id=<?php echo $invoice['invoice_no']; ?>">
                                        <?php echo $invoice['invoice_no']; ?>
                                    </a>
                                </td>
                                <td><?php echo formatDate($invoice['invoice_date']); ?></td>
                                <td><?php echo $invoice['party_name']; ?></td>
                                <td class="text-end text-currency"><?php echo formatCurrency($invoice['total_amount']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center p-4 text-muted">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-2">No invoices yet</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Low Stock Alert -->
    <!-- <div class="col-md-5 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-triangle text-warning"></i> Low Stock Alert</span>
                <a href="<?= $web_url ?>reports/stock_report.php" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (count($lowStockProducts) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Size</th>
                                <th class="text-end">Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td><?php echo $product['product_name']; ?></td>
                                <td><span class="badge bg-secondary"><?php echo $product['size_name']; ?></span></td>
                                <td class="text-end">
                                    <span class="badge bg-<?php echo $product['quantity'] == 0 ? 'danger' : 'warning'; ?>">
                                        <?php echo $product['quantity']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center p-4 text-muted">
                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                    <p class="mt-2">All products are well stocked!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div> -->
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
