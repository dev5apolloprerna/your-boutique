<?php
ob_start();
$pageTitle = 'Size Master - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

// Get all sizes
$sql = "SELECT * FROM sizes ORDER BY sort_order ASC";
$result = $conn->query($sql);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-rulers"></i> Size Master</h2>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Size
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list"></i> All Sizes
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="100">Sort Order</th>
                        <th>Size Name</th>
                        <th width="120">Size Code</th>
                        <th width="200">Barcode Example</th>
                        <th width="100">Status</th>
                        <th width="120">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?php echo $row['sort_order']; ?></strong>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($row['size_name']); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-primary" style="font-size: 1.1em; padding: 8px 12px;">
                                <?php echo $row['size_code']; ?>
                            </span>
                        </td>
                        <td>
                            <code>5001<?php echo $row['size_code']; ?>000001</code>
                            <br>
                            <small class="text-muted">Search: <?php echo $row['size_code']; ?>000001</small>
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
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="alert alert-info mt-3">
    <i class="bi bi-info-circle"></i>
    <strong>Size Code Legend:</strong>
    A=XS, B=S, C=M, D=L, E=XL, F=XXL, G=XXXL
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
