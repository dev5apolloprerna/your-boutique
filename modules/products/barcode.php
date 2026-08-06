<?php
ob_start();
// Set headers to prevent layout caching issues
header("Cache-Control: no-cache, must-revalidate");
require_once __DIR__ . '/../../includes/header.php'; // Ensure database connection ($conn) is loaded

// 1. Get Product ID and Size ID from URL query string
$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$sizeId = isset($_GET['size_id']) ? intval($_GET['size_id']) : 0;
$printQty = isset($_GET['qty']) ? intval($_GET['qty']) : 1; // Number of sticker copies to print

// 60-07-2026 Emergency Safeguard fallback values if IDs are missing
if ($productId === 0) {
    $productCode = "300F030009";
    $productName = "A LINE SET KALI";
    $sizeName = "XXL";
    $mrp = 3550.00;
} else {
    // 2. Fetch accurate data from your database tables
    $sql = "SELECT p.*, s.size_name FROM products p 
            INNER JOIN sizes s ON s.id = ? 
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $sizeId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();

    if (!$product) {
        die("Product or Size records not found in database.");
    }

    $productCode = $product['product_code'];
    $productName = strtoupper($product['product_name']);
    $sizeName = strtoupper($product['size_name']);
    $mrp = floatval($product['mrp']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Label - <?php echo $productCode; ?></title>
    <style>
        /* Exact physical translation properties for TSC TE244 38mm x 25mm label footprint */
        @page {
            size: 38mm 25mm;
            margin: 0;
        }
        body {
            width: 38mm;
            height: 25mm;
            margin: 0;
            padding: 1mm 2mm;
            box-sizing: border-box;
            font-family: "Arial", sans-serif;
            color: #000000;
            background-color: #ffffff;
            overflow: hidden;
            -webkit-print-color-adjust: exact;
        }
        .label-container {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-after: always;
        }
        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            width: 100%;
        }
        .product-info {
            font-size: 8pt;
            font-weight: bold;
            line-height: 1.1;
            max-width: 70%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .size-badge {
            font-size: 11pt;
            font-weight: 900;
            text-align: right;
            line-height: 1;
        }
        .barcode-section {
            text-align: center;
            margin: 0.5mm 0;
        }
        /* Pure CSS barcode representation technique matching your photo footprint */
        .barcode-visual {
            font-family: 'Libre Barcode 128', 'IDAutomationHC39M', monospace;
            font-size: 22pt;
            line-height: 1;
            margin: 0;
            padding: 0;
            letter-spacing: 0.5px;
        }
        .barcode-text {
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-top: -1px;
        }
        .price-section {
            font-size: 12pt;
            font-weight: bold;
            text-align: center;
            line-height: 1;
            margin-bottom: 0.5mm;
        }
        /* Force immediate windows print spooling mode */
        @media print {
            .no-print { display: none; }
        }
    </style>
    <!-- Native loading of standard web font standard to display dynamic scanning shapes -->
    <link href="https://googleapis.com" rel="stylesheet">
</head>
<body>

    <!-- Loop prints matching quantity requested -->
    <?php for ($i = 0; $i < $printQty; $i++): ?>
    <div class="label-container">
        
        <div class="header-row">
            <div class="product-info">
                <?php echo htmlspecialchars($productName); ?><br>
                KALI
            </div>
            <div class="size-badge">
                <?php echo htmlspecialchars($sizeName); ?>
            </div>
        </div>

        <div class="barcode-section">
            <!-- Renders standard alphanumeric codes into high contrast bars -->
            <div class="barcode-visual"><?php echo htmlspecialchars($productCode); ?></div>
            <div class="barcode-text"><?php echo htmlspecialchars($productCode); ?></div>
        </div>

        <div class="price-section">
            RS: <?php echo number_format($mrp, 2, '.', ''); ?>
        </div>

    </div>
    <?php endfor; ?>

    <script>
        // Triggers the physical printer hardware immediately upon page layout readiness
        window.onload = function() {
            window.print();
            // Automatically close tab window after print or cancel actions complete
            setTimeout(function() { window.close(); }, 500);
        }
    </script>
</body>
</html>
