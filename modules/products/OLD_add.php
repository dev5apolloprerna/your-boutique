<?php
ob_start();
$pageTitle = 'Add Product - Payal Urban Stitch';
require_once __DIR__ . '/../../includes/header.php';

// Generate next product code
$nextCode = generateProductCode($conn);

// Get all active sizes
$sizesResult = $conn->query("SELECT * FROM sizes WHERE is_active = 1 ORDER BY sort_order ASC");
$sizes = [];
while ($row = $sizesResult->fetch_assoc()) {
    $sizes[] = $row;
}

// Get all active categories
$categoriesResult = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, category_name ASC");
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productCode = sanitize($_POST['product_code'] ?? '');
    $productName = sanitize($_POST['product_name'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 0);
    $mrp = floatval($_POST['mrp'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($productName) || $mrp <= 0) {
        setFlashMessage('error', 'Please fill all required fields');
    } else {
        // Determine GST rate based on MRP
        $gstRate = getGSTRate($mrp);
        
        // Insert product
        $sql = "INSERT INTO products (product_code, product_name, category_id, mrp, gst_rate, is_active) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssiddi', $productCode, $productName, $categoryId, $mrp, $gstRate, $isActive);
        
        if ($stmt->execute()) {
            $productId = $conn->insert_id;
            $userId = getUserId();
            
            // Insert size-wise stock
            foreach ($sizes as $size) {
                $quantity = intval($_POST['qty_' . $size['id']] ?? 0);
                
                // Insert into product_stock
                $stockSql = "INSERT INTO product_stock (product_id, size_id, quantity) VALUES (?, ?, ?)";
                $stockStmt = $conn->prepare($stockSql);
                $stockStmt->bind_param('iii', $productId, $size['id'], $quantity);
                $stockStmt->execute();
                
                // Record stock transaction if quantity > 0
                if ($quantity > 0) {
                    $transSql = "INSERT INTO stock_transactions (product_id, size_id, quantity, transaction_type, reference_type, notes, created_by) 
                                 VALUES (?, ?, ?, 'IN', 'INITIAL', 'Initial stock', ?)";
                    $transStmt = $conn->prepare($transSql);
                    $transStmt->bind_param('iiii', $productId, $size['id'], $quantity, $userId);
                    $transStmt->execute();
                }
            }
            
            setFlashMessage('success', 'Product added successfully');
            header('Location: list.php');
            exit();
        } else {
            setFlashMessage('error', 'Error adding product');
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
                            <label for="product_code" class="form-label">Product Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="product_code" name="product_code" 
                                   value="<?php echo $nextCode; ?>" readonly>
                            <small class="text-muted">Auto-generated</small>
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
                                <option value="0">-- Select Category (Optional) --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo $cat['category_name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (count($categories) === 0): ?>
                            <small class="text-warning">No categories found. <a href="/modules/categories/add.php">Add categories first</a></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="mrp" class="form-label">MRP (Including GST) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control" id="mrp" name="mrp" 
                                       placeholder="0.00" step="0.01" min="0.01" required>
                            </div>
                            <small class="text-muted">GST will be calculated automatically</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">GST Rate (Auto)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="gst_display" readonly 
                                       placeholder="Enter MRP first">
                                <span class="input-group-text">%</span>
                            </div>
                            <small class="text-muted" id="gst_info">≤₹2500 = 5% | >₹2500 = 18%</small>
                        </div>
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
            
            <!-- Size-wise Stock -->
            <div class="card mt-3">
                <div class="card-header">
                    <i class="bi bi-rulers"></i> Size-wise Stock Entry
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($sizes as $size): ?>
                        <div class="col-md-3 mb-3">
                            <label for="qty_<?php echo $size['id']; ?>" class="form-label">
                                <strong><?php echo $size['size_name']; ?></strong>
                            </label>
                            <input type="number" class="form-control" id="qty_<?php echo $size['id']; ?>" 
                                   name="qty_<?php echo $size['id']; ?>" value="0" min="0">
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($sizes) === 0): ?>
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> No sizes available. 
                                <a href="/modules/sizes/add.php">Add sizes first</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6><i class="bi bi-info-circle"></i> Instructions:</h6>
                    <ul class="mb-0">
                        <li>Product code is auto-generated</li>
                        <li>MRP should include GST</li>
                        <li>GST rate is determined automatically:
                            <ul>
                                <li>≤₹2500 = 5% GST</li>
                                <li>>₹2500 = 18% GST</li>
                            </ul>
                        </li>
                        <li>Enter quantity for each size</li>
                        <li>Leave quantity as 0 if size not applicable</li>
                    </ul>
                    
                    <hr>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Product
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

<?php 
$extraJS = <<<'JS'
<script>
$(document).ready(function() {
    // Calculate GST rate based on MRP
    $('#mrp').on('input', function() {
        let mrp = parseFloat($(this).val());
        if (!isNaN(mrp) && mrp > 0) {
            let gstRate = (mrp <= 2500) ? 5 : 18;
            $('#gst_display').val(gstRate);
            
            if (mrp <= 2500) {
                $('#gst_info').html('<span class="text-success">5% GST (CGST 2.5% + SGST 2.5%)</span>');
            } else {
                $('#gst_info').html('<span class="text-info">18% GST (CGST 9% + SGST 9%)</span>');
            }
        } else {
            $('#gst_display').val('');
            $('#gst_info').text('≤₹2500 = 5% | >₹2500 = 18%');
        }
    });
});
</script>
JS;

require_once __DIR__ . '/../../includes/footer.php'; 
?>
