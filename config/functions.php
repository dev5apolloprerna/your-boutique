<?php
// Common Helper Functions

// Format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Format date
function formatDate($date) {
    return date('d-m-Y', strtotime($date));
}

// Format datetime
function formatDateTime($datetime) {
    return date('d-m-Y h:i A', strtotime($datetime));
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Generate next product code
function generateProductCode($conn) {
    $sql = "SELECT product_code FROM products ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastCode = $row['product_code'];
        $number = intval(substr($lastCode, 2)); // Remove 'PY' prefix
        $newNumber = $number + 1;
    } else {
        $newNumber = 1;
    }
    
    return 'PY' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
}

// Generate next invoice number
function generateInvoiceNumber($conn) {
    $sql = "SELECT invoice_no FROM invoices ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastInvoice = $row['invoice_no'];
        $number = intval(substr($lastInvoice, 3)); // Remove 'INV' prefix
        $newNumber = $number + 1;
    } else {
        $newNumber = 1;
    }
    
    return 'INV' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
}

// Generate next credit note number
function generateCreditNoteNumber($conn) {
    $sql = "SELECT credit_note_no FROM credit_notes ORDER BY id DESC LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastCN = $row['credit_note_no'];
        $number = intval(substr($lastCN, 2)); // Remove 'CN' prefix
        $newNumber = $number + 1;
    } else {
        $newNumber = 1;
    }
    
    return 'CN' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
}

// Calculate GST breakdown from MRP (price including GST)
function calculateGSTBreakdown($mrp, $gstRate) {
    // MRP includes GST
    // Base Amount = MRP / (1 + GST Rate/100)
    $baseAmount = $mrp / (1 + ($gstRate / 100));
    $gstAmount = $mrp - $baseAmount;
    
    // Split GST into CGST and SGST
    $cgst = $gstAmount / 2;
    $sgst = $gstAmount / 2;
    
    return [
        'base_amount' => round($baseAmount, 2),
        'gst_amount' => round($gstAmount, 2),
        'cgst' => round($cgst, 2),
        'sgst' => round($sgst, 2),
        'total' => round($mrp, 2)
    ];
}

// Determine GST rate based on MRP
function getGSTRate($mrp) {
    return ($mrp <= 2500) ? 5 : 18;
}

// Update product stock
function updateStock($conn, $productId, $sizeId, $quantity, $operation = 'add') {
    if ($operation === 'add') {
        $sql = "UPDATE product_stock SET quantity = quantity + ? WHERE product_id = ? AND size_id = ?";
    } else {
        $sql = "UPDATE product_stock SET quantity = quantity - ? WHERE product_id = ? AND size_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $quantity, $productId, $sizeId);
    return $stmt->execute();
}

// Record stock transaction
function recordStockTransaction($conn, $productId, $sizeId, $quantity, $type, $referenceType, $referenceId, $notes = null) {
    $userId = getUserId();
    $sql = "INSERT INTO stock_transactions (product_id, size_id, quantity, transaction_type, reference_type, reference_id, notes, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiissssi', $productId, $sizeId, $quantity, $type, $referenceType, $referenceId, $notes, $userId);
    return $stmt->execute();
}

// Check if mobile number exists
function getMobileExists($conn, $mobile) {
    $sql = "SELECT id FROM parties WHERE mobile = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $mobile);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Validate mobile number (Indian format)
function isValidMobile($mobile) {
    return preg_match('/^[6-9]\d{9}$/', $mobile);
}

// Get setting value
function getSetting($conn, $key, $default = '') {
    $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['setting_value'];
    }
    
    return $default;
}
?>
