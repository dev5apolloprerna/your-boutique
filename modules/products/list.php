<?php
ob_start();
$pageTitle = 'Product Master - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

// Get all products
$sql = "SELECT p.*, c.category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY p.product_number ASC";
$result = $conn->query($sql);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-box-seam"></i> Product Master</h2>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Product
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list"></i> All Products
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="80">Code</th>
                        <th>Product Name</th>
                        <th>Product Code</th>
                        <th>Category</th>
                        <th class="text-end" width="100">MRP</th>
                        <th class="text-center" width="60">GST</th>
                        <th width="100">Status</th>
                        <th width="120">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo $row['product_number']; ?></strong>
                        </td>
                       
                        <td>
                            <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>
                        </td>
                         <td>
                            <strong><?php echo $row['product_code']; ?></strong>
                        </td>
                        <td>
                            <?php if (!empty($row['category_name'])): ?>
                            <span class="badge bg-info"><?php echo $row['category_name']; ?></span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <strong>₹<?php echo number_format($row['mrp'], 2); ?></strong>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?php echo ($row['gst_rate'] == 5) ? 'success' : 'warning'; ?>">
                                <?php echo $row['gst_rate']; ?>%
                            </span>
                        </td>
                        <td>
                            <?php if ($row['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">
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
