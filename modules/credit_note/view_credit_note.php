<?php
ob_start();
$pageTitle = 'View Credit Note - Payal Arban Stichis';
require_once __DIR__ . '/../../includes/header.php';

$creditNoteNo = $_GET['cn'] ?? '';

if (empty($creditNoteNo)) {
    setFlashMessage('error', 'Credit Note not found');
    header('Location: list.php');
    exit();
}

// Get credit note details
$sql = "SELECT cn.*, p.party_name, p.mobile, p.address, i.invoice_no
        FROM credit_notes cn
        INNER JOIN parties p ON cn.party_id = p.id
        INNER JOIN invoices i ON cn.invoice_id = i.id
        WHERE cn.credit_note_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $creditNoteNo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    setFlashMessage('error', 'Credit Note not found');
    header('Location: list.php');
    exit();
}

$creditNote = $result->fetch_assoc();

// Get credit note items
$itemsSql = "SELECT cni.*, p.product_code, p.product_name, c.category_name, c.hsn_code, s.size_name
             FROM credit_note_items cni
             INNER JOIN products p ON cni.product_id = p.id
             LEFT JOIN categories c ON p.category_id = c.id  
             INNER JOIN sizes s ON cni.size_id = s.id
             WHERE cni.credit_note_id = ?
             ORDER BY cni.id";
$itemsStmt = $conn->prepare($itemsSql);
$itemsStmt->bind_param('i', $creditNote['id']);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();

$items = [];
while ($item = $itemsResult->fetch_assoc()) {
    $items[] = $item;
}

function creditAmountToWords($number)
{
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    $number = (int) $number;

    if ($number < 20) return $ones[$number];
    if ($number < 100) return trim($tens[intdiv($number, 10)] . ' ' . $ones[$number % 10]);
    if ($number < 1000) return trim($ones[intdiv($number, 100)] . ' Hundred ' . creditAmountToWords($number % 100));
    if ($number < 100000) return trim(creditAmountToWords(intdiv($number, 1000)) . ' Thousand ' . creditAmountToWords($number % 1000));
    if ($number < 10000000) return trim(creditAmountToWords(intdiv($number, 100000)) . ' Lakh ' . creditAmountToWords($number % 100000));
    return trim(creditAmountToWords(intdiv($number, 10000000)) . ' Crore ' . creditAmountToWords($number % 10000000));
}

$amountInWords = creditAmountToWords((int) round($creditNote['total_amount'])) . ' Rupees Only';
?>

<div class="no-print mb-3">
    <button type="button" onclick="window.print()" class="btn btn-primary">
        <i class="bi bi-printer"></i> Print Credit Note
    </button>
    <a href="create_credit_note.php" class="btn btn-danger">
        <i class="bi bi-plus-circle"></i> New Credit Note
    </a>
    <a href="list.php" class="btn btn-secondary">
        <i class="bi bi-list"></i> All Credit Notes
    </a>
</div>

<div class="credit-note-print" id="credit-note-content">
    <style>
        .credit-note-print {
            width: 210mm;
            max-width: 210mm;
            margin: 0 auto;
            padding: 10px;
            color: #111;
            background: #fff;
            border: 1px solid #bbb;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
            font-size: 11px;
        }

        .cn-header {
            text-align: center;
            border-bottom: .5px solid #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }

        .cn-title {
            float: left;
            font-size: 15px;
            font-weight: 700;
            margin: 5px 0;
        }

        .cn-company-details {
            font-size: 10px;
            line-height: 1.4;
        }

        .cn-info {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin: 8px 0;
            font-size: 10px;
            line-height: 1.3;
        }

        .cn-info>div:first-child {
            width: 60%;
        }

        .cn-info-right {
            width: 38%;
            text-align: right;
        }

        .cn-items {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 10px;
        }

        .cn-items th,
        .cn-items td {
            border-right: .25px solid #000;
            padding: 4px 3px;
            vertical-align: top;
        }

        .cn-items th {
            background: #f2f2f2;
            text-align: center;
            border-top: .25px solid #000;
            border-bottom: .25px solid #000;
            font-size: 9px;
        }

        .cn-items td {
            font-size: 10px;
        }

        .cn-items th:first-child,
        .cn-items td:first-child {
            border-left: .25px solid #000;
        }

        .cn-center {
            text-align: center;
        }

        .cn-right {
            text-align: right;
        }

        .cn-total-row {
            font-weight: 700;
            background: #f2f2f2;
        }

        .cn-words {
            margin: 8px 0;
            padding: 5px;
            border: 1px solid #000;
            background: #fafafa;
            font-style: italic;
        }

        .cn-notes {
            margin-top: 6px;
            padding: 4px;
            border: .5px solid #999;
        }

        .cn-footer {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #333;
            font-size: 8px;
            line-height: 1.4;
        }

        .cn-terms {
            width: 62%;
        }

        .cn-signature {
            width: 38%;
            text-align: right;
        }

        .cn-signature-line {
            display: inline-block;
            width: 150px;
            margin-top: 40px;
            border-top: 1px solid #333;
            text-align: center;
            font-size: 10px;
        }

        .cn-thanks {
            margin-top: 6px;
            text-align: center;
            color: #555;
            font-size: 7px;
            font-style: italic;
        }

        @media print {
            @page {
                size: A5 portrait;
                margin: 0;
            }

            html,
            body {
                width: 148mm;
                height: 210mm;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }

            body * {
                visibility: hidden !important;
            }

            .credit-note-print,
            .credit-note-print * {
                visibility: visible !important;
            }

            .credit-note-print {
                position: fixed !important;
                left: 1mm !important;
                top: 4mm !important;
                width: 300mm !important;
                max-width: 300mm !important;
                margin: 0 !important;
                padding: 8px !important;
                border: 1px solid #333 !important;
                box-sizing: border-box !important;
                overflow: visible !important;
                transform: scale(.72);
                transform-origin: top left;
                page-break-inside: avoid !important;
            }
        }
    </style>

    <div class="cn-header">
        <div class="cn-title">CREDIT NOTE</div>

        <div class="cn-company-details">
            <strong>31/3 Tirupati Avenue, Pushpkunj Society Gate-4, Kankaria BRTS Road, Ahmedabad - 380008. M: 91065-31790</strong>
        </div>

    </div>

    <div class="cn-info">
        <div>
            <strong>Name:</strong> <?php echo htmlspecialchars(strtoupper($creditNote['party_name'])); ?><br>
            <strong>Contact No:</strong> <?php echo !empty($creditNote['mobile']) ? htmlspecialchars($creditNote['mobile']) : '-'; ?><br>
            <?php if (!empty($creditNote['address'])): ?>
                <strong>Address:</strong> <?php echo htmlspecialchars($creditNote['address']); ?>
            <?php endif; ?>
        </div>

        <div class="cn-info-right">
            <strong>Credit Note No:</strong> <?php echo htmlspecialchars($creditNote['credit_note_no']); ?><br>
            <strong>Date:</strong> <?php echo date('d/m/Y', strtotime($creditNote['credit_date'])); ?><br>
            <strong>Against Invoice:</strong> <?php echo htmlspecialchars($creditNote['invoice_no']); ?><br>
            <strong>Refund Mode:</strong> <?php echo htmlspecialchars($creditNote['refund_mode']); ?>
        </div>
    </div>

    <table class="cn-items">
        <colgroup>
            <col style="width: 30px">
            <col style="width: 80px">
            <col>
            <col style="width: 40px">
            <col style="width: 50px">
            <col style="width: 55px">
            <col style="width: 50px">
            <col style="width: 50px">
            <col style="width: 60px">
        </colgroup>
        <thead>
            <tr>
                <th>Sr.No</th>
                <th>Item Code</th>
                <th>Particular</th>
                <th>Qty</th>
                <th>Rate</th>
                <th>HSN Code</th>
                <th>I/CGST Amt</th>
                <th>S/CGST Amt</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $index => $item): ?>
                <tr>
                    <td class="cn-center"><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($item['product_code']); ?></td>
                    <td><strong><?php echo !empty($item['category_name']) ? htmlspecialchars(strtoupper($item['category_name'])) . ' - ' : ''; ?><?php echo htmlspecialchars(strtoupper($item['product_name'])); ?>&nbsp;&nbsp;<small>Size: <?php echo htmlspecialchars($item['size_name']); ?></small></strong></td>
                    <td class="cn-center"><strong><?php echo (int) $item['quantity']; ?></strong></td>
                    <td class="cn-right"><?php echo number_format($item['base_amount'], 2); ?></td>
                    <td class="cn-center"><strong><?php echo htmlspecialchars($item['hsn_code'] ?? '-'); ?></strong></td>
                    <td class="cn-right"><?php echo number_format($item['cgst_amount'], 2); ?></td>
                    <td class="cn-right"><?php echo number_format($item['sgst_amount'], 2); ?></td>
                    <td class="cn-right"><strong><?php echo number_format($item['total_amount'], 2); ?></strong></td>
                </tr>
            <?php endforeach; ?>
            <?php for ($row = count($items); $row < 7; $row++): ?>
                <tr>
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            <?php endfor; ?>
            <tr>
                <td colspan="4" class="cn-right"><strong>Total:</strong></td>
                <td class="cn-right"><?php echo number_format(array_sum(array_column($items, 'base_amount')), 2); ?></td>
                <td></td>
                <td class="cn-right"><?php echo number_format($creditNote['cgst_amount'], 2); ?></td>
                <td class="cn-right"><?php echo number_format($creditNote['sgst_amount'], 2); ?></td>
                <td class="cn-right"><strong><?php echo number_format($creditNote['total_amount'], 2); ?></strong></td>
            </tr>
            <tr class="cn-total-row">
                <td colspan="8" class="cn-right">Credit Amount:</td>
                <td class="cn-right"><?php echo number_format($creditNote['total_amount'], 2); ?></td>
            </tr>
        </tbody>
    </table>

    <div class="cn-words"><strong>Credit Note Value in words:</strong> <?php echo htmlspecialchars($amountInWords); ?></div>

    <?php if (!empty($creditNote['notes'])): ?>
        <div class="cn-notes"><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($creditNote['notes'])); ?></div>
    <?php endif; ?>

    <div class="cn-footer">
        <div class="cn-terms">
            <strong>GSTIN:</strong> 24AIQPA5593E1Z8<br>
            Subject to Ahmedabad Jurisdiction.<br><br>
            <strong>Terms &amp; Conditions:</strong><br>
            1. This credit note is valid only against the invoice shown above.<br>
            2. Credit notes issued for exchanges are not redeemable for cash.<br>
            3. This is a computer generated credit note and no signature is required.
        </div>
        <div class="cn-signature">
            <strong>For, Your-boutique</strong><br>
            <div class="cn-signature-line">Authorised Signatory</div>
        </div>
    </div>
    <div class="cn-thanks">Thank you for shopping with us!</div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>