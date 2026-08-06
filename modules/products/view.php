<?php
ob_start();
$pageTitle = 'View Product - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

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
$stockSql = "SELECT ps.*, s.size_name,s.size_code FROM product_stock ps 
             INNER JOIN sizes s ON ps.size_id = s.id 
             WHERE ps.product_id = ? ORDER BY s.sort_order ASC";
$stockStmt = $conn->prepare($stockSql);
$stockStmt->bind_param('i', $id);
$stockStmt->execute();
$stockResult = $stockStmt->get_result();

// Calculate GST breakdown
$gstBreakdown = calculateGSTBreakdown($product['mrp'], $product['gst_rate']);
?>

<div class="row">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="list.php">Product Master</a></li>
                <li class="breadcrumb-item active">View Product</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-eye"></i> Product Details</span>
                <div>
                    <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-sm btn-warning">
                        <i class="bi bi-pencil"></i> Edit
                    </a>
                    <a href="list.php" class="btn btn-sm btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td width="200"><strong>Product Code:</strong></td>
                        <td><span class="badge bg-primary fs-6"><?php echo $product['product_code']; ?></span></td>
                    </tr>
                    <tr>
                        <td><strong>Product Name:</strong></td>
                        <td><?php echo $product['product_name']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>MRP (Inc. GST):</strong></td>
                        <td class="text-currency fs-5"><?php echo formatCurrency($product['mrp']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>GST Rate:</strong></td>
                        <td><span class="badge bg-info"><?php echo $product['gst_rate']; ?>%</span></td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <?php if ($product['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Created:</strong></td>
                        <td><?php echo formatDateTime($product['created_at']); ?></td>
                    </tr>
                </table>
                
                <hr>
                
                <h6><i class="bi bi-calculator"></i> GST Breakdown:</h6>
                <table class="table table-sm">
                    <tr>
                        <td>Base Amount (Excluding GST):</td>
                        <td class="text-end"><?php echo formatCurrency($gstBreakdown['base_amount']); ?></td>
                    </tr>
                    <tr>
                        <td>CGST (<?php echo $product['gst_rate'] / 2; ?>%):</td>
                        <td class="text-end"><?php echo formatCurrency($gstBreakdown['cgst']); ?></td>
                    </tr>
                    <tr>
                        <td>SGST (<?php echo $product['gst_rate'] / 2; ?>%):</td>
                        <td class="text-end"><?php echo formatCurrency($gstBreakdown['sgst']); ?></td>
                    </tr>
                    <tr class="table-primary">
                        <td><strong>Total MRP:</strong></td>
                        <td class="text-end"><strong><?php echo formatCurrency($product['mrp']); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-box-seam"></i> Size-wise Stock
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Size</th>
                            <th>Code</th>
                            <th class="text-end">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalQty = 0;
                        while ($stock = $stockResult->fetch_assoc()): 
                            $totalQty += $stock['quantity'];
                        ?>
                        <tr>
                            <td><strong><?php echo $stock['size_name']; ?></strong></td>
                            <td><strong><?php echo $productCode = $stock['size_code'] . $product['product_code']; ?></strong></td>
                            <td class="text-end">
                                <span class="badge bg-<?php echo $stock['quantity'] > 0 ? 'success' : 'secondary'; ?>">
                                    <?php echo $stock['quantity']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <tr class="table-primary">
                            <td><strong>Total:</strong></td>
                            <td><strong></strong></td>
                            <td class="text-end"><strong><?php echo $totalQty; ?> pcs</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/modules/stock/add_stock.php?product=<?php echo $id; ?>" class="btn btn-success">
                        <i class="bi bi-plus-circle"></i> Add Stock
                    </a>
                    <a href="sticker.php?product=<?php echo $id; ?>" class="btn btn-info text-white">
                        <i class="bi bi-printer"></i> Print Stickers
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
