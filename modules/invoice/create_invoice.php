<?php
ob_start();
$pageTitle = 'Create Invoice - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

// Initialize cart and customer in session
if (!isset($_SESSION['invoice_cart'])) {
    $_SESSION['invoice_cart'] = [];
}

if (!isset($_SESSION['invoice_customer'])) {
    $_SESSION['invoice_customer'] = null;
}

// Handle customer lock
if (isset($_POST['lock_customer'])) {
    // $mobile = sanitize($_POST['mobile']);
    // $name = sanitize($_POST['party_name']);
    $partyId = intval($_POST['party_id'] ?? 0);
    $mobile = sanitize($_POST['mobile'] ?? '');
    $name = sanitize($_POST['party_name'] ?? '');
    $notes = sanitize($_POST['party_notes'] ?? '');
    
    // $_SESSION['invoice_customer'] = [
    //     'mobile' => $mobile,
    //     'name' => $name,
    //     'notes' => $notes
    // ];
    
    // setFlashMessage('success', 'Customer locked! Now add products.');
    
     if ($partyId > 0) {
        $partyStmt = $conn->prepare('SELECT id, party_name, mobile, notes FROM parties WHERE id = ?');
        $partyStmt->bind_param('i', $partyId);
        $partyStmt->execute();
        $party = $partyStmt->get_result()->fetch_assoc();
    } else {
        $party = null;
    }

    if ($party) {
        $_SESSION['invoice_customer'] = [
            'party_id' => (int) $party['id'],
            'mobile' => $party['mobile'],
            'name' => $party['party_name'],
            'notes' => $party['notes']
        ];
        setFlashMessage('success', 'Customer selected! Now add products.');
    } elseif ($name === '') {
        setFlashMessage('error', 'Please enter a customer name');
    } elseif ($mobile !== '' && !isValidMobile($mobile)) {
        setFlashMessage('error', 'Please enter a valid 10-digit mobile number or leave it blank');
    } else {
        if ($mobile !== '') {
            $duplicateStmt = $conn->prepare('SELECT id FROM parties WHERE mobile = ?');
            $duplicateStmt->bind_param('s', $mobile);
            $duplicateStmt->execute();
            $duplicateMobile = $duplicateStmt->get_result()->num_rows > 0;
        } else {
            $duplicateMobile = false;
        }

        if ($duplicateMobile) {
            setFlashMessage('error', 'That mobile number is already assigned to another customer');
        } else {
            $mobileValue = $mobile === '' ? null : $mobile;
            $insertStmt = $conn->prepare('INSERT INTO parties (mobile, party_name, notes) VALUES (?, ?, ?)');
            $insertStmt->bind_param('sss', $mobileValue, $name, $notes);
            $insertStmt->execute();
            $_SESSION['invoice_customer'] = [
                'party_id' => $conn->insert_id,
                'mobile' => $mobileValue,
                'name' => $name,
                'notes' => $notes
            ];
            setFlashMessage('success', 'New customer created! Now add products.');
        }
    }
    header('Location: create_invoice.php');
    exit();
}

// Handle change customer
if (isset($_GET['change_customer'])) {
    $_SESSION['invoice_customer'] = null;
    setFlashMessage('info', 'Customer unlocked. Search new customer.');
    header('Location: create_invoice.php');
    exit();
}

// Handle clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['invoice_cart'] = [];
    setFlashMessage('success', 'Cart cleared');
    header('Location: create_invoice.php');
    exit();
}

// Handle add to cart
if (isset($_POST['add_to_cart'])) {
    $productId = intval($_POST['product_id']);
    $sizeId = intval($_POST['size_id']);
    $quantity = intval($_POST['quantity']);
    // $discount = floatval($_POST['discount'] ?? 0);
    // $_SESSION['bill_discount'] = floatval($_POST['bill_discount'] ?? 0);
    $discountPercent = min(100, max(0, floatval($_POST['discount_percent'] ?? 0)));
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
            //$cartId = $productId . '_' . $sizeId;
            // $cartId = uniqid();
            $cartId = time() . rand(1000,9999);
            $_SESSION['invoice_cart'][$cartId] = [
                'product_id' => $productId,
                'size_id' => $sizeId,
                'product_code' => $item['product_code'],
                'product_name' => $item['product_name'],
                'size_name' => $item['size_name'],
                'quantity' => $quantity,
                'mrp' => $item['mrp'],
                'gst_rate' => $item['gst_rate'],
                'discount_percent' => $discountPercent //'discount' => $discount
            ];
            setFlashMessage('success', 'Product added! Add more or generate invoice.');
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
    
    $paymentMode = sanitize($_POST['payment_mode']);
    // $billDiscount = floatval($_POST['bill_discount'] ?? 0);
    $billDiscountPercent = min(100, max(0, floatval($_POST['bill_discount_percent'] ?? 0)));
    
    // Get customer from session
    $customer = $_SESSION['invoice_customer'];
    
    if (empty($customer) || empty($_SESSION['invoice_cart'])) {
        setFlashMessage('error', 'Please lock customer and add items to cart');
    } else {
        // $mobile = $customer['mobile'];
        // $partyName = $customer['name'];
        $partyId = intval($customer['party_id'] ?? 0);
        $notes = $customer['notes'];
        // $address='';
        $conn->begin_transaction();
        
        try {
            // Get or create party
            // $partySql = "SELECT id FROM parties WHERE mobile = ?";
            // Confirm the selected party still exists.
            $partySql = "SELECT id FROM parties WHERE id = ?";
            $partyStmt = $conn->prepare($partySql);
            // $partyStmt->bind_param('s', $mobile);
            $partyStmt->bind_param('i', $partyId);
            $partyStmt->execute();
            $partyResult = $partyStmt->get_result();
            
            // if ($partyResult->num_rows > 0) {
            //     $party = $partyResult->fetch_assoc();
            //     $partyId = $party['id'];
                
            //     // Update party details
            //     $updatePartySql = "UPDATE parties SET party_name = ?, address = ?, notes = ? WHERE id = ?";
            //     $updateStmt = $conn->prepare($updatePartySql);
            //     $updateStmt->bind_param('sssi', $partyName, $address, $notes, $partyId);
            //     $updateStmt->execute();
            // } else {
            //     // Insert new party
            //     $insertPartySql = "INSERT INTO parties (mobile, party_name, address, notes) VALUES (?, ?, ?, ?)";
            //     $insertStmt = $conn->prepare($insertPartySql);
            //     $insertStmt->bind_param('ssss', $mobile, $partyName, $address, $notes);
            //     $insertStmt->execute();
            //     $partyId = $conn->insert_id;
            // }
            if ($partyResult->num_rows === 0) {
                throw new Exception('Selected customer no longer exists');
            }
            
            // Calculate invoice totals
            $subtotal = 0;
            $totalDiscount = 0;
            $totalCGST = 0;
            $totalSGST = 0;
            $grandTotal = 0;
            
            // foreach ($_SESSION['invoice_cart'] as $item) {
            //     $itemTotal = $item['mrp'] * $item['quantity'];
            //     $itemDiscount = $item['discount'];
                
            //     $subtotal += $itemTotal;
            //     $totalDiscount += $itemDiscount;
            // }
            
            // // Apply bill-level discount
            // $totalDiscount += $billDiscount;
            // $taxableAmount = $subtotal - $totalDiscount;
            foreach ($_SESSION['invoice_cart'] as $item) {
                $subtotal += $item['mrp'] * $item['quantity'];
            }
            
            // Discounts are recorded once on the invoice header. Invoice item
            // amounts remain at their full value so the detail lines reconcile
            // to the subtotal before the invoice-level discount is applied.
            $totalDiscount = 0;
            
            // Calculate GST for each item
            foreach ($_SESSION['invoice_cart'] as &$item) {
                // $itemTotal = ($item['mrp'] * $item['quantity']) - $item['discount'];
                $itemTotal = $item['mrp'] * $item['quantity'];
                
                // Proportionate bill discount
                // if ($billDiscount > 0) {
                //     $proportionateDiscount = ($itemTotal / ($subtotal - $totalDiscount + $billDiscount)) * $billDiscount;
                //     $itemTotal -= $proportionateDiscount;
                //     $item['discount'] += $proportionateDiscount;
                // }
                
                // $item['bill_discount'] = 0;
                // if ($billDiscount > 0) {
                //     $proportionateDiscount = ($itemTotal / ($subtotal - $totalDiscount + $billDiscount)) * $billDiscount;
                //     $itemTotal -= $proportionateDiscount;
                //     // Store separately
                //     $item['bill_discount'] = $proportionateDiscount;
                // }
                
                // $item['bill_discount'] = 0;
                // if ($billDiscount > 0) {
                //     $proportionateDiscount = ($itemTotal / $subtotal) * $billDiscount;
                
                //     $itemTotal -= $proportionateDiscount;
                //     $item['bill_discount'] = $proportionateDiscount;
                // }
                
                $gstBreakdown = calculateGSTBreakdown($item['mrp'], $item['gst_rate']);
                $basePerUnit = $gstBreakdown['base_amount'];
                
                $itemBase = $basePerUnit * $item['quantity'];
                // $itemDiscounted = $itemTotal;
                $itemLevelDiscount = round($itemTotal * (floatval($item['discount_percent'] ?? 0) / 100), 2);
                $billDiscountShare = round(($itemTotal - $itemLevelDiscount) * ($billDiscountPercent / 100), 2);
                $itemDiscount = $itemLevelDiscount + $billDiscountShare;
                $totalDiscount += $itemDiscount;
                $discountedTotal = $itemTotal - $itemDiscount;
                $fullBase = $itemTotal / (1 + ($item['gst_rate'] / 100));
                
                $cgst = ($fullBase * ($item['gst_rate'] / 2)) / 100;
                $sgst = ($fullBase * ($item['gst_rate'] / 2)) / 100;
                
                $item['base_amount'] = $fullBase;
                $item['cgst'] = $cgst;
                $item['sgst'] = $sgst;
                $item['total'] = $itemTotal;
                $item['discount_amount'] = 0;
                
                $totalCGST += $cgst;
                $totalSGST += $sgst;
                $grandTotal += $discountedTotal;
            }
            // $grandTotal -= $totalDiscount;
            // Generate invoice number
            $invoiceNo = generateInvoiceNumber($conn);
            $invoiceDate = date('Y-m-d');
            $userId = getUserId();
            
            // Insert invoice
            $invoiceSql = "INSERT INTO invoices (invoice_no, invoice_date, party_id, subtotal, discount_amount, cgst_amount, sgst_amount, total_amount, payment_mode, notes, created_by) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $invoiceStmt = $conn->prepare($invoiceSql);
            $invoiceStmt->bind_param('ssidddddssi', $invoiceNo, $invoiceDate, $partyId, $subtotal, $totalDiscount, $totalCGST, $totalSGST, $grandTotal, $paymentMode, $notes, $userId);
            $invoiceStmt->execute();
            $invoiceId = $conn->insert_id;
            
            // Insert invoice items and update stock
           
           
            foreach ($_SESSION['invoice_cart'] as $cartId => $cartItem) {
                
                $itemSql = "INSERT INTO invoice_items (invoice_id, product_id, size_id, quantity, mrp, gst_rate, discount_amount, base_amount, cgst_amount, sgst_amount, total_amount) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $itemStmt = $conn->prepare($itemSql);
                $itemStmt->bind_param('iiiiddddddd', $invoiceId, $cartItem['product_id'], $cartItem['size_id'], $cartItem['quantity'], $cartItem['mrp'], $cartItem['gst_rate'], $cartItem['discount_amount'], $cartItem['base_amount'], $cartItem['cgst'], $cartItem['sgst'], $cartItem['total']);
                $itemStmt->execute();
                
                // Update stock
                updateStock($conn, $cartItem['product_id'], $cartItem['size_id'], $cartItem['quantity'], 'subtract');
                
                // Record transaction
                recordStockTransaction($conn, $cartItem['product_id'], $cartItem['size_id'], $cartItem['quantity'], 'OUT', 'SALE', $invoiceId, 'Invoice: ' . $invoiceNo);
            }
            
            $conn->commit();
            
            // Clear cart and customer session
            $_SESSION['invoice_cart'] = [];
            $_SESSION['invoice_customer'] = null;
            $_SESSION['bill_discount_percent'] = 0;
            
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

// foreach ($_SESSION['invoice_cart'] as $item) {
//     $itemTotal = $item['mrp'] * $item['quantity'];
//     $cartSummary['subtotal'] += $itemTotal;
//     $cartSummary['discount'] += floatval($item['discount']);
// }
foreach ($_SESSION['invoice_cart'] as $item) {
    $cartSummary['subtotal'] += $item['mrp'] * $item['quantity'];
    $itemGross = $item['mrp'] * $item['quantity'];
    $cartSummary['discount'] += round($itemGross * (floatval($item['discount_percent'] ?? 0) / 100), 2);
}

// $cartSummary['discount'] = floatval($_SESSION['bill_discount'] ?? 0);
$billDiscountPercent = min(100, max(0, floatval($_SESSION['bill_discount_percent'] ?? 0)));
$cartSummary['discount'] += round(($cartSummary['subtotal'] - $cartSummary['discount']) * ($billDiscountPercent / 100), 2);
$cartSummary['total'] = $cartSummary['subtotal'] - $cartSummary['discount'];
?>

<?php require_once __DIR__ . '/create_invoice_view.php'; 

// Handle clear cart
if (isset($_GET['clear_cart'])) {
    $_SESSION['invoice_cart'] = [];
    setFlashMessage('success', 'Cart cleared');
    header('Location: create_invoice.php');
    exit();
}
?>