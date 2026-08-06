<?php
ob_start();
$pageTitle = 'Create Invoice - Payal Arban Stichis';
require_once __DIR__ . '/../../includes/header.php';

// Initialize cart in session
if (!isset($_SESSION['invoice_cart'])) {
    $_SESSION['invoice_cart'] = [];
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $productId = intval($_POST['product_id']);
    $sizeId = intval($_POST['size_id']);
    $quantity = intval($_POST['quantity']);
    $discount = floatval($_POST['discount'] ?? 0);
    
    // Get product details
    $sql = "SELECT p.*, s.size_name FROM products p, sizes s 
            WHERE p.id = ? AND s.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $productId, $sizeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    
    if ($item) {
        // Check stock
        $stockSql = "SELECT quantity FROM product_stock WHERE product_id = ? AND size_id = ?";
        $stockStmt = $conn->prepare($stockSql);
        $stockStmt->bind_param('ii', $productId, $sizeId);
        $stockStmt->execute();
        $stockResult = $stockStmt->get_result();
        $stock = $stockResult->fetch_assoc();
        
        if ($stock && $stock['quantity'] >= $quantity) {
            $cartId = $productId . '_' . $sizeId;
            $_SESSION['invoice_cart'][$cartId] = [
                'product_id' => $productId,
                'size_id' => $sizeId,
                'product_code' => $item['product_code'],
                'product_name' => $item['product_name'],
                'size_name' => $item['size_name'],
                'quantity' => $quantity,
                'mrp' => $item['mrp'],
                'gst_rate' => $item['gst_rate'],
                'discount' => $discount
            ];
            setFlashMessage('success', 'Item added to cart');
        } else {
            setFlashMessage('error', 'Insufficient stock');
        }
    }
    
    header('Location: create_invoice.php');
    exit();
}

// Handle remove from cart
if (isset($_GET['remove'])) {
    $cartId = $_GET['remove'];
    unset($_SESSION['invoice_cart'][$cartId]);
    setFlashMessage('success', 'Item removed from cart');
    header('Location: create_invoice.php');
    exit();
}

// Handle invoice generation
if (isset($_POST['generate_invoice'])) {
    $mobile = sanitize($_POST['mobile']);
    $partyName = sanitize($_POST['party_name']);
    $address = sanitize($_POST['address'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    $paymentMode = sanitize($_POST['payment_mode']);
    $billDiscount = floatval($_POST['bill_discount'] ?? 0);
    
    if (empty($mobile) || empty($partyName) || empty($_SESSION['invoice_cart'])) {
        setFlashMessage('error', 'Please fill all required fields and add items to cart');
    } else {
        $conn->begin_transaction();
        
        try {
            // Get or create party
            $partySql = "SELECT id FROM parties WHERE mobile = ?";
            $partyStmt = $conn->prepare($partySql);
            $partyStmt->bind_param('s', $mobile);
            $partyStmt->execute();
            $partyResult = $partyStmt->get_result();
            
            if ($partyResult->num_rows > 0) {
                $party = $partyResult->fetch_assoc();
                $partyId = $party['id'];
                
                // Update party details
                $updatePartySql = "UPDATE parties SET party_name = ?, address = ?, notes = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updatePartySql);
                $updateStmt->bind_param('sssi', $partyName, $address, $notes, $partyId);
                $updateStmt->execute();
            } else {
                // Insert new party
                $insertPartySql = "INSERT INTO parties (mobile, party_name, address, notes) VALUES (?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertPartySql);
                $insertStmt->bind_param('ssss', $mobile, $partyName, $address, $notes);
                $insertStmt->execute();
                $partyId = $conn->insert_id;
            }
            
            // Calculate invoice totals
            $subtotal = 0;
            $totalDiscount = 0;
            $totalCGST = 0;
            $totalSGST = 0;
            $grandTotal = 0;
            
            foreach ($_SESSION['invoice_cart'] as $item) {
                $itemTotal = $item['mrp'] * $item['quantity'];
                $itemDiscount = $item['discount'];
                
                $subtotal += $itemTotal;
                $totalDiscount += $itemDiscount;
            }
            
            // Apply bill-level discount
            $totalDiscount += $billDiscount;
            $taxableAmount = $subtotal - $totalDiscount;
            
            // Calculate GST for each item
            foreach ($_SESSION['invoice_cart'] as &$item) {
                $itemTotal = ($item['mrp'] * $item['quantity']) - $item['discount'];
                
                // Proportionate bill discount
                if ($billDiscount > 0) {
                    $proportionateDiscount = ($itemTotal / ($subtotal - $totalDiscount + $billDiscount)) * $billDiscount;
                    $itemTotal -= $proportionateDiscount;
                    $item['discount'] += $proportionateDiscount;
                }
                
                $gstBreakdown = calculateGSTBreakdown($item['mrp'], $item['gst_rate']);
                $basePerUnit = $gstBreakdown['base_amount'];
                
                $itemBase = $basePerUnit * $item['quantity'];
                $itemDiscounted = $itemTotal;
                $discountedBase = $itemDiscounted / (1 + ($item['gst_rate'] / 100));
                
                $cgst = ($discountedBase * ($item['gst_rate'] / 2)) / 100;
                $sgst = ($discountedBase * ($item['gst_rate'] / 2)) / 100;
                
                $item['base_amount'] = $discountedBase;
                $item['cgst'] = $cgst;
                $item['sgst'] = $sgst;
                $item['total'] = $itemDiscounted;
                
                $totalCGST += $cgst;
                $totalSGST += $sgst;
                $grandTotal += $itemDiscounted;
            }
            
            // Generate invoice number
            $invoiceNo = generateInvoiceNumber($conn);
            $invoiceDate = date('Y-m-d');
            $userId = getUserId();
            
            // Insert invoice
            $invoiceSql = "INSERT INTO invoices (invoice_no, invoice_date, party_id, subtotal, discount_amount, cgst_amount, sgst_amount, total_amount, payment_mode, notes, created_by) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $invoiceStmt = $conn->prepare($invoiceSql);
            $invoiceStmt->bind_param('ssidd dddsi', $invoiceNo, $invoiceDate, $partyId, $subtotal, $totalDiscount, $totalCGST, $totalSGST, $grandTotal, $paymentMode, $notes, $userId);
            $invoiceStmt->execute();
            $invoiceId = $conn->insert_id;
            
            // Insert invoice items and update stock
            foreach ($_SESSION['invoice_cart'] as $item) {
                $itemSql = "INSERT INTO invoice_items (invoice_id, product_id, size_id, quantity, mrp, gst_rate, discount_amount, base_amount, cgst_amount, sgst_amount, total_amount) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $itemStmt = $conn->prepare($itemSql);
                $itemStmt->bind_param('iiiiddddddd', $invoiceId, $item['product_id'], $item['size_id'], $item['quantity'], $item['mrp'], $item['gst_rate'], $item['discount'], $item['base_amount'], $item['cgst'], $item['sgst'], $item['total']);
                $itemStmt->execute();
                
                // Update stock
                updateStock($conn, $item['product_id'], $item['size_id'], $item['quantity'], 'subtract');
                
                // Record transaction
                recordStockTransaction($conn, $item['product_id'], $item['size_id'], $item['quantity'], 'OUT', 'SALE', $invoiceId, 'Invoice: ' . $invoiceNo);
            }
            
            $conn->commit();
            
            // Clear cart
            $_SESSION['invoice_cart'] = [];
            
            setFlashMessage('success', 'Invoice generated successfully!');
            header('Location: view.php?invoice=' . $invoiceNo);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            setFlashMessage('error', 'Error generating invoice: ' . $e->getMessage());
        }
    }
}

// Calculate cart summary
$cartSummary = [
    'subtotal' => 0,
    'discount' => 0,
    'cgst' => 0,
    'sgst' => 0,
    'total' => 0
];

foreach ($_SESSION['invoice_cart'] as $item) {
    $itemTotal = $item['mrp'] * $item['quantity'];
    $cartSummary['subtotal'] += $itemTotal;
    $cartSummary['discount'] += $item['discount'];
}
?>


<?php
// Handle clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['invoice_cart'] = [];
    setFlashMessage('success', 'Cart cleared');
    header('Location: create_invoice.php');
    exit();
}

require_once __DIR__ . '/create_invoice_view.php';
?>