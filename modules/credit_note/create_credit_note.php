<?php
ob_start();
$pageTitle = 'Create Credit Note - Your-boutique';
require_once __DIR__ . '/../../includes/header.php';

// Initialize credit note session
if (!isset($_SESSION['credit_note_items'])) {
    $_SESSION['credit_note_items'] = [];
}

$selectedInvoice = null;
$invoiceItems = [];

// Load invoice if selected
if (isset($_GET['invoice']) || isset($_POST['invoice_no'])) {
    $invoiceNo = $_GET['invoice'] ?? $_POST['invoice_no'];
    
    // Get invoice details
    $sql = "SELECT i.*, p.party_name, p.mobile, p.address 
            FROM invoices i
            INNER JOIN parties p ON i.party_id = p.id
            WHERE i.invoice_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $invoiceNo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $selectedInvoice = $result->fetch_assoc();
        
        // Check if invoice is within 30 days
        $invoiceDate = strtotime($selectedInvoice['invoice_date']);
        $currentDate = strtotime(date('Y-m-d'));
        $daysDiff = floor(($currentDate - $invoiceDate) / (60 * 60 * 24));
        
        // if ($daysDiff > 30) {
        //     setFlashMessage('warning', 'This invoice is older than 30 days. Returns may not be accepted.');
        // }
        
        // Get invoice items
        $itemsSql = "SELECT ii.*, p.product_code, p.product_name, s.size_name,
                     (SELECT COALESCE(SUM(cni.quantity), 0) FROM credit_note_items cni 
                      INNER JOIN credit_notes cn ON cni.credit_note_id = cn.id 
                      WHERE cni.invoice_item_id = ii.id) as returned_qty
                     FROM invoice_items ii
                     INNER JOIN products p ON ii.product_id = p.id
                     INNER JOIN sizes s ON ii.size_id = s.id
                     WHERE ii.invoice_id = ?";
        $itemsStmt = $conn->prepare($itemsSql);
        $itemsStmt->bind_param('i', $selectedInvoice['id']);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();
        
        while ($row = $itemsResult->fetch_assoc()) {
            $row['available_qty'] = $row['quantity'] - $row['returned_qty'];
            $invoiceItems[] = $row;
        }
    }
}

// Handle add return item
if (isset($_POST['add_return_item'])) {
    $invoiceItemId = intval($_POST['invoice_item_id']);
    $returnQty = intval($_POST['return_quantity']);
    
    // Find the invoice item
    foreach ($invoiceItems as $item) {
        if ($item['id'] == $invoiceItemId && $returnQty > 0 && $returnQty <= $item['available_qty']) {
            $returnId = 'return_' . $invoiceItemId;
            $_SESSION['credit_note_items'][$returnId] = [
                'invoice_item_id' => $invoiceItemId,
                'product_id' => $item['product_id'],
                'size_id' => $item['size_id'],
                'product_code' => $item['product_code'],
                'product_name' => $item['product_name'],
                'size_name' => $item['size_name'],
                'quantity' => $returnQty,
                'mrp' => $item['mrp'],
                'gst_rate' => $item['gst_rate'],
                'discount_per_unit' => $item['discount_amount'] / $item['quantity'],
                'base_amount' => $item['base_amount'] / $item['quantity'],
                'cgst_per_unit' => $item['cgst_amount'] / $item['quantity'],
                'sgst_per_unit' => $item['sgst_amount'] / $item['quantity'],
                'total_per_unit' => $item['total_amount'] / $item['quantity']
            ];
            setFlashMessage('success', 'Item added to return list');
            break;
        }
    }
    
    header('Location: create_credit_note.php?invoice=' . $invoiceNo);
    exit();
}

// Handle remove return item
if (isset($_GET['remove_return'])) {
    $returnId = $_GET['remove_return'];
    unset($_SESSION['credit_note_items'][$returnId]);
    setFlashMessage('success', 'Item removed from return list');
    header('Location: create_credit_note.php?invoice=' . $_GET['invoice']);
    exit();
}

// Handle exchange item addition
if (isset($_POST['add_exchange_item'])) {
    $productId = intval($_POST['product_id']);
    $sizeId = intval($_POST['size_id']);
    $quantity = intval($_POST['quantity']);
    
    // Get product details
    $sql = "SELECT p.*, s.size_name FROM products p, sizes s WHERE p.id = ? AND s.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $productId, $sizeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    
    if ($item) {
        if (!isset($_SESSION['exchange_items'])) {
            $_SESSION['exchange_items'] = [];
        }
        
        $exchangeId = 'exchange_' . $productId . '_' . $sizeId;
        $_SESSION['exchange_items'][$exchangeId] = [
            'product_id' => $productId,
            'size_id' => $sizeId,
            'product_code' => $item['product_code'],
            'product_name' => $item['product_name'],
            'size_name' => $item['size_name'],
            'quantity' => $quantity,
            'mrp' => $item['mrp'],
            'gst_rate' => $item['gst_rate']
        ];
        setFlashMessage('success', 'Exchange item added');
    }
    
    header('Location: create_credit_note.php?invoice=' . $invoiceNo);
    exit();
}

// Handle generate credit note
if (isset($_POST['generate_credit_note'])) {
    $invoiceNo = sanitize($_POST['invoice_no']);
    $refundMode = sanitize($_POST['refund_mode']);
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (empty($_SESSION['credit_note_items'])) {
        setFlashMessage('error', 'Please add items to return');
    } else {
        $conn->begin_transaction();
        
        try {
            // Get invoice details
            $sql = "SELECT * FROM invoices WHERE invoice_no = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $invoiceNo);
            $stmt->execute();
            $result = $stmt->get_result();
            $invoice = $result->fetch_assoc();
            if (!$invoice) {
                throw new RuntimeException('Invoice not found.');
            }
            
            // Calculate credit note totals
            $subtotal = 0;
            $totalCGST = 0;
            $totalSGST = 0;
            $grandTotal = 0;
            
            foreach ($_SESSION['credit_note_items'] as $item) {
                $itemTotal = $item['total_per_unit'] * $item['quantity'];
                $cgst = $item['cgst_per_unit'] * $item['quantity'];
                $sgst = $item['sgst_per_unit'] * $item['quantity'];
                
                $subtotal += ($item['mrp'] * $item['quantity']);
                $totalCGST += $cgst;
                $totalSGST += $sgst;
                $grandTotal += $itemTotal;
            }
            
            // Generate credit note number
            $creditNoteNo = generateCreditNoteNumber($conn);
            $creditDate = date('Y-m-d');
            $userId = getUserId();
            
            // Insert credit note
            $cnSql = "INSERT INTO credit_notes (credit_note_no, credit_date, invoice_id, party_id, subtotal, cgst_amount, sgst_amount, total_amount, refund_mode, notes, created_by) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $cnStmt = $conn->prepare($cnSql);
            $cnStmt->bind_param('ssiiddddssi', $creditNoteNo, $creditDate, $invoice['id'], $invoice['party_id'], $subtotal, $totalCGST, $totalSGST, $grandTotal, $refundMode, $notes, $userId);
            if (!$cnStmt->execute()) {
                throw new RuntimeException('Unable to save the credit note.');
            }
            $creditNoteId = $conn->insert_id;
            
            // Insert credit note items and restore stock
            foreach ($_SESSION['credit_note_items'] as $item) {
                $itemBase = $item['base_amount'] * $item['quantity'];
                $itemCGST = $item['cgst_per_unit'] * $item['quantity'];
                $itemSGST = $item['sgst_per_unit'] * $item['quantity'];
                $itemTotal = $item['total_per_unit'] * $item['quantity'];
                
                $cniSql = "INSERT INTO credit_note_items (credit_note_id, invoice_item_id, product_id, size_id, quantity, mrp, gst_rate, base_amount, cgst_amount, sgst_amount, total_amount) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $cniStmt = $conn->prepare($cniSql);
                $cniStmt->bind_param('iiiiidddddd', $creditNoteId, $item['invoice_item_id'], $item['product_id'], $item['size_id'], $item['quantity'], $item['mrp'], $item['gst_rate'], $itemBase, $itemCGST, $itemSGST, $itemTotal);
                if (!$cniStmt->execute()) {
                    throw new RuntimeException('Unable to save a credit note item.');
                }
                
                // Restore stock
                updateStock($conn, $item['product_id'], $item['size_id'], $item['quantity'], 'add');
                
                // Record transaction
                recordStockTransaction($conn, $item['product_id'], $item['size_id'], $item['quantity'], 'RETURN', 'CREDIT_NOTE', $creditNoteId, 'Credit Note: ' . $creditNoteNo);
            }
            
            // Handle exchange if any
            if (isset($_SESSION['exchange_items']) && !empty($_SESSION['exchange_items'])) {
                // Create new invoice for exchange items
                $exchangeInvoiceNo = generateInvoiceNumber($conn);
                $exchangeSubtotal = 0;
                $exchangeCGST = 0;
                $exchangeSGST = 0;
                $exchangeTotal = 0;
                
                foreach ($_SESSION['exchange_items'] as $exItem) {
                    $gstBreakdown = calculateGSTBreakdown($exItem['mrp'], $exItem['gst_rate']);
                    $itemTotal = $exItem['mrp'] * $exItem['quantity'];
                    $itemBase = $gstBreakdown['base_amount'] * $exItem['quantity'];
                    $itemCGST = $gstBreakdown['cgst'] * $exItem['quantity'];
                    $itemSGST = $gstBreakdown['sgst'] * $exItem['quantity'];
                    
                    $exchangeSubtotal += $itemTotal;
                    $exchangeCGST += $itemCGST;
                    $exchangeSGST += $itemSGST;
                    $exchangeTotal += $itemTotal;
                }
                
                $exInvoiceSql = "INSERT INTO invoices (invoice_no, invoice_date, party_id, subtotal, discount_amount, cgst_amount, sgst_amount, total_amount, payment_mode, notes, created_by) 
                                 VALUES (?, ?, ?, ?, 0, ?, ?, ?, 'Exchange', ?, ?)";
                $exInvStmt = $conn->prepare($exInvoiceSql);
                $exInvStmt->bind_param('ssiddddsi', $exchangeInvoiceNo, $creditDate, $invoice['party_id'], $exchangeSubtotal, $exchangeCGST, $exchangeSGST, $exchangeTotal, $notes, $userId);
                if (!$exInvStmt->execute()) {
                    throw new RuntimeException('Unable to save the exchange invoice.');
                }
                $exchangeInvoiceId = $conn->insert_id;
                
                // Insert exchange items
                foreach ($_SESSION['exchange_items'] as $exItem) {
                    $gstBreakdown = calculateGSTBreakdown($exItem['mrp'], $exItem['gst_rate']);
                    $itemBase = $gstBreakdown['base_amount'] * $exItem['quantity'];
                    $itemCGST = $gstBreakdown['cgst'] * $exItem['quantity'];
                    $itemSGST = $gstBreakdown['sgst'] * $exItem['quantity'];
                    $itemTotal = $exItem['mrp'] * $exItem['quantity'];
                    
                    $exItemSql = "INSERT INTO invoice_items (invoice_id, product_id, size_id, quantity, mrp, gst_rate, discount_amount, base_amount, cgst_amount, sgst_amount, total_amount) 
                                  VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)";
                    $exItemStmt = $conn->prepare($exItemSql);
                    $exItemStmt->bind_param('iiiidddddd', $exchangeInvoiceId, $exItem['product_id'], $exItem['size_id'], $exItem['quantity'], $exItem['mrp'], $exItem['gst_rate'], $itemBase, $itemCGST, $itemSGST, $itemTotal);
                    if (!$exItemStmt->execute()) {
                        throw new RuntimeException('Unable to save an exchange item.');
                    }
                    
                    // Update stock
                    updateStock($conn, $exItem['product_id'], $exItem['size_id'], $exItem['quantity'], 'subtract');
                    recordStockTransaction($conn, $exItem['product_id'], $exItem['size_id'], $exItem['quantity'], 'OUT', 'SALE', $exchangeInvoiceId, 'Exchange Invoice: ' . $exchangeInvoiceNo);
                }
            }
            
            $conn->commit();
            
            // Clear session
            $_SESSION['credit_note_items'] = [];
            unset($_SESSION['exchange_items']);
            
            setFlashMessage('success', 'Credit Note generated successfully!');
            header('Location: view_credit_note.php?cn=' . $creditNoteNo);
            exit();
            
        } catch (Throwable $e) {
            $conn->rollback();
            setFlashMessage('error', 'Error generating credit note: ' . $e->getMessage());
        }
    }
}

// Calculate return summary
$returnSummary = ['subtotal' => 0, 'cgst' => 0, 'sgst' => 0, 'total' => 0];
foreach ($_SESSION['credit_note_items'] as $item) {
    $returnSummary['subtotal'] += ($item['mrp'] * $item['quantity']);
    $returnSummary['cgst'] += ($item['cgst_per_unit'] * $item['quantity']);
    $returnSummary['sgst'] += ($item['sgst_per_unit'] * $item['quantity']);
    $returnSummary['total'] += ($item['total_per_unit'] * $item['quantity']);
}

// Calculate exchange summary
$exchangeSummary = ['subtotal' => 0, 'total' => 0];
if (isset($_SESSION['exchange_items'])) {
    foreach ($_SESSION['exchange_items'] as $item) {
        $exchangeSummary['subtotal'] += ($item['mrp'] * $item['quantity']);
        $exchangeSummary['total'] += ($item['mrp'] * $item['quantity']);
    }
}

$balanceAmount = $exchangeSummary['total'] - $returnSummary['total'];
?>

<?php require_once __DIR__ . '/create_credit_note_view.php'; ?>
