<?php
ob_start();
$pageTitle = 'Add Stock - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

// Get all active products
$productsResult = $conn->query("SELECT id, product_code, product_name FROM products WHERE is_active = 1 ORDER BY product_code ASC");

$selectedProduct = null;
$productSizes = [];

// Pre-select product if passed in URL
$preselectedProductId = intval($_GET['product'] ?? 0);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = intval($_POST['product_id']);
    $sizeId = intval($_POST['size_id']);
    $quantity = intval($_POST['quantity']);
    $notes = sanitize($_POST['notes'] ?? '');
    
    if ($productId > 0 && $sizeId > 0 && $quantity > 0) {
        $userId = getUserId();
        
        // Update stock
        $updateSql = "UPDATE product_stock SET quantity = quantity + ? WHERE product_id = ? AND size_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('iii', $quantity, $productId, $sizeId);
        
        if ($updateStmt->execute()) {
            // Record transaction
            $transSql = "INSERT INTO stock_transactions (product_id, size_id, quantity, transaction_type, reference_type, notes, created_by) 
                         VALUES (?, ?, ?, 'IN', 'PURCHASE', ?, ?)";
            $transStmt = $conn->prepare($transSql);
            $transStmt->bind_param('iiisi', $productId, $sizeId, $quantity, $notes, $userId);
            $transStmt->execute();
            
            setFlashMessage('success', "Successfully added $quantity pieces to stock");
            header('Location: add_stock.php?product=' . $productId);
            exit();
        } else {
            setFlashMessage('error', 'Error updating stock');
        }
    } else {
        setFlashMessage('error', 'Please fill all required fields');
    }
}

// Get product details if selected
if (isset($_GET['product'])) {
    $productId = intval($_GET['product']);
    
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $selectedProduct = $result->fetch_assoc();
    
    if ($selectedProduct) {
        // Get sizes with current stock
        $sizesSql = "SELECT ps.*, s.size_name FROM product_stock ps 
                     INNER JOIN sizes s ON ps.size_id = s.id 
                     WHERE ps.product_id = ? ORDER BY s.sort_order ASC";
        $sizesStmt = $conn->prepare($sizesSql);
        $sizesStmt->bind_param('i', $productId);
        $sizesStmt->execute();
        $sizesResult = $sizesStmt->get_result();
        
        while ($row = $sizesResult->fetch_assoc()) {
            $productSizes[] = $row;
        }
    }
}
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Add Stock</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-box-arrow-in-down"></i> Add Stock</h2>
    </div>
</div>

<form method="GET" action="" id="productForm">
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-search"></i> Select Product
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-10">
                            <label for="product" class="form-label">Product <span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="product" name="product" required onchange="this.form.submit()">
                                <option value="">-- Select Product --</option>
                                <?php 
                                $productsResult->data_seek(0);
                                while ($prod = $productsResult->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $prod['id']; ?>" 
                                        <?php echo ($selectedProduct && $selectedProduct['id'] == $prod['id']) ? 'selected' : ''; ?>
                                        <?php echo ($preselectedProductId == $prod['id']) ? 'selected' : ''; ?>>
                                    <?php echo $prod['product_code']; ?> - <?php echo $prod['product_name']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6><i class="bi bi-info-circle"></i> Instructions:</h6>
                    <ol class="mb-0 small">
                        <li>Select product first</li>
                        <li>Choose size</li>
                        <li>Enter quantity to add</li>
                        <li>Add notes (optional)</li>
                        <li>Click Add Stock</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if ($selectedProduct): ?>
<form method="POST" action="">
    <input type="hidden" name="product_id" value="<?php echo $selectedProduct['id']; ?>">
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-plus-circle"></i> Add Stock Entry
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>Product:</strong> <?php echo $selectedProduct['product_code']; ?> - <?php echo $selectedProduct['product_name']; ?><br>
                        <strong>MRP:</strong> <?php echo formatCurrency($selectedProduct['mrp']); ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="size_id" class="form-label">Size <span class="text-danger">*</span></label>
                            <select class="form-select" id="size_id" name="size_id" required>
                                <option value="">-- Select Size --</option>
                                <?php foreach ($productSizes as $size): ?>
                                <option value="<?php echo $size['size_id']; ?>">
                                    <?php echo $size['size_name']; ?> (Current: <?php echo $size['quantity']; ?> pcs)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="quantity" class="form-label">Quantity to Add <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   placeholder="0" min="1" required autofocus>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-plus-circle"></i> Add Stock
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" 
                                  placeholder="e.g., Purchase order PO-123, Supplier: XYZ"></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Current Stock Status -->
            <div class="card mt-3">
                <div class="card-header">
                    <i class="bi bi-box"></i> Current Stock Status
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Size</th>
                                    <th class="text-end">Current Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalStock = 0;
                                foreach ($productSizes as $size): 
                                    $totalStock += $size['quantity'];
                                ?>
                                <tr>
                                    <td><strong><?php echo $size['size_name']; ?></strong></td>
                                    <td class="text-end">
                                        <span class="badge bg-<?php echo $size['quantity'] > 0 ? 'success' : 'secondary'; ?> fs-6">
                                            <?php echo $size['quantity']; ?> pcs
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="table-primary">
                                    <td><strong>Total Stock:</strong></td>
                                    <td class="text-end"><strong><?php echo $totalStock; ?> pcs</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Recent Transactions -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Recent Stock Transactions
                </div>
                <div class="card-body">
                    <?php
                    // Get recent transactions for this product
                    $transSql = "SELECT st.*, s.size_name, u.full_name 
                                 FROM stock_transactions st
                                 INNER JOIN sizes s ON st.size_id = s.id
                                 LEFT JOIN users u ON st.created_by = u.id
                                 WHERE st.product_id = ? 
                                 ORDER BY st.created_at DESC LIMIT 10";
                    $transStmt = $conn->prepare($transSql);
                    $transStmt->bind_param('i', $selectedProduct['id']);
                    $transStmt->execute();
                    $transResult = $transStmt->get_result();
                    ?>
                    
                    <?php if ($transResult->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm small mb-0">
                            <tbody>
                                <?php while ($trans = $transResult->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $trans['size_name']; ?></strong><br>
                                        <small class="text-muted"><?php echo formatDateTime($trans['created_at']); ?></small>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge bg-<?php echo $trans['transaction_type'] == 'IN' ? 'success' : 'danger'; ?>">
                                            <?php echo $trans['transaction_type'] == 'IN' ? '+' : '-'; ?><?php echo $trans['quantity']; ?>
                                        </span><br>
                                        <small class="text-muted"><?php echo $trans['reference_type']; ?></small>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center mb-0">No transactions yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
