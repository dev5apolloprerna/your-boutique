<?php
ob_start();
$pageTitle = 'Size Master - Payal Urban Stitch';
require_once __DIR__ . '/../../includes/header.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM sizes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        setFlashMessage('success', 'Size deleted successfully');
    } else {
        setFlashMessage('error', 'Error deleting size');
    }
    header('Location: list.php');
    exit();
}

// Get all sizes
$sql = "SELECT * FROM sizes ORDER BY sort_order ASC, size_name ASC";
$result = $conn->query($sql);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-rulers"></i> Size Master</h2>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Size
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                All Sizes
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>Size Name</th>
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
                                    <strong><?php echo $row['size_name']; ?></strong>
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
                                    No sizes found. Add your first size!
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
                <p><strong>Size Master</strong> is used to manage all available clothing sizes in your inventory.</p>
                <hr>
                <h6>Common Sizes:</h6>
                <ul class="mb-0">
                    <li>XS - Extra Small</li>
                    <li>S - Small</li>
                    <li>M - Medium</li>
                    <li>L - Large</li>
                    <li>XL - Extra Large</li>
                    <li>XXL - Double Extra Large</li>
                    <li>XXXL - Triple Extra Large</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
