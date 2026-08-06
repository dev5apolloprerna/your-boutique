<?php
ob_start();
$pageTitle = 'Product Master - Payal Urban Stitch';
require_once __DIR__ . '/../../includes/header.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        setFlashMessage('success', 'Product deleted successfully');
    } else {
        setFlashMessage('error', 'Error deleting product');
    }
    header('Location: list.php');
    exit();
}

// Search and filter
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

$sql = "SELECT p.*, 
        (SELECT SUM(ps.quantity) FROM product_stock ps WHERE ps.product_id = p.id) as total_stock
        FROM products p 
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (p.product_code LIKE '%$search%' OR p.product_name LIKE '%$search%')";
}

if ($filter === 'active') {
    $sql .= " AND p.is_active = 1";
} elseif ($filter === 'inactive') {
    $sql .= " AND p.is_active = 0";
}

$sql .= " ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-box-seam"></i> Product Master</h2>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Product
            </a>
        </div>
    </div>
</div>

<!-- Search and Filter -->
<div class="row mb-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search by code or name..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="filter">
                            <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Products</option>
                            <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active Only</option>
                            <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Products Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                All Products (<?php echo $result->num_rows; ?>)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="100">Code</th>
                                <th>Product Name</th>
                                <th width="120" class="text-end">MRP</th>
                                <th width="80" class="text-center">GST</th>
                                <th width="100" class="text-center">Stock</th>
                                <th width="100">Status</th>
                                <th width="200" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $row['product_code']; ?></strong>
                                </td>
                                <td><?php echo $row['product_name']; ?></td>
                                <td class="text-end text-currency"><?php echo formatCurrency($row['mrp']); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?php echo $row['gst_rate']; ?>%</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?php echo $row['total_stock'] > 0 ? 'success' : 'danger'; ?>">
                                        <?php echo $row['total_stock'] ?? 0; ?> pcs
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info text-white" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="list.php?delete=<?php echo $row['id']; ?>" 
                                       class="btn btn-sm btn-danger btn-delete" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mt-2">No products found. Add your first product!</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
