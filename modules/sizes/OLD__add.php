<?php
ob_start();
$pageTitle = 'Add Size - Payal Urban Stitch';
require_once __DIR__ . '/../../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sizeName = strtoupper(sanitize($_POST['size_name'] ?? ''));
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($sizeName)) {
        setFlashMessage('error', 'Size name is required');
    } else {
        // Check if size already exists
        $checkSql = "SELECT id FROM sizes WHERE size_name = ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param('s', $sizeName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            setFlashMessage('error', 'Size already exists');
        } else {
            $sql = "INSERT INTO sizes (size_name, sort_order, is_active) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sii', $sizeName, $sortOrder, $isActive);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Size added successfully');
                header('Location: list.php');
                exit();
            } else {
                setFlashMessage('error', 'Error adding size');
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
                <li class="breadcrumb-item active">Add Size</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Add New Size
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="size_name" class="form-label">Size Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control text-uppercase" id="size_name" name="size_name" 
                               placeholder="e.g., XL, XXL" required autofocus>
                        <small class="text-muted">Will be automatically converted to uppercase</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" 
                               value="0" min="0">
                        <small class="text-muted">Used to arrange sizes in order (0 = default)</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Size
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card bg-light">
            <div class="card-body">
                <h6><i class="bi bi-lightbulb"></i> Tips:</h6>
                <ul>
                    <li>Use standard size abbreviations (XS, S, M, L, XL, XXL, XXXL)</li>
                    <li>Sort order helps arrange sizes logically in dropdowns</li>
                    <li>Inactive sizes won't appear in product entry</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
