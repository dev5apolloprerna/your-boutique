<?php
ob_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$response = ['found' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sizeCode = sanitize($_POST['size_code'] ?? '');
    $productNumber = intval($_POST['product_number'] ?? 0);
    
    if (!empty($sizeCode) && $productNumber > 0) {
        // Get size_id by size_code
        $sizeSql = "SELECT id, size_name FROM sizes WHERE size_code = ?";
        $sizeStmt = $conn->prepare($sizeSql);
        $sizeStmt->bind_param('s', $sizeCode);
        $sizeStmt->execute();
        $sizeResult = $sizeStmt->get_result();
        $size = $sizeResult->fetch_assoc();
        
        if ($size) {
            // Get product by product_number
            $prodSql = "SELECT p.id, p.product_code, p.product_name, p.mrp, p.gst_rate, c.category_name
                       FROM products p
                       LEFT JOIN categories c ON p.category_id = c.id
                       WHERE p.product_number = ?";
            $prodStmt = $conn->prepare($prodSql);
            $prodStmt->bind_param('i', $productNumber);
            $prodStmt->execute();
            $prodResult = $prodStmt->get_result();
            $product = $prodResult->fetch_assoc();
            
            if ($product) {
                // Get stock
                $stockSql = "SELECT quantity FROM product_stock WHERE product_id = ? AND size_id = ?";
                $stockStmt = $conn->prepare($stockSql);
                $stockStmt->bind_param('ii', $product['id'], $size['id']);
                $stockStmt->execute();
                $stockResult = $stockStmt->get_result();
                $stock = $stockResult->fetch_assoc();
                
                $response = [
                    'found' => true,
                    'product_id' => $product['id'],
                    'size_id' => $size['id'],
                    'product_code' => $product['product_code'],
                    'product_name' => $product['product_name'],
                    'category_name' => $product['category_name'] ?? 'N/A',
                    'size_name' => $size['size_name'],
                    'mrp' => $product['mrp'],
                    'gst_rate' => $product['gst_rate'],
                    'stock' => $stock['quantity'] ?? 0
                ];
            }
        }
    }
}

echo json_encode($response);
?>
