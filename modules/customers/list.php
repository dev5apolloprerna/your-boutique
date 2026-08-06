<?php
ob_start();
$pageTitle = 'Customer Master - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $sql = "DELETE FROM parties WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        setFlashMessage('success', 'Customer deleted successfully');
    } else {
        setFlashMessage('error', 'Error deleting customer');
    }
    header('Location: list.php');
    exit();
}

// Search
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM parties WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (mobile LIKE '%$search%' OR party_name LIKE '%$search%')";
}

$sql .= " ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people"></i> Customer Master</h2>
            <a href="add.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Customer
            </a>
        </div>
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
                               placeholder="Search by name or mobile..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
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

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                All Customers (<?php echo $result->num_rows; ?>)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="60">#</th>
                                <th>Name</th>
                                <th width="150">Mobile</th>
                                <th>Notes</th>
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
                                    <strong><?php echo htmlspecialchars($row['party_name']); ?></strong>
                                </td>
                                <td><?php echo $row['mobile']; ?></td>
                                <td><?php echo !empty($row['notes']) ? htmlspecialchars(substr($row['notes'], 0, 50)) . '...' : '-'; ?></td>
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
                                    No customers found. Add your first customer!
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
