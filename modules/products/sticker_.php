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
                            'category' => substr($product['category_name'] ?? '', 0, 10), // $product['category_name'] ?? '',
                            'code' => $product['product_code'],
                            'product' => substr($product['product_name'] ?? '', 0, 10), // $product['product_name'],
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
    <button type="button" onclick="printStickerHardcoded()" class="btn btn-primary">
    <i class="bi bi-printer"></i> Print Stickers
</button>
    <a href="sticker.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<div class="print-area">
    <!--<style>-->
        
    <!--    @media print {-->
            
           
    <!--header,-->
    <!--footer,-->
    <!--nav,-->
    <!--.navbar,-->
    <!--.sidebar,-->
    <!--.topbar,-->
    <!--.main-header,-->
    <!--.main-footer,-->
    <!--.app-header,-->
    <!--.app-footer,-->
    <!--.no-print {-->
    <!--    display: none !important;-->
    <!--    visibility: hidden !important;-->
    <!--}-->
    <!--        body { margin: 0; }-->
    <!--        .no-print { display: none !important; }-->
    <!--        .sticker {-->
    <!--            page-break-inside: avoid;-->
    <!--        }-->
    <!--    }-->

    <!--    .sticker-grid {-->
    <!--        display: flex;-->
    <!--        flex-wrap: wrap;-->
    <!--        gap: 10px;-->
    <!--        padding: 10px;-->
    <!--    }-->

    <!--    .sticker {-->
    <!--        width: 37mm;-->
    <!--        height: 25mm;-->
    <!--        border: 1px dashed #333;-->
    <!--        padding: 8px;-->
    <!--        text-align: center;-->
    <!--        display: flex;-->
    <!--        flex-direction: column;-->
    <!--        justify-content: flex-start;-->
    <!--        page-break-inside: avoid;-->
    <!--        box-sizing: border-box;-->
    <!--        background: #fff;-->
    <!--        color: #000;-->
    <!--    }-->

    <!--    .sticker-top {-->
    <!--        display: flex;-->
    <!--        align-items: flex-start;-->
    <!--        justify-content: space-between;-->
    <!--        gap: 8px;-->
    <!--        width: 100%;-->
    <!--        margin-bottom: 4px;-->
    <!--    }-->

    <!--    .sticker-info {-->
    <!--        flex: 1;-->
    <!--        text-align: left;-->
    <!--        line-height: 1.1;-->
    <!--        overflow: hidden;-->
    <!--    }-->

    <!--    .sticker-category,-->
    <!--    .sticker-product {-->
    <!--        font-size: 8px;-->
    <!--        font-weight: 700;-->
    <!--        text-transform: uppercase;-->
    <!--        margin: 0;-->
    <!--        padding: 0;-->
    <!--        color: #000;-->
    <!--        line-height: 1.15;-->
    <!--        letter-spacing: 0.2px;-->
    <!--        white-space: nowrap;-->
    <!--    }-->

    <!--    .sticker-size {-->
    <!--        font-size: 14.5px;-->
    <!--        font-weight: 900;-->
    <!--        margin: 0;-->
    <!--        padding: 0;-->
    <!--        background: transparent;-->
    <!--        border-radius: 0;-->
    <!--        color: #000;-->
    <!--        line-height: 1;-->
    <!--        text-align: right;-->
    <!--        min-width: 42px;-->
    <!--        white-space: nowrap;-->
    <!--    }-->

    <!--    .sticker-barcode {-->
    <!--        margin: 4px 0 2px 0;-->
    <!--        height: 35px;-->
    <!--        display: flex;-->
    <!--        align-items: center;-->
    <!--        justify-content: center;-->
    <!--    }-->

    <!--    .sticker-code {-->
    <!--        font-size: 9.5px;-->
    <!--        font-weight: 700;-->
    <!--        font-family: Arial, sans-serif;-->
    <!--        margin: 1px 0 0 0;-->
    <!--        padding: 0;-->
    <!--        color: #000;-->
    <!--        line-height: 1;-->
    <!--        letter-spacing: 0.4px;-->
    <!--    }-->

    <!--    .sticker-price {-->
    <!--        font-size: 14.5px;-->
    <!--        font-weight: 900;-->
    <!--        margin: 4px 0 0 0;-->
    <!--        padding: 0;-->
    <!--        color: #000;-->
    <!--        border-top: none;-->
    <!--        line-height: 1.05;-->
    <!--    }-->
    <!--</style>-->
    
   <style>
    #stickerPrintSource {
        width: 84mm;
        margin: 0;
        padding: 0;
        background: #fff;
    }

    /* .sticker-page {
        width: 84mm;
        height: 25mm;
        display: grid;
        grid-template-columns: 37mm 37mm;
        column-gap: 4mm;
        padding-left: 3mm;
        padding-right: 3mm;
        padding-top: 0;
        padding-bottom: 0;
        box-sizing: border-box;
        margin: 0;
        overflow: hidden;
        background: #fff;
    } */ 

.sticker-page{
    width:84mm;
    height:25mm;

    display:grid;

    grid-template-columns:repeat(3, 1fr);

    column-gap:1mm;

    margin:0;
    padding:0;

    page-break-after:always;
    box-sizing:border-box;
}

    .sticker {
        width: 27.3mm;
        height: 25mm;
        border: 1px dashed #333;
        padding: 1.5mm;
        box-sizing: border-box;
        overflow: hidden;
        background: #fff;
        color: #000;
        text-align: center;
    }

    .empty-sticker {
        border: none;
    }

    .sticker-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
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
        line-height: 1.1;
        margin: 0;
        padding: 0;
        white-space: nowrap;
        text-transform: uppercase;
        color: #000;
        font-family: Arial, sans-serif;
    }

    .sticker-size {
        font-size: 12px;
        font-weight: 900;
        margin: 0;
        padding: 0;
        line-height: 1;
        color: #000;
        font-family: Arial, sans-serif;
    }

    .sticker-barcode {
        height: 7mm;
        margin: 0.5mm 0 0.2mm 0;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .sticker-barcode svg {
        width: 31mm;
        height: 7mm;
    }

    .sticker-code {
        font-size: 7px;
        font-weight: 700;
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        line-height: 1;
        color: #000;
    }

    .sticker-price {
        font-size: 12px;
        font-weight: 900;
        font-family: Arial, sans-serif;
        margin: 0.4mm 0 0 0;
        padding: 0;
        line-height: 1;
        color: #000;
    }

    @media print {
        @page {
            size: 84mm 25mm;
            margin: 0;
        }

        html,
        body {
            width: 84mm !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body * {
            visibility: hidden !important;
        }

        #stickerPrintSource,
        #stickerPrintSource * {
            visibility: visible !important;
        }

        #stickerPrintSource {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 84mm !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .sticker-page {
            width: 84mm !important;
            height: 25mm !important;
            display: grid !important;
            grid-template-columns: 37mm 37mm !important;
            column-gap: 4mm !important;
            padding-left: 3mm !important;
            padding-right: 3mm !important;
            margin: 0 !important;
            page-break-after: always !important;
            break-after: page !important;
        }

        .sticker-page:last-child {
            page-break-after: auto !important;
            break-after: auto !important;
        }

        .sticker {
            width: 37mm !important;
            height: 25mm !important;
        }

        .no-print,
        header,
        footer,
        nav,
        .navbar,
        .sidebar,
        .topbar,
        .main-header,
        .main-footer,
        .app-header,
        .app-footer {
            display: none !important;
            visibility: hidden !important;
        }
    }
</style>

<script>
function printStickerHardcoded() {
    const stickerSource = document.getElementById('stickerPrintSource');

    if (!stickerSource) {
        alert('Sticker print source not found.');
        return;
    }

    const stickerHtml = stickerSource.innerHTML;

    const printCss = `
        @page {
size: 80mm 25mm;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 84mm;
            margin: 0;
            padding: 0;
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-wrapper {
            width: 84mm;
            margin: 0;
            padding: 0;
            background: #fff;
        }

        .sticker-page {
    width: 84mm;
    height: 25mm;
    display: grid;
    grid-template-columns: repeat(3, 27.3mm);
    column-gap: 1mm;
    justify-content: center;
    margin: 0;
    padding: 0;
}


        .sticker-page:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        .sticker {
width: 38mm;
    height: 25mm;
            border: 1px dashed #333;
            padding: 1.5mm;
            box-sizing: border-box;
            overflow: hidden;
            background: #fff;
            color: #000;
            text-align: center;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .empty-sticker {
            border: none;
        }

        .sticker-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
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
            line-height: 1.1;
            margin: 0;
            padding: 0;
            white-space: nowrap;
            text-transform: uppercase;
            color: #000;
            font-family: Arial, sans-serif;
        }

        .sticker-size {
            font-size: 12px;
            font-weight: 900;
            margin: 0;
            padding: 0;
            line-height: 1;
            color: #000;
            font-family: Arial, sans-serif;
        }

        .sticker-barcode {
            height: 7mm;
            margin: 0.5mm 0 0.2mm 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .sticker-barcode svg {
            width: 31mm;
            height: 7mm;
        }

        .sticker-code {
            font-size: 7px;
            font-weight: 700;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1;
            color: #000;
        }

        .sticker-price {
            font-size: 12px;
            font-weight: 900;
            font-family: Arial, sans-serif;
            margin: 0.4mm 0 0 0;
            padding: 0;
            line-height: 1;
            color: #000;
        }
    `;

    const printWindow = window.open('', '_blank', 'width=900,height=600');

    printWindow.document.open();
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Print Stickers</title>
            <style>${printCss}</style>
        </head>
        <body>
            <div class="print-wrapper">
                ${stickerHtml}
            </div>

            <script>
                window.onload = function() {
                    setTimeout(function() {
                        window.focus();
                        window.print();
                    }, 500);
                };
            <\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}
</script>
    
    <div id="stickerPrintSource">
    <?php foreach (array_chunk($stickers, 3) as $row): ?>
        <div class="sticker-page">

            <?php foreach ($row as $sticker): ?>
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

            <?php for($i = count($row); $i < 3; $i++): ?>
                    <div class="sticker empty-sticker"></div>
            <?php endfor; ?>

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
