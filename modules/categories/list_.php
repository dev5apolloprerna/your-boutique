<?php
ob_start();
$pageTitle = 'Category Master - Payal Urban Stitch';
require_once __DIR__ . '/../../includes/header.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        setFlashMessage('success', 'Category deleted successfully');
    } else {
        setFlashMessage('error', 'Error deleting category');
    }
    header('Location: list.php');
    exit();
}

// Get all categories
$sql = "SELECT * FROM categories ORDER BY sort_order ASC, category_name ASC";
$result = $conn->query($sql);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-tags"></i> Category Master</h2>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Category
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                All Categories
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>Category Name</th>
                                <th width="120">Sort Order</th>
                                <th width="100">Status</th>
                                <th width="150" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sr = 1;
                            while ($row = $result->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $sr++; ?></td>
                                <td>
                                    <strong><?php echo $row['category_name']; ?></strong>
                                </td>
                                <td><?php echo $row['sort_order']; ?></td>
                                <td>
                                    <?php if ($row['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="list.php?delete=<?php echo $row['id']; ?>" 
                                       class="btn btn-sm btn-danger btn-delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            
                            <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No categories found. Add your first category!
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> Information
            </div>
            <div class="card-body">
                <p><strong>Category Master</strong> helps organize products into different types.</p>
                <hr>
                <h6>Examples:</h6>
                <ul class="mb-0">
                    <li>Shirts</li>
                    <li>T-Shirts</li>
                    <li>Jeans</li>
                    <li>Trousers</li>
                    <li>Jackets</li>
                    <li>Sarees</li>
                    <li>Kurtas</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
