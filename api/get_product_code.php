<?php
session_start();
ob_start();
require_once '../config/database.php';
header('Content-Type: application/json');

$code = $_POST['code'] ?? '';

if (empty($code)) {
    echo json_encode(['found' => false]);
    exit();
}

$conn = getDBConnection();
$letter = substr($code, 0, 1);
$number = substr($code, 1);
$sql = "SELECT 
            p.*,
            ps.id AS stock_id,
            ps.size_id,
            ps.quantity,
            s.size_name,
            s.size_code
        FROM products p
        INNER JOIN product_stock ps
            ON p.id = ps.product_id
        INNER JOIN sizes s
            ON ps.size_id = s.id
        WHERE p.product_code = ?
          AND p.is_active = 1
          AND ps.quantity > 0
        ORDER BY s.sort_order ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $number, $letter);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $item = $result->fetch_assoc();
    $productId = $item['id'];
    $sizeId    = $item['size_id'];
    // $cartId = $productId . '_' . $sizeId;
    $cartId = time() . rand(1000,9999);
    $_SESSION['invoice_cart'][$cartId] = [
        'product_id'   => $productId,
        'size_id'      => $sizeId,
        'product_code' => $item['product_code'],
        'size_code' => $item['size_code'],
        'product_name' => $item['product_name'],
        'size_name'    => $item['size_name'],
        'quantity'     => 1,                   // Cart quantity
        'stock_qty'    => $item['quantity'],   // Available stock
        'mrp'          => $item['mrp'],
        'gst_rate'     => $item['gst_rate'],
	    'discount_percent' => 0
    ];
    echo json_encode([
    'found' => true,
    'message' => 'Product added to cart.'
]);

} else {
    echo json_encode(['found' => false]);
}

$conn->close();
?>
