<?php
ob_start();
$pageTitle = 'Add Category - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = sanitize($_POST['category_name'] ?? '');
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $gst_rate = floatval($_POST['gst_rate'] ?? 0);
    $isSplit = intval($_POST['is_split'] ?? 0);

    
    if (empty($categoryName)) {
        setFlashMessage('error', 'Category name is required');
    } else {
        // Check if category already exists
        $checkSql = "SELECT id FROM categories WHERE category_name = ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param('s', $categoryName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            setFlashMessage('error', 'Category already exists');
        } else {
            $sql = "INSERT INTO categories (category_name, gst_rate, is_split, sort_order, is_active) VALUES (?, ?, ?, ? ,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sdiii', $categoryName, $gst_rate, $isSplit, $sortOrder, $isActive);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Category added successfully');
                header('Location: list.php');
                exit();
            } else {
                setFlashMessage('error', 'Error adding category');
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
                <li class="breadcrumb-item"><a href="list.php">Category Master</a></li>
                <li class="breadcrumb-item active">Add Category</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Add New Category
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name" 
                               placeholder="e.g., Shirts, Jeans, T-Shirts" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GST (%)</label>
                        <input type="number" step="0.01" name="gst_rate" class="form-control" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Split GST</label>
                        <select name="is_split" class="form-select">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" 
                               value="0" min="0">
                        <small class="text-muted">Used to arrange categories in order (0 = default)</small>
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
                            <i class="bi bi-save"></i> Save Category
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
                    <li>Use clear category names (Shirts, Jeans, etc.)</li>
                    <li>Sort order helps arrange categories logically</li>
                    <li>Inactive categories won't appear in product entry</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
