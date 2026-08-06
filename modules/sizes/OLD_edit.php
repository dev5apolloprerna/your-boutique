<?php
ob_start();
$pageTitle = 'Edit Size - Payal Urban Stitch';
require_once __DIR__ . '/../../includes/header.php';

$id = intval($_GET['id'] ?? 0);

// Get size details
$sql = "SELECT * FROM sizes WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Size not found');
    header('Location: list.php');
    exit();
}

$size = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sizeName = strtoupper(sanitize($_POST['size_name'] ?? ''));
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($sizeName)) {
        setFlashMessage('error', 'Size name is required');
    } else {
        // Check if size name already exists (excluding current)
        $checkSql = "SELECT id FROM sizes WHERE size_name = ? AND id != ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param('si', $sizeName, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            setFlashMessage('error', 'Size already exists');
        } else {
            $sql = "UPDATE sizes SET size_name = ?, sort_order = ?, is_active = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('siii', $sizeName, $sortOrder, $isActive, $id);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Size updated successfully');
                header('Location: list.php');
                exit();
            } else {
                setFlashMessage('error', 'Error updating size');
            }
        }
    }
}
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="list.php">Size Master</a></li>
                <li class="breadcrumb-item active">Edit Size</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil"></i> Edit Size
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="size_name" class="form-label">Size Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="size_name" name="size_name" 
                               value="<?php echo $size['size_name']; ?>" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" 
                               value="<?php echo $size['sort_order']; ?>" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo $size['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Size
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
