<?php
ob_start();
$pageTitle = 'Add Product - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

// Get all active categories
$categoriesResult = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, category_name ASC");
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

// Get all sizes
$sizesResult = $conn->query("SELECT id, size_name FROM sizes ORDER BY sort_order ASC");
$sizes = [];
while ($row = $sizesResult->fetch_assoc()) {
    $sizes[] = $row;
}

// Get next product number
$sql = "SELECT COALESCE(MAX(product_number), 0) + 1 AS next_number FROM products";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$nextProductNumber = $row['next_number'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = sanitize($_POST['product_name'] ?? '');
    $productNumber = intval($_POST['product_number'] ?? 0);
    $categoryId = intval($_POST['category_id'] ?? 0);
    $mrp = floatval($_POST['mrp'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($productName) || $productNumber <= 0 || $mrp <= 0) {
        setFlashMessage('error', 'Please fill all required fields');
    } else {
        // Check if product number already exists
        $checkSql = "SELECT id FROM products WHERE product_number = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('i', $productNumber);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

	if ($checkResult->num_rows > 0) {
            setFlashMessage('error', 'Product code ' . $productNumber . ' already exists!');
        } else {
	    $cat_rate_Sql = "SELECT gst_rate FROM categories WHERE id = ?";
	    $cat_rate_Stmt = $conn->prepare($cat_rate_Sql);
            $cat_rate_Stmt->bind_param('i', $categoryId);
            $cat_rate_Stmt->execute();
            $cat_rate_Result = $cat_rate_Stmt->get_result();
	    $cat_rate = $cat_rate_Result->fetch_assoc();
            $gstRate = $cat_rate_Result['gst_rate']; // getGSTRate($mrp);
            
            // Generate complete product_code barcode for default size A (XS)
         //   $prefixCode = '5001';
         //   $sizeCodeDefault = 'A';
            $productCode =  str_pad($productNumber, 5, '0', STR_PAD_LEFT);
            
            $sql = "INSERT INTO products (product_code, product_number, prefix_code, product_name, category_id, mrp, gst_rate, is_active) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sissiidi', $productCode, $productNumber, $prefixCode, $productName, $categoryId, $mrp, $gstRate, $isActive);
            
            if ($stmt->execute()) {
                $productId = $conn->insert_id;
                
                // Add stock records for all sizes
                foreach ($sizes as $size) {
                    $qty = intval($_POST['qty_' . $size['id']] ?? 0);
                    
                    // Insert stock
                    $insertStockSql = "INSERT INTO product_stock (product_id, size_id, quantity) VALUES (?, ?, ?)";
                    $insertStockStmt = $conn->prepare($insertStockSql);
                    $insertStockStmt->bind_param('iii', $productId, $size['id'], $qty);
                    $insertStockStmt->execute();
                    
                    // Record transaction if qty > 0
                    if ($qty > 0) {
                        recordStockTransaction($conn, $productId, $size['id'], $qty, 'IN', 'OPENING', $productId, 'Opening Stock');
                    }
                }
                
                setFlashMessage('success', 'Product added successfully! Code: ' . $productNumber);
                header('Location: list.php');
                exit();
            } else {
                setFlashMessage('error', 'Error adding product: ' . $conn->error);
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
                <li class="breadcrumb-item"><a href="list.php">Product Master</a></li>
                <li class="breadcrumb-item active">Add Product</li>
            </ol>
        </nav>
    </div>
</div>

<form method="POST" action="">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-plus-circle"></i> Add New Product
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="product_number" class="form-label">Product Code <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="product_number" name="product_number" 
                                   value="<?php echo $nextProductNumber; ?>" min="1" required>
                            <small class="text-muted">Auto: <?php echo $nextProductNumber; ?> (can edit)</small>
                        </div>
                        
                        <div class="col-md-8 mb-3">
                            <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="product_name" name="product_name" 
                                   placeholder="e.g., Blue Cotton Shirt" required autofocus>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="0">-- No Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>">
                                    <?php echo $cat['category_name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="mrp" class="form-label">MRP (Including GST) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" id="mrp" name="mrp" 
                                       placeholder="1500.00" step="0.01" min="0.01" required>
                            </div>
                        </div>
                    </div>

                    <!-- <div class="mb-3">
                        <label class="form-label">Current GST Rate</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="gst_display" 
                                   value="" readonly placeholder="Will auto-calculate">
                            <span class="input-group-text">%</span>
                        </div>
                        <small class="text-muted" id="gst_info">Will update based on MRP</small>
                    </div> -->
                    
                    <hr>
                    <h6 class="mb-3"><i class="bi bi-box"></i> Opening Stock (Quantity per Size)</h6>
                    
                    <div class="row">
                        <?php foreach ($sizes as $size): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <label for="qty_<?php echo $size['id']; ?>" class="form-label"><?php echo $size['size_name']; ?></label>
                            <input type="number" class="form-control" id="qty_<?php echo $size['id']; ?>" 
                                   name="qty_<?php echo $size['id']; ?>" value="0" min="0">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6><i class="bi bi-lightbulb"></i> Quick Guide:</h6>
                    <ul class="mb-3 small">
                        <li>Product Code: Required (auto-filled)</li>
                        <li>Product Name: Required</li>
                        <li>Category: Optional</li>
                        <li>MRP: Including GST</li>
                        <li>GST Rate: Auto-calculated</li>
                        <li>Stock: Enter qty per size</li>
                    </ul>
                    
                    <h6 class="mt-4"><i class="bi bi-info-circle"></i> GST Rules:</h6>
                    <ul class="mb-3 small">
                        <li>≤ ₹2500: 5% GST</li>
                        <li>&gt; ₹2500: 18% GST</li>
                    </ul>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add Product
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
                $('#gst_info').html('<span class="text-success">✓ 5% GST</span>');
            } else {
                $('#gst_info').html('<span class="text-info">✓ 18% GST</span>');
            }
        }
    });
});
</script>
