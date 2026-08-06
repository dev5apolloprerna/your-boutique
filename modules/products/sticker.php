<?php
ob_start();
error_reporting(0);
$pageTitle = 'Print Stickers - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

// Get all active products
$productsResult = $conn->query("SELECT id, product_code, product_name FROM products WHERE is_active = 1 ORDER BY product_code ASC");

$selectedProduct = null;
$productSizes = [];
$stickers = [];
$btPrintResult = null;

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
                    $sizeSql = "SELECT size_name,size_code FROM sizes WHERE id = ?";
                    $sizeStmt = $conn->prepare($sizeSql);
                    $sizeStmt->bind_param('i', $sizeId);
                    $sizeStmt->execute();
                    $sizeResult = $sizeStmt->get_result();
                    $size = $sizeResult->fetch_assoc();
                    
                    // Add stickers
                    for ($i = 0; $i < $qty; $i++) {
                        $stickers[] = [
                            'category' => substr($product['category_name'] ?? '', 0, 10), // $product['category_name'] ?? '',
                            'code' => $product['product_code'],
                            'product' => substr($product['product_name'] ?? '', 0, 10), // $product['product_name'],
                            'size' => $size['size_name'],
			    'size_code' => $size['size_code'],
                            'mrp' => $product['mrp']
                        ];
                    }

                }
            }
        }
    }

    // Send generated stickers to BarTender using the dynamic printer file.
    // Keep bartender_print_fixed.php in the same folder as this sticker.php file.
    if (!empty($stickers)) {
        require_once __DIR__ . '/bartender_print_fixed.php';

        $btPrintResult = bartender_print_stickers($stickers, [
            'debug'    => false,
            'keep_xml' => true,
        ]);
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
    <!--<button onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer"></i> Print Stickers
    </button>-->
    <a href="sticker.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php
if (!empty($btPrintResult) && function_exists('bartender_print_result_html')) {
    bartender_print_result_html($btPrintResult);
}
?>

<!--<div class="print-area">
    <style>
    :root {
        --paper-width: 84mm;
        --sticker-width: 37mm;
        --sticker-height: 25mm;
        --side-gap: 3mm;
        --middle-gap: 4mm;
    }

    .print-area {
        width: var(--paper-width);
        margin: 0;
        padding: 0;
        background: #fff;
    }

    .sticker-grid {
        width: var(--paper-width);
        display: grid;
        grid-template-columns: var(--sticker-width) var(--sticker-width);
        column-gap: var(--middle-gap);
        row-gap: 0;
        padding-left: var(--side-gap);
        padding-right: var(--side-gap);
        padding-top: 0;
        padding-bottom: 0;
        margin: 0;
        box-sizing: border-box;
    }

    .sticker {
        width: var(--sticker-width);
        height: var(--sticker-height);
        border: 1px dashed #333;
        padding: 1.5mm 1.5mm;
        text-align: center;
        box-sizing: border-box;
        background: #fff;
        color: #000;
        overflow: hidden;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .sticker-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1mm;
        width: 100%;
        margin-bottom: 0.5mm;
    }

    .sticker-info {
        flex: 1;
        text-align: left;
        line-height: 1;
        overflow: hidden;
    }

    .sticker-category,
    .sticker-product {
        font-size: 6px;
        font-weight: 700;
        text-transform: uppercase;
        margin: 0;
        padding: 0;
        color: #000;
        line-height: 1.1;
        white-space: nowrap;
    }

    .sticker-size {
        font-size: 12px;
        font-weight: 900;
        margin: 0;
        padding: 0;
        color: #000;
        line-height: 1;
        text-align: right;
        min-width: 9mm;
        white-space: nowrap;
    }

    .sticker-barcode {
        margin: 0.5mm 0 0.2mm 0;
        height: 7mm;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .sticker-barcode svg {
        width: 31mm;
        height: 7mm;
        max-width: 100%;
    }

    .sticker-code {
        font-size: 7px;
        font-weight: 700;
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        color: #000;
        line-height: 1;
        letter-spacing: 0.3px;
    }

    .sticker-price {
        font-size: 12px;
        font-weight: 900;
        margin: 0.4mm 0 0 0;
        padding: 0;
        color: #000;
        line-height: 1;
    }

    @page {
        size: 84mm 25mm;
        margin: 0;
    }

    @media print {
        html,
        body {
            width: 84mm !important;
            height: 25mm !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body * {
            visibility: hidden !important;
        }

        .print-area,
        .print-area * {
            visibility: visible !important;
        }

        .print-area {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 84mm !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .sticker-grid {
            width: 84mm !important;
            display: grid !important;
            grid-template-columns: 37mm 37mm !important;
            column-gap: 6mm !important;
            row-gap: 0 !important;
            padding-left: 2mm !important;
            padding-right: 2mm !important;
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        .sticker {
            width: 37mm !important;
            height: 25mm !important;
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .sticker:nth-child(2n) {
            page-break-after: always;
            break-after: page;
        }

        header,
        footer,
        nav,
        .navbar,
        .sidebar,
        .topbar,
        .main-header,
        .main-footer,
        .app-header,
        .app-footer,
        .no-print {
            display: none !important;
            visibility: hidden !important;
        }
    }
</style>
    
    <div class="sticker-grid">
        <?php foreach ($stickers as $sticker): ?>
        <div class="sticker">
            <div class="sticker-top">
                <div class="sticker-info">
                    <?php if (!empty($sticker['category'])): ?>
                    <p class="sticker-category"><?php echo strtoupper($sticker['category']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($sticker['product'])): ?>
                    <p class="sticker-product"><?php echo strtoupper($sticker['product']); ?></p>
                    <?php endif; ?>
                </div>
            <p class="sticker-size"><?php echo $sticker['size']; ?></p>
            </div>
            
            
                
            
            <div class="sticker-barcode">
                
                <svg width="120" height="28" xmlns="http://www.w3.org/2000/svg">
                    <?php
                
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
            <p class="sticker-code"><?php echo $sticker['code']; ?></p>
            
            <p class="sticker-price"><?php echo formatCurrency($sticker['mrp']); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>-->

<div class="no-print mt-3">
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        <strong>Total Stickers Generated:</strong> <?php echo count($stickers); ?>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
