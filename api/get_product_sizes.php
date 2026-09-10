<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$code = trim($_POST['code'] ?? '');

if ($code === '') {
    echo json_encode([
        'found' => false,
        'message' => 'Please enter a product code.'
    ]);
    exit();
}

$conn = getDBConnection();

// Credit-note exchange entry needs product information only. Unlike the billing
// barcode endpoint, this endpoint must not add anything to the invoice cart.
$productSql = "SELECT id, product_code, product_name, mrp, gst_rate
               FROM products
               WHERE product_code = ? AND is_active = 1
               LIMIT 1";
$productStmt = $conn->prepare($productSql);
$productStmt->bind_param('s', $code);
$productStmt->execute();
$product = $productStmt->get_result()->fetch_assoc();

if (!$product) {
    echo json_encode([
        'found' => false,
        'message' => 'Active product not found.'
    ]);
    $conn->close();
    exit();
}

$sizesSql = "SELECT s.id, s.size_name AS name, s.size_code, ps.quantity
             FROM product_stock ps
             INNER JOIN sizes s ON s.id = ps.size_id
             WHERE ps.product_id = ?
               AND ps.quantity > 0
               AND s.is_active = 1
             ORDER BY s.sort_order ASC, s.size_name ASC";
$sizesStmt = $conn->prepare($sizesSql);
$sizesStmt->bind_param('i', $product['id']);
$sizesStmt->execute();
$sizesResult = $sizesStmt->get_result();

$sizes = [];
while ($size = $sizesResult->fetch_assoc()) {
    $sizes[] = [
        'id' => (int) $size['id'],
        'name' => $size['name'],
        'code' => $size['size_code'],
        'quantity' => (int) $size['quantity']
    ];
}

echo json_encode([
    'found' => true,
    'id' => (int) $product['id'],
    'code' => $product['product_code'],
    'name' => $product['product_name'],
    'mrp' => (float) $product['mrp'],
    'gst_rate' => (float) $product['gst_rate'],
    'sizes' => $sizes
]);

$conn->close();