<?php
ob_start();
$pageTitle = 'Print Stickers - Payal Urban Stitch';
require_once __DIR__ . '/../../includes/header.php';

// Get all active products
$productsResult = $conn->query("SELECT id, product_code, product_name FROM products WHERE is_active = 1 ORDER BY product_code ASC");

$selectedProduct = null;
$productSizes = [];
$stickers = [];

if (isset($_GET['product'])) {
    $productId = intval($_GET['product']);
    
    // Get product details WITH category
    $sql = "SELECT p.*, c.category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $selectedProduct = $result->fetch_assoc();
    
    if ($selectedProduct) {
        // Get sizes with stock
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

// Generate stickers if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $productId = intval($_POST['product_id']);
    
    // Get product details WITH category
    $sql = "SELECT p.*, c.category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if ($product) {
        // Get sizes and quantities
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'qty_') === 0) {
                $sizeId = intval(str_replace('qty_', '', $key));
                $qty = intval($value);
                
                if ($qty > 0) {
                    // Get size name
                    $sizeSql = "SELECT size_name FROM sizes WHERE id = ?";
                    $sizeStmt = $conn->prepare($sizeSql);
                    $sizeStmt->bind_param('i', $sizeId);
                    $sizeStmt->execute();
                    $sizeResult = $sizeStmt->get_result();
                    $size = $sizeResult->fetch_assoc();
                    
                    // Add stickers
                    for ($i = 0; $i < $qty; $i++) {
                        $stickers[] = [
                            'category' => $product['category_name'] ?? '',
                            'code' => $product['product_code'],
                            'size' => $size['size_name'],
                            'mrp' => $product['mrp']
                        ];
                    }
                }
            }
        }
    }
}
?>

<?php if (empty($stickers)): ?>
<!-- Sticker Configuration Form -->
<div class="row">
    <div class="col-12">
        <h2 class="mb-4"><i class="bi bi-printer"></i> Print Barcode Stickers</h2>
    </div>
</div>

<form method="GET" action="">
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-box-seam"></i> Select Product
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <label for="product" class="form-label">Product <span class="text-danger">*</span></label>
                            <select class="form-select" id="product" name="product" required onchange="this.form.submit()">
                                <option value="">-- Select Product --</option>
                                <?php while ($prod = $productsResult->fetch_assoc()): ?>
                                <option value="<?php echo $prod['id']; ?>" 
                                        <?php echo ($selectedProduct && $selectedProduct['id'] == $prod['id']) ? 'selected' : ''; ?>>
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
                    <h6><i class="bi bi-info-circle"></i> New Sticker Format:</h6>
                    <ul class="mb-0 small">
                        <li><strong>Category Name</strong></li>
                        <li>Product Code</li>
                        <li>Size</li>
                        <li>Barcode</li>
                        <li><strong style="font-size: 1.2em;">PRICE (Big & Bold)</strong></li>
                    </ul>
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
                    <i class="bi bi-rulers"></i> Select Quantity per Size
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        <strong>Product:</strong> <?php echo $selectedProduct['product_code']; ?> - <?php echo $selectedProduct['product_name']; ?><br>
                        <strong>Category:</strong> <?php echo $selectedProduct['category_name'] ?? 'N/A'; ?><br>
                        <strong>MRP:</strong> <?php echo formatCurrency($selectedProduct['mrp']); ?>
                    </p>
                    
                    <div class="row">
                        <?php foreach ($productSizes as $size): ?>
                        <div class="col-md-3 mb-3">
                            <label for="qty_<?php echo $size['size_id']; ?>" class="form-label">
                                <strong><?php echo $size['size_name']; ?></strong>
                                <small class="text-muted">(Stock: <?php echo $size['quantity']; ?>)</small>
                            </label>
                            <input type="number" class="form-control" id="qty_<?php echo $size['size_id']; ?>" 
                                   name="qty_<?php echo $size['size_id']; ?>" value="0" min="0">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <hr>
                    
                    <button type="submit" name="generate" class="btn btn-primary btn-lg">
                        <i class="bi bi-printer"></i> Generate Stickers
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>

<?php else: ?>
<!-- Print Stickers View -->
<div class="no-print mb-3">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer"></i> Print Stickers
    </button>
    <a href="sticker.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="print-area">
    <style>
        @media print {
            body { margin: 0;
 	--paper-width: 25mm;        /* Was 84mm */
        --paper-height: 37mm;       /* Was 25mm */
        --sticker-width: 84mm;      /* Swapped */
        --sticker-height: 37mm;     /* Swapped */
        --side-gap: 2mm;            /* Adjusted for new top/bottom gaps */
        --middle-gap: 4mm;
       }
            .sticker-container { page-break-after: always; }
        }
        
        .sticker-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 10px;
        }
        
        .sticker {
            width: 84mm;
            height: 37mm;
            border: 1px dashed #333;
            padding: 10px;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-inside: avoid;
            box-sizing: border-box;
            background: white;
        }
        
        .sticker-category {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            color: #333;
        }
        
        .sticker-code {
            font-size: 14.5px;
            font-weight: 600;
            font-family: 'Courier New', monospace;
            margin: 3px 0;
        }
        
        .sticker-size {
            font-size: 10px;
            font-weight: bold;
            margin: 3px 0;
            padding: 2px 8px;
            background: #f0f0f0;
            border-radius: 3px;
            display: inline-block;
        }
        
        .sticker-barcode {
            margin: 5px 0;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sticker-price {
            font-size: 24px;
            font-weight: 900;
            margin: 5px 0 0 0;
            color: #000;
            border-top: 2px solid #333;
            padding-top: 5px;
        }
    </style>
    
    <div class="sticker-grid">
        <?php foreach ($stickers as $sticker): ?>
        <div class="sticker">
            <?php if (!empty($sticker['category'])): ?>
            <p class="sticker-category"><?php echo strtoupper($sticker['category']); ?></p>
            <?php endif; ?>
            
            <p class="sticker-code"><?php echo $sticker['code']; ?></p>
            
            <p class="sticker-size"><?php echo $sticker['size']; ?></p>
            
            <div class="sticker-barcode">
                <!-- Simple barcode representation using SVG bars -->
                <svg width="120" height="28" xmlns="http://www.w3.org/2000/svg">
                    <?php
                    // Simple barcode pattern generator
                    $pattern = str_split(md5($sticker['code'] . $sticker['size']), 1);
                    $x = 0;
                    foreach ($pattern as $i => $char) {
                        if ($i >= 35) break;
                        $height = (hexdec($char) % 3 == 0) ? 35 : 28;
                        $width = (hexdec($char) % 2 == 0) ? 3 : 2;
                        echo "<rect x='$x' y='0' width='$width' height='$height' fill='black'/>";
                        $x += $width + 1;
                    }
                    ?>
                </svg>
            </div>
            
            <p class="sticker-price"><?php echo formatCurrency($sticker['mrp']); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="no-print mt-3">
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        <strong>Total Stickers Generated:</strong> <?php echo count($stickers); ?>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
