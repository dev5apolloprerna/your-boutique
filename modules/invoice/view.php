<?php
ob_start();
// $pageTitle = 'View Invoice - Your-boutique';
$pageTitle = 'View Invoice - Payal Arban Stichis';
require_once __DIR__ . '/../../includes/header.php';

$invoiceNo = $_GET['invoice'] ?? '';

if (empty($invoiceNo)) {
    setFlashMessage('error', 'Invoice not found');
    header('Location: list.php');
    exit();
}

// Get invoice details
$sql = "SELECT i.*, p.party_name, p.mobile
        FROM invoices i
        INNER JOIN parties p ON i.party_id = p.id
        WHERE i.invoice_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $invoiceNo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Invoice not found');
    header('Location: list.php');
    exit();
}

$invoice = $result->fetch_assoc();

$paymentsSql = "SELECT payment_mode, amount FROM invoice_payments WHERE invoice_id = ? ORDER BY id";
$paymentsStmt = $conn->prepare($paymentsSql);
$paymentsStmt->bind_param('i', $invoice['id']);
$paymentsStmt->execute();
$paymentsResult = $paymentsStmt->get_result();
$payments = $paymentsResult->fetch_all(MYSQLI_ASSOC);

$creditPaymentsSql = "SELECT cn.credit_note_no, cna.amount
                      FROM credit_note_applications cna
                      INNER JOIN credit_notes cn ON cn.id = cna.credit_note_id
                      WHERE cna.invoice_id = ? ORDER BY cna.id";
$creditPaymentsStmt = $conn->prepare($creditPaymentsSql);
$creditPaymentsStmt->bind_param('i', $invoice['id']);
$creditPaymentsStmt->execute();
foreach ($creditPaymentsStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $creditPayment) {
    $payments[] = [
        'payment_mode' => 'Credit Note ' . $creditPayment['credit_note_no'],
        'amount' => $creditPayment['amount']
    ];
}

// Get invoice items
$itemsSql = "SELECT 
                ii.*, 
                p.product_code, 
                p.product_name, 
                c.category_name,
                c.hsn_code,
                c.is_split,
                c.gst_rate AS category_gst_rate,
                s.size_name
             FROM invoice_items ii
             INNER JOIN products p ON ii.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id
             INNER JOIN sizes s ON ii.size_id = s.id
             WHERE ii.invoice_id = ?";
$itemsStmt = $conn->prepare($itemsSql);
$itemsStmt->bind_param('i', $invoice['id']);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();

$items = [];
$calculatedDiscount = 0;
while ($item = $itemsResult->fetch_assoc()) {
    $grossAmount = round((float) $item['mrp'] * (int) $item['quantity'], 2);
    $storedDiscount = round((float) $item['discount_amount'], 2);
    $amountDifference = max(0, round($grossAmount - (float) $item['total_amount'], 2));
    $item['display_discount_amount'] = max($storedDiscount, $amountDifference);
    $calculatedDiscount += $item['display_discount_amount'];
    $items[] = $item;
}

// Older invoices may have an empty header discount even when item discounts exist.
$displayDiscount = max(round((float) $invoice['discount_amount'], 2), round($calculatedDiscount, 2));
$discountPercent = (float) $invoice['subtotal'] > 0
    ? round(($displayDiscount / (float) $invoice['subtotal']) * 100, 2)
    : 0;

// Convert total to words
function numberToWords($number)
{
    $ones = array('', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen');
    $tens = array('', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety');

    if ($number < 20) return $ones[$number];
    if ($number < 100) return $tens[intval($number / 10)] . ' ' . $ones[$number % 10];
    if ($number < 1000) return $ones[intval($number / 100)] . ' Hundred ' . numberToWords($number % 100);
    if ($number < 100000) return numberToWords(intval($number / 1000)) . ' Thousand ' . numberToWords($number % 1000);
    if ($number < 10000000) return numberToWords(intval($number / 100000)) . ' Lakh ' . numberToWords($number % 100000);
    return numberToWords(intval($number / 10000000)) . ' Crore ' . numberToWords($number % 10000000);
}

$amountInWords = numberToWords(intval($invoice['total_amount'])) . ' Rupees Only';
?>

<div class="no-print mb-3">
    <button onclick="generatePDF()" class="btn btn-primary">
        <i class="bi bi-printer"></i> Print Invoice
    </button>
    <button onclick="generatePDF()" class="btn btn-success">
        <i class="bi bi-file-pdf"></i> Download PDF
    </button>
    <a href="delete.php?invoice=<?php echo $invoiceNo; ?>" class="btn btn-danger">
        <i class="bi bi-trash"></i> Delete Invoice
    </a>
    <a href="create_invoice.php" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> New Invoice
    </a>
    <a href="list.php" class="btn btn-secondary">
        <i class="bi bi-list"></i> All Invoices
    </a>
</div>

<div class="print-area" id="invoice-content">
    <style>
        @media print {
            @page {
                size: 148mm 210mm;
                margin: 0mm;
            }

            body {
                font-size: 7pt;
                line-height: 1.3;

            }

            .no-print,
            header,
            nav,
            .navbar,
            .sidebar,
            .topbar,
            .main-header,
            .main-sidebar,
            .main-footer,
            footer {
                display: none !important;
                visibility: hidden !important;
            }

            .print-area,
            .print-area *,
            #invoice-content,
            #invoice-content * {
                visibility: visible !important;
            }

            .print-area,
            #invoice-content {
                position: fixed !important;
                left: 1mm !important;
                top: 4mm !important;

                /* Keep your landscape invoice design but fit it inside portrait paper */
                width: 350mm !important;
                max-width: 350mm !important;
                margin: 0 !important;
                padding: 0 !important;

                transform: scale(0.72);
                transform-origin: top left;

                background: #fff !important;
                overflow: visible !important;
            }

            .invoice-container {
                width: 300mm !important;
                max-width: 300mm !important;
                margin: 0 !important;
                padding: 8px !important;
                border: 1px solid #333 !important;
                box-sizing: border-box !important;
                overflow: visible !important;
                page-break-inside: avoid !important;
            }


            .invoice-header {
                text-align: center;
                border-bottom: 0.50px solid #000;
                padding-bottom: 8px;
                margin-bottom: 8px;
            }

        }

        .invoice-container {
            width: 210mm !important;
            max-width: 210mm !important;
            margin: 0 auto;
            background: white;
            padding: 10px;
            font-family: Arial, sans-serif;
            font-size: 11px;
            border: 1px solid #ddd;
        }

        .invoice-header {
            text-align: center;
            border-bottom: 0.50px solid #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .invoice-title {
            font-size: 16px;
            font-weight: bold;
            margin: 5px 0;
        }

        .company-logo {
            max-width: 120px;
            max-height: 60px;
            margin: 0 auto 5px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin: 5px 0;
        }

        .company-details {
            font-size: 10px;
            color: #555;
            line-height: 1.4;
        }

        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 10px;
        }

        .invoice-info-left {
            width: 60%;
        }

        .invoice-info-right {
            width: 38%;
            text-align: right;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 10px;
        }

        .items-table th {
            background: #f5f5f5;
            border-bottom: 0.25px solid #000;
            border-right: 0.25px solid #000;
            border-top: 0.25px solid #000;
            padding: 4px 3px;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
        }

        .items-table td {
            /*border-bottom: 0.25px solid #000;*/
            border-right: 0.25px solid #000;
            padding: 4px 3px;
            font-size: 10px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-section {
            margin-top: 5px;
        }

        .total-row {
            font-weight: bold;
            background: #f5f5f5;
            border: 1px solid #000;

        }

        .amount-words {
            margin: 8px 0;
            padding: 5px;
            background: #f9f9f9;
            border: 1px solid #000;
            font-size: 10px;
            font-style: italic;
        }

        .footer-section {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #333;
        }

        .terms {
            font-size: 8px;
            line-height: 1.4;
            margin: 5px 0;
        }

        .signature-section {
            text-align: right;
            margin-top: 15px;
        }

        .signature-line {
            margin-top: 40px;
            border-top: 1px solid #333;
            display: inline-block;
            width: 150px;
            text-align: center;
            font-size: 10px;
        }

        .gstin-section {
            margin: 5px 0;
            font-size: 9px;
        }

        .company-tagline {
            text-align: center;
            margin-top: 5px;
            font-size: 7px;
            font-style: italic;
            color: #666;
        }
    </style>

    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">

            <div class="company-name" style="width:100%; !important;"><span style="font-size:15px;float:left;">TAX INVOICE</span> <img style="margin-right: 80px;" src="../../assets/images/logo.jpeg" alt="Payal Urban Stitch" class="company-logo"
                    onerror="this.style.display='none'"></div>
            <div class="company-details">
                <strong>31/3 Tirupati Avenue, Pushpkunj Society Gate-4,
                    Kankaria BRTS Road, Ahmedabad - 380008. M : 91065-31790 </strong>
            </div>
        </div>

        <!--<div class="invoice-title text-center">TAX INVOICE</div>-->

        <!-- Invoice Info -->
        <div class="invoice-info">
            <div class="invoice-info-left">
                <!--<strong>Name:</strong> <?php echo strtoupper($invoice['party_name']); ?><br>-->
                <!--<strong>Contact No:</strong> <?php echo $invoice['mobile']; ?>-->
                <strong>Name:</strong> <?php echo htmlspecialchars(strtoupper($invoice['party_name'])); ?><br>
                <strong>Contact No:</strong> <?php echo !empty($invoice['mobile']) ? htmlspecialchars($invoice['mobile']) : '-'; ?>
            </div>
            <div class="invoice-info-right">
                <strong>Invoice No:</strong> <?php echo $invoice['invoice_no']; ?><br>
                <strong>Date:</strong> <?php echo date('d/m/Y', strtotime($invoice['invoice_date'])); ?><br>
                <strong>Payment:</strong>
                <?php if (!empty($payments)): ?>
                    <?php foreach ($payments as $index => $payment): ?>
                        <?php echo $index > 0 ? ' + ' : ''; ?><?php echo htmlspecialchars($payment['payment_mode']); ?>
                        (<?php echo formatCurrency($payment['amount']); ?>)
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php echo htmlspecialchars($invoice['payment_mode']); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="border-left: 0.25px solid #000;" width="30">Sr.No</th>
                    <!--<th width="30" style="border-left: 0.25px solid #000;">Sr.No</th>-->
                    <th width="80">Item Code</th>
                    <th>Particular</th>
                    <th width="40">Qty</th>
                    <th width="50">Rate</th>
                    <th width="55">HSN Code</th>
                    <th width="50">I/CGST Amt</th>
                    <th width="50">S/CGST Amt</th>
                    <th width="60">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sr = 1;
                $subtotal = 0;
                $totalCGST = 0;
                $totalSGST = 0;
                $iCounter = 0;
                $subTotal = 0;

                function splitInclusiveGstAmount($grossAmount, $gstRate)
                {
                    $grossAmount = round((float)$grossAmount, 2);
                    $gstRate = (float)$gstRate;

                    $baseAmount = round($grossAmount / (1 + ($gstRate / 100)), 2);
                    $taxAmount  = round($grossAmount - $baseAmount, 2);

                    $cgstAmount = round($taxAmount / 2, 2);
                    $sgstAmount = round($taxAmount - $cgstAmount, 2);

                    return [
                        'base_amount'  => $baseAmount,
                        'cgst_amount'  => $cgstAmount,
                        'sgst_amount'  => $sgstAmount,
                        'total_amount' => $grossAmount
                    ];
                }
                // while ($item = $itemsResult->fetch_assoc()):
                foreach ($items as $item):

                    $gstRate = isset($item['gst_rate']) && $item['gst_rate'] !== ''
                        ? (float)$item['gst_rate']
                        : (float)$item['category_gst_rate'];

                    // Always print the full item value; discounts belong to the invoice summary.
                    $itemTotalAmount = round((float)$item['mrp'] * (int)$item['quantity'], 2);

                    $shouldSplit = (
                        $gstRate == 5.00 &&
                        $itemTotalAmount > 2500 &&
                        (int)$item['is_split'] === 1
                    );

                    $rowsToPrint = [];

                    if ($shouldSplit) {
                        // First product fixed 2350
                        $firstAmount = 2350;

                        // Second product remaining amount
                        $secondAmount = round($itemTotalAmount - $firstAmount, 2);

                        $firstCalc = splitInclusiveGstAmount($firstAmount, $gstRate);
                        $secondCalc = splitInclusiveGstAmount($secondAmount, $gstRate);

                        $rowsToPrint[] = array_merge($item, [
                            'print_product_code' => $item['product_code'],
                            'print_product_name' => $item['product_name'],
                            'print_base_amount'  => $firstCalc['base_amount'],
                            'print_cgst_amount'  => $firstCalc['cgst_amount'],
                            'print_sgst_amount'  => $firstCalc['sgst_amount'],
                            'print_total_amount' => $firstCalc['total_amount'],
                        ]);

                        $rowsToPrint[] = array_merge($item, [
                            'print_product_code' => $item['product_code'] . '-1',
                            'print_product_name' => $item['product_name'] . '-1',
                            'print_base_amount'  => $secondCalc['base_amount'],
                            'print_cgst_amount'  => $secondCalc['cgst_amount'],
                            'print_sgst_amount'  => $secondCalc['sgst_amount'],
                            'print_total_amount' => $secondCalc['total_amount'],
                        ]);
                    } else {
                        $fullAmountCalc = splitInclusiveGstAmount($itemTotalAmount, $gstRate);
                        $rowsToPrint[] = array_merge($item, [
                            'print_product_code' => $item['product_code'],
                            'print_product_name' => $item['product_name'],
                            'print_base_amount'  => $fullAmountCalc['base_amount'],
                            'print_cgst_amount'  => $fullAmountCalc['cgst_amount'],
                            'print_sgst_amount'  => $fullAmountCalc['sgst_amount'],
                            'print_total_amount' => $fullAmountCalc['total_amount'],
                        ]);
                    }

                    foreach ($rowsToPrint as $rowIndex => $printItem): // foreach ($rowsToPrint as $printItem):

                        $gstPercent = $gstRate / 2;

                        $subTotal += $printItem['print_base_amount'];
                        $totalCGST += $printItem['print_cgst_amount'];
                        $totalSGST += $printItem['print_sgst_amount'];
                ?>
                        <tr>
                            <td style="border-left: 0.25px solid #000;" class="text-center">
                                <?php echo $sr++; ?>
                            </td>

                            <td><?php echo $printItem['print_product_code']; ?></td>

                            <td>
                                <strong>
                                    <?php echo $printItem['category_name'] ? strtoupper($printItem['category_name']) . ' - ' : ''; ?>
                                    <?php echo strtoupper($printItem['print_product_name']); ?>&nbsp;&nbsp;
                                    <small>Size: <?php echo $printItem['size_name']; ?></small>
                                </strong>
                            </td>

                            <td class="text-center">
                                <strong><?php echo $printItem['quantity']; ?></strong>
                            </td>

                            <td class="text-right">
                                <strong><?php echo number_format($printItem['print_base_amount'], 2); ?></strong>
                            </td>

                            <td class="text-center">
                                <strong><?php echo htmlspecialchars($printItem['hsn_code'] ?? '-'); ?></strong>
                            </td>

                            <td class="text-right">
                                <strong><?php echo number_format($printItem['print_cgst_amount'], 2); ?></strong>
                            </td>

                            <td class="text-right">
                                <strong><?php echo number_format($printItem['print_sgst_amount'], 2); ?></strong>
                            </td>

                            <td class="text-right">
                                <strong><?php echo number_format($printItem['print_total_amount'], 2); ?></strong>
                            </td>
                        </tr>

                <?php
                        $iCounter++;
                    endforeach;

                endforeach;
                ?>
                <?php
                $TotalRow = 7;
                if ($displayDiscount > 0) { ///if($invoice['discount_amount'] > 0){
                    $TotalRow = 5;
                }
                while ($iCounter <  $TotalRow) :

                ?>
                    <tr>
                        <td style="border-left: 0.25px solid #000;" class="text-center"><?php echo $sr; ?></td>
                        <!--<td class="text-center" style="border-left: 0.25px solid #000;"><?php echo $sr++; ?></td>-->
                        <td></td>
                        <td class="text-center"></td>
                        <td class="text-right"></td>
                        <td class="text-center"></td>
                        <td class="text-right"></td>
                        <td class="text-center"></td>
                        <td class="text-right"></td>
                        <td class="text-right"></td>
                    </tr>
                <?php $iCounter++;
                endwhile; ?>
                <!-- Total Row -->
                <?php // if($invoice['discount_amount'] > 0){ 
                ?>
                <?php if ($displayDiscount > 0) { ?>
                    <!--<tr>
                        <td style="border-left: 0.25px solid #000;" class="text-center">7</td>
                        <td></td>
                        <td class="text-left">
                            <strong>Discount<?php echo $discountPercent > 0 ? ' (' . number_format($discountPercent, 2) . '%)' : ''; ?></strong>
                        </td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-right">
                            <strong>-<?php echo number_format($displayDiscount, 2); ?></strong>
                        </td>
                    </tr> -->
                <?php } ?>
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>Total:</strong></td>
                    <td class="text-right"><?php echo number_format($subTotal, 2); ?></td>
                    <td></td>
                    <td class="text-right"><?php echo number_format($totalCGST, 2); ?></td>
                    <td class="text-right"><?php echo number_format($totalSGST, 2); ?></td>
                    <td class="text-right"><strong><?php echo number_format($invoice['subtotal'], 2); ?></strong></td>
                </tr>
                <?php if ($displayDiscount > 0): ?>
                    <tr>
                        <td colspan="8" style="border-left: 0.25px solid #000;" class="text-right"><strong>Gross Amount:</strong></td>
                        <td width="100" class="text-right"><?php echo number_format($invoice['subtotal'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="8" style="border-left: 0.25px solid #000;" class="text-right"><strong>Discount (<?php echo number_format($discountPercent, 2); ?>%):</strong></td>
                        <td class="text-right"><strong>-<?php echo number_format($displayDiscount, 2); ?></strong></td>
                    </tr>
                    <tr class="total-row">
                        <td colspan="8" style="border-left: 0.25px solid #000;" class="text-right"><strong>Net Payable:</strong></td>
                        <td class="text-right"><strong><?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($displayDiscount > 0): ?>
            <!-- <table class="items-table totals-section" aria-label="Invoice discount summary">
            <tbody>
                <tr>
                    <td class="text-right"><strong>Gross Amount:</strong></td>
                    <td width="100" class="text-right"><?php echo number_format($invoice['subtotal'], 2); ?></td>
                </tr>
                <tr>
                    <td class="text-right"><strong>Discount (<?php echo number_format($discountPercent, 2); ?>%):</strong></td>
                    <td class="text-right"><strong>-<?php echo number_format($displayDiscount, 2); ?></strong></td>
                </tr>
                <tr class="total-row">
                    <td class="text-right"><strong>Net Payable:</strong></td>
                    <td class="text-right"><strong><?php echo number_format($invoice['total_amount'], 2); ?></strong></td>
                </tr>
            </tbody>
        </table> -->
        <?php endif; ?>

        <!-- Amount in Words -->
        <div class="amount-words">
            <strong>Invoice Value in words:</strong> <?php echo $amountInWords; ?>
        </div>

        <!-- Footer Section -->
        <div class="footer-section">
            <table width="100%" style="border: none;">
                <tr>
                    <td width="60%" style="vertical-align: top; border: none;">
                        <div class="gstin-section">
                            <strong>GSTIN:</strong> 24AIQPA5593E1Z8<br>
                            Subject to Ahmedabad Jurisdiction.
                        </div>

                        <div class="terms">
                            <strong>Terms & Conditions:</strong><br>
                            1. No guarantee for colour, material and slippage.<br>
                            2. Goods will be exchanged within 15 days from the date of purchase, provided the merchandise is in saleable condition and without any use of perfumes, with the original product tag and invoice, shown as proof of purchase.<br>
                            3. Credit note will be issued in exchange case, refunds will not be given.<br>
                            4. This is computer generated invoice and no signature is required.
                        </div>
                    </td>
                    <td width="40%" style="vertical-align: top; border: none;">
                        <div class="signature-section">
                            <strong>For, Your-boutique</strong><br>
                            <div class="signature-line">
                                Authorised Signatory
                            </div>
                        </div>

                        <div style="margin-top: 10px; text-align: right; font-size: 10px;">
                            <!--<strong>TOTAL</strong> : -->


                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="company-tagline">
            Thank you for shopping with us!
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    // function generatePDF() {
    //     const element = document.getElementById('invoice-content');
    //     const opt = {
    //         margin: 5,
    //         filename: '<?php echo $invoiceNo; ?>.pdf',
    //         image: { type: 'jpeg', quality: 0.98 },
    //         html2canvas: { scale: 2, logging: false },
    //         jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    //     };
    //     html2pdf().set(opt).from(element).save();

    // }
    function generatePDF() {
        const element = document.getElementById('invoice-content');

        const opt = {
            margin: 0,
            filename: '<?php echo $invoiceNo; ?>.pdf',
            image: {
                type: 'jpeg',
                quality: 0.98
            },
            html2canvas: {
                scale: 2,
                logging: false,
                useCORS: true
            },
            jsPDF: {
                unit: 'mm',
                format: 'a4', // A5 Portrait
                orientation: 'portrait'
            }
        };

        html2pdf()
            .set(opt)
            .from(element)
            .toPdf()
            .get('pdf')
            .then(function(pdf) {
                pdf.autoPrint();

                const blobUrl = pdf.output('bloburl');
                window.open(blobUrl, '_blank');
            });
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>