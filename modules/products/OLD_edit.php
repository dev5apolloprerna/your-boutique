<?php
ob_start();
$pageTitle = 'Edit Product - Payal Urban Stitch';
require_once __DIR__ . '/../../includes/header.php';

// Get all active categories
$categoriesResult = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, category_name ASC");
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

$id = intval($_GET['id'] ?? 0);

// Get product details
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Product not found');
    header('Location: list.php');
    exit();
}

$product = $result->fetch_assoc();

// Get product stock
$stockSql = "SELECT ps.*, s.size_name FROM product_stock ps 
             INNER JOIN sizes s ON ps.size_id = s.id 
             WHERE ps.product_id = ? ORDER BY s.sort_order ASC";
$stockStmt = $conn->prepare($stockSql);
$stockStmt->bind_param('i', $id);
$stockStmt->execute();
$stockResult = $stockStmt->get_result();
$productStock = [];
while ($row = $stockResult->fetch_assoc()) {
    $productStock[$row['size_id']] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = sanitize($_POST['product_name'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $mrp = floatval($_POST['mrp'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($productName) || $mrp <= 0) {
        setFlashMessage('error', 'Please fill all required fields');
    } else {
        $gstRate = getGSTRate($mrp);
        
        $sql = "UPDATE products SET product_name = ?, category_id = ?, mrp = ?, gst_rate = ?, is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('siddii', $productName, $categoryId, $mrp, $gstRate, $isActive, $id);
        
        if ($stmt->execute()) {
            setFlashMessage('success', 'Product updated successfully');
            header('Location: list.php');
            exit();
        } else {
            setFlashMessage('error', 'Error updating product');
        }
    }
}
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="list.php">Product Master</a></li>
                <li class="breadcrumb-item active">Edit Product</li>
            </ol>
        </nav>
    </div>
</div>

<form method="POST" action="">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pencil"></i> Edit Product
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="product_code" class="form-label">Product Code</label>
                            <input type="text" class="form-control" id="product_code" 
                                   value="<?php echo $product['product_code']; ?>" readonly>
                        </div>
                        
                        <div class="col-md-8 mb-3">
                            <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="product_name" name="product_name" 
                                   value="<?php echo htmlspecialchars($product['product_name']); ?>" required autofocus>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="0">-- No Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo ($product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo $cat['category_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mrp" class="form-label">MRP (Including GST) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" id="mrp" name="mrp" 
                                       value="<?php echo $product['mrp']; ?>" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Current GST Rate</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="gst_display" 
                                       value="<?php echo $product['gst_rate']; ?>" readonly>
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted" id="gst_info">Will update based on new MRP</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Current Stock Display -->
            <div class="card mt-3">
                <div class="card-header">
                    <i class="bi bi-box"></i> Current Stock (Read-only)
                    <a href="../stock/add_stock.php?product=<?php echo $id; ?>" class="btn btn-sm btn-success float-end">
                        <i class="bi bi-plus-circle"></i> Add Stock
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Size</th>
                                    <th class="text-end">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productStock as $stock): ?>
                                <tr>
                                    <td><strong><?php echo $stock['size_name']; ?></strong></td>
                                    <td class="text-end">
                                        <span class="badge bg-<?php echo $stock['quantity'] > 0 ? 'success' : 'secondary'; ?>">
                                            <?php echo $stock['quantity']; ?> pcs
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-info-circle"></i> To modify stock quantities, use the <strong>Add Stock</strong> module.
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6><i class="bi bi-info-circle"></i> Note:</h6>
                    <ul class="mb-3">
                        <li>Product code cannot be changed</li>
                        <li>Category can be changed anytime</li>
                        <li>Stock quantities are managed separately</li>
                        <li>Changing MRP will recalculate GST rate</li>
                    </ul>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Product
                        </button>
                        <a href="list.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#mrp').on('input', function() {
        let mrp = parseFloat($(this).val());
        if (!isNaN(mrp) && mrp > 0) {
            let gstRate = (mrp <= 2500) ? 5 : 18;
            $('#gst_display').val(gstRate);
            
            if (mrp <= 2500) {
                $('#gst_info').html('<span class="text-success">Will be 5% GST</span>');
            } else {
                $('#gst_info').html('<span class="text-info">Will be 18% GST</span>');
            }
        }
    });
});
</script>