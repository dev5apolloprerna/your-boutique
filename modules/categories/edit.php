<?php
ob_start();
$pageTitle = 'Edit Category - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

$id = intval($_GET['id'] ?? 0);

// Get category details
$sql = "SELECT * FROM categories WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Category not found');
    header('location: list.php');
    exit();
}

$category = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = sanitize($_POST['category_name'] ?? '');
    $hsnCode = sanitize($_POST['hsn_code'] ?? '');
    $sortOrder = intval($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $gst_rate = floatval($_POST['gst_rate'] ?? 0);
    $isSplit = intval($_POST['is_split'] ?? 0);
    
    if (empty($categoryName)) {
        setFlashMessage('error', 'Category name is required');
    } else {
        // Check if category name already exists (excluding current)
        $checkSql = "SELECT id FROM categories WHERE category_name = ? AND id != ?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param('si', $categoryName, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            setFlashMessage('error', 'Category already exists');
        } else {
            $sql = "UPDATE categories SET category_name = ?, hsn_code = ?, gst_rate=?, is_split=?, sort_order = ?, is_active = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssdiiii', $categoryName, $hsnCode, $gst_rate, $isSplit, $sortOrder, $isActive, $id);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Category updated successfully');
                header('location: list.php');
                exit();
            } else {
                setFlashMessage('error', 'Error updating category');
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
                <li class="breadcrumb-item active">Edit Category</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil"></i> Edit Category
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name" 
                               value="<?php echo htmlspecialchars($category['category_name']); ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="hsn_code" class="form-label">HSN Code</label>
                        <input type="text" class="form-control text-uppercase" id="hsn_code" name="hsn_code"
                               value="<?php echo htmlspecialchars($category['hsn_code'] ?? ''); ?>"
                               placeholder="e.g., 6205" maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GST (%)</label>
                        <input type="number" step="0.01" name="gst_rate" class="form-control" value="<?php echo $category['gst_rate']; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Split GST</label>
                        <select name="is_split" class="form-select">
                            <option value="0" <?= $category['is_split']==0?'selected':'' ?>>No</option>
                            <option value="1" <?= $category['is_split']==1?'selected':'' ?>>Yes</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" 
                               value="<?php echo $category['sort_order']; ?>" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo $category['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Category
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
