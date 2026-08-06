<?php
$pageTitle = 'Stock Report - Payal Arban Stichis';
require_once __DIR__ . '/../includes/header.php';

$search = $_GET['search'] ?? '';

$sql = "SELECT p.product_code, p.product_name, p.mrp, s.size_name, ps.quantity
        FROM product_stock ps
        INNER JOIN products p ON ps.product_id = p.id
        INNER JOIN sizes s ON ps.size_id = s.id
        WHERE p.is_active = 1";

if (!empty($search)) {
    $sql .= " AND (p.product_code LIKE '%$search%' OR p.product_name LIKE '%$search%')";
}

$sql .= " ORDER BY p.product_code, s.sort_order";
$result = $conn->query($sql);

$totalQty = 0;
$totalValue = 0;
?>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-box-seam"></i> Stock Report</h2>
    </div>
</div>

<!-- Search -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search by code or name..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                    <div class="col-md-2">
                        <button type="button" onclick="window.print()" class="btn btn-success w-100">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Report -->
<div class="card">
    <div class="card-header">
        Current Stock as on <?php echo date('d-m-Y'); ?>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Size</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">MRP</th>
                        <th class="text-end">Stock Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): 
                        $stockValue = $row['quantity'] * $row['mrp'];
                        $totalQty += $row['quantity'];
                        $totalValue += $stockValue;
                    ?>
                    <tr class="<?php echo $row['quantity'] < 5 ? 'table-warning' : ''; ?>">
                        <td><?php echo $row['product_code']; ?></td>
                        <td><?php echo $row['product_name']; ?></td>
                        <td>
                            <span class="badge bg-secondary"><?php echo $row['size_name']; ?></span>
                        </td>
                        <td class="text-end">
                            <span class="badge bg-<?php echo $row['quantity'] > 0 ? 'success' : 'danger'; ?>">
                                <?php echo $row['quantity']; ?>
                            </span>
                        </td>
                        <td class="text-end"><?php echo formatCurrency($row['mrp']); ?></td>
                        <td class="text-end"><?php echo formatCurrency($stockValue); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot class="table-primary">
                    <tr>
                        <th colspan="3">TOTAL</th>
                        <th class="text-end"><?php echo $totalQty; ?> pcs</th>
                        <th></th>
                        <th class="text-end"><?php echo formatCurrency($totalValue); ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="alert alert-warning mt-3">
            <i class="bi bi-exclamation-triangle"></i> 
            <strong>Low Stock Alert:</strong> Items highlighted in yellow have less than 5 pieces in stock.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
