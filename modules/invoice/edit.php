<?php
ob_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/functions.php';

requireLogin();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = getDBConnection();

function invoiceEditQuery($conn, $sql, $params = [])
{
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function invoiceEditEscape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function invoiceEditMoney($value)
{
    if (
        !is_scalar($value) ||
        !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', (string)$value)
    ) {
        throw new RuntimeException(
            'Enter a valid amount with at most two decimal places.'
        );
    }

    return (int)round((float)$value * 100);
}

function invoiceEditState($conn, $number, $lock = false)
{
    $suffix = $lock ? ' FOR UPDATE' : '';

    $invoice = invoiceEditQuery(
        $conn,
        'SELECT * FROM invoices WHERE invoice_no = ?' . $suffix,
        [$number]
    )->get_result()->fetch_assoc();

    if (!$invoice) {
        throw new RuntimeException('Invoice not found.');
    }

    $items = invoiceEditQuery(
        $conn,
        'SELECT * FROM invoice_items
         WHERE invoice_id = ? ORDER BY id' . $suffix,
        [$invoice['id']]
    )->get_result()->fetch_all(MYSQLI_ASSOC);

    $payments = invoiceEditQuery(
        $conn,
        'SELECT * FROM invoice_payments
         WHERE invoice_id = ? ORDER BY id' . $suffix,
        [$invoice['id']]
    )->get_result()->fetch_all(MYSQLI_ASSOC);

    return [$invoice, $items, $payments];
}

function invoiceEditVersion($state)
{
    return hash_hmac(
        'sha256',
        json_encode($state),
        $_SESSION['invoice_edit_token']
    );
}

$_SESSION['invoice_edit_token'] ??= bin2hex(random_bytes(32));

$invoiceNo = is_string($_GET['invoice'] ?? null)
    ? $_GET['invoice']
    : '';

try {
    $state = invoiceEditState($conn, $invoiceNo);
} catch (Throwable $e) {
    http_response_code(404);
    exit('Invoice not found or required database tables are unavailable.');
}

[$invoice, $items, $payments] = $state;

$version = invoiceEditVersion($state);
$error = '';
$inTransaction = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (
            !is_string($_POST['csrf'] ?? null) ||
            !hash_equals(
                $_SESSION['invoice_edit_token'],
                $_POST['csrf']
            )
        ) {
            throw new RuntimeException('Invalid form token. Reload the page.');
        }

        if (($_POST['complete_form'] ?? '') !== '1') {
            throw new RuntimeException(
                'The submitted form was incomplete. Reduce the number of rows.'
            );
        }

        $conn->begin_transaction();
        $inTransaction = true;

        $lockedState = invoiceEditState($conn, $invoiceNo, true);

        if (
            !is_string($_POST['version'] ?? null) ||
            !hash_equals(
                invoiceEditVersion($lockedState),
                $_POST['version']
            )
        ) {
            throw new RuntimeException(
                'This invoice changed. Reload the page before editing.'
            );
        }

        [$lockedInvoice, $oldItems] = $lockedState;
        $invoiceId = (int)$lockedInvoice['id'];

        foreach (['credit_notes', 'credit_note_applications'] as $table) {
            $linked = invoiceEditQuery(
                $conn,
                "SELECT id FROM $table
                 WHERE invoice_id = ? FOR UPDATE",
                [$invoiceId]
            )->get_result()->num_rows;

            if ($linked) {
                throw new RuntimeException(
                    'Invoices linked to returns or credit notes cannot be edited.'
                );
            }
        }

        $postedItems = $_POST['items'] ?? null;

        if (!is_array($postedItems) || count($postedItems) > 150) {
            throw new RuntimeException('Invalid item list. Maximum 150 rows.');
        }

        $oldQuantities = [];
        $newQuantities = [];
        $newItems = [];

        foreach ($oldItems as $item) {
            $key = $item['product_id'] . ':' . $item['size_id'];

            $oldQuantities[$key] =
                ($oldQuantities[$key] ?? 0) + (int)$item['quantity'];
        }

        $subtotal = 0;
        $cgstTotal = 0;
        $sgstTotal = 0;

        foreach ($postedItems as $row) {
            if (!is_array($row)) {
                throw new RuntimeException('Invalid invoice item.');
            }

            if (!empty($row['remove'])) {
                continue;
            }

            $stockId = filter_var(
                $row['stock_id'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            $quantity = filter_var(
                $row['quantity'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => [
                    'min_range' => 1,
                    'max_range' => 1000000
                ]]
            );

            if (!$stockId || !$quantity) {
                throw new RuntimeException(
                    'Select a product/size and enter a positive whole quantity.'
                );
            }

            $product = invoiceEditQuery(
                $conn,
                'SELECT ps.product_id, ps.size_id, p.gst_rate
                 FROM product_stock ps
                 JOIN products p ON p.id = ps.product_id
                 WHERE ps.id = ?',
                [$stockId]
            )->get_result()->fetch_assoc();

            if (!$product) {
                throw new RuntimeException('Product/size no longer exists.');
            }

            $price = invoiceEditMoney($row['mrp'] ?? '');
            $gross = $price * $quantity;

            if ($gross > 9999999999) {
                throw new RuntimeException('Item total is too large.');
            }

            $gstRate = (float)$product['gst_rate'];

            // Keep the original GST rate for an unchanged original line.
            foreach ($oldItems as $old) {
                if (
                    (string)$old['id'] === (string)($row['id'] ?? '') &&
                    (int)$old['product_id'] === (int)$product['product_id'] &&
                    (int)$old['size_id'] === (int)$product['size_id']
                ) {
                    $gstRate = (float)$old['gst_rate'];
                    break;
                }
            }

            if ($gstRate < 0 || $gstRate > 100) {
                throw new RuntimeException('Invalid product GST rate.');
            }

            // Matches existing creation: full-value line GST,
            // with the invoice discount stored on the header.
            $halfTax = (int)round(
                ($gross - $gross / (1 + $gstRate / 100)) / 2
            );

            $newItems[] = [
                $product['product_id'],
                $product['size_id'],
                $quantity,
                $price / 100,
                $gstRate,
                ($gross - 2 * $halfTax) / 100,
                $halfTax / 100,
                $halfTax / 100,
                $gross / 100
            ];

            $key = $product['product_id'] . ':' . $product['size_id'];

            $newQuantities[$key] =
                ($newQuantities[$key] ?? 0) + $quantity;

            $subtotal += $gross;
            $cgstTotal += $halfTax;
            $sgstTotal += $halfTax;
        }

        if (!$newItems || $subtotal > 9999999999) {
            throw new RuntimeException('Add items with a valid invoice total.');
        }

        $discount = invoiceEditMoney($_POST['discount'] ?? '');

        if ($discount > $subtotal) {
            throw new RuntimeException('Discount cannot exceed subtotal.');
        }

        $total = $subtotal - $discount;

        if (!is_array($_POST['payments'] ?? null)) {
            throw new RuntimeException('Invalid payment information.');
        }

        $newPayments = [];
        $paid = 0;

        foreach (['Cash', 'Card', 'UPI'] as $mode) {
            $amount = invoiceEditMoney($_POST['payments'][$mode] ?? '0');

            if ($amount > 0) {
                $newPayments[$mode] = $amount;
                $paid += $amount;
            }
        }

        if ($paid !== $total) {
            throw new RuntimeException(
                'Cash + Card + UPI must equal ' .
                    number_format($total / 100, 2)
            );
        }

        // Aggregate duplicate lines and lock affected stock consistently.
        $keys = array_unique(array_merge(
            array_keys($oldQuantities),
            array_keys($newQuantities)
        ));

        sort($keys, SORT_STRING);

        foreach ($keys as $key) {
            [$productId, $sizeId] = array_map('intval', explode(':', $key));

            // Positive difference restores stock; negative deducts stock.
            $difference =
                ($oldQuantities[$key] ?? 0) -
                ($newQuantities[$key] ?? 0);

            $stock = invoiceEditQuery(
                $conn,
                'SELECT quantity FROM product_stock
                 WHERE product_id = ? AND size_id = ?
                 FOR UPDATE',
                [$productId, $sizeId]
            )->get_result()->fetch_assoc();

            if (!$stock || (int)$stock['quantity'] + $difference < 0) {
                throw new RuntimeException(
                    'Insufficient stock or missing stock record: ' . $key
                );
            }

            if ($difference !== 0) {
                invoiceEditQuery(
                    $conn,
                    'UPDATE product_stock
                     SET quantity = quantity + ?
                     WHERE product_id = ? AND size_id = ?',
                    [$difference, $productId, $sizeId]
                );

                $recorded = recordStockTransaction(
                    $conn,
                    $productId,
                    $sizeId,
                    abs($difference),
                    $difference > 0 ? 'IN' : 'OUT',
                    'ADJUSTMENT',
                    $invoiceId,
                    'Invoice edited: ' . $invoiceNo
                );

                if (!$recorded) {
                    throw new RuntimeException(
                        'Could not record the stock adjustment.'
                    );
                }
            }
        }

        invoiceEditQuery(
            $conn,
            'DELETE FROM invoice_items WHERE invoice_id = ?',
            [$invoiceId]
        );

        foreach ($newItems as $item) {
            invoiceEditQuery(
                $conn,
                'INSERT INTO invoice_items
                 (invoice_id, product_id, size_id, quantity, mrp,
                  gst_rate, base_amount, cgst_amount, sgst_amount,
                  total_amount, discount_amount)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)',
                array_merge([$invoiceId], $item)
            );
        }

        invoiceEditQuery(
            $conn,
            'DELETE FROM invoice_payments WHERE invoice_id = ?',
            [$invoiceId]
        );

        foreach ($newPayments as $mode => $amount) {
            invoiceEditQuery(
                $conn,
                'INSERT INTO invoice_payments
                 (invoice_id, payment_mode, amount)
                 VALUES (?, ?, ?)',
                [$invoiceId, $mode, $amount / 100]
            );
        }

        $paymentMode = count($newPayments) > 1
            ? 'Split'
            : (array_key_first($newPayments) ?? 'Cash');

        invoiceEditQuery(
            $conn,
            'UPDATE invoices
             SET subtotal = ?, discount_amount = ?,
                 cgst_amount = ?, sgst_amount = ?,
                 total_amount = ?, payment_mode = ?
             WHERE id = ?',
            [
                $subtotal / 100,
                $discount / 100,
                $cgstTotal / 100,
                $sgstTotal / 100,
                $total / 100,
                $paymentMode,
                $invoiceId
            ]
        );

        $conn->commit();
        $inTransaction = false;

        setFlashMessage('success', 'Invoice updated and stock adjusted.');

        header('Location: view.php?invoice=' . rawurlencode($invoiceNo));
        exit;
    } catch (Throwable $e) {
        if ($inTransaction) {
            $conn->rollback();
        }

        error_log('Invoice editing failed: ' . $e->getMessage());

        $error = $e instanceof mysqli_sql_exception
            ? 'Database update failed. No changes were saved. Check the server log.'
            : $e->getMessage();
    }
}

$options = invoiceEditQuery(
    $conn,
    'SELECT ps.id, ps.product_id, ps.size_id, ps.quantity,
            p.product_code, p.product_name, s.size_name
     FROM product_stock ps
     JOIN products p ON p.id = ps.product_id
     JOIN sizes s ON s.id = ps.size_id
     ORDER BY p.product_code, s.size_name'
)->get_result()->fetch_all(MYSQLI_ASSOC);

$formItems = [];

foreach ($items as $item) {
    $stockId = '';

    foreach ($options as $option) {
        if (
            $option['product_id'] == $item['product_id'] &&
            $option['size_id'] == $item['size_id']
        ) {
            $stockId = $option['id'];
            break;
        }
    }

    $formItems[] = [
        'id' => $item['id'],
        'stock_id' => $stockId,
        'quantity' => $item['quantity'],
        'mrp' => $item['mrp']
    ];
}

$paymentValues = ['Cash' => 0, 'Card' => 0, 'UPI' => 0];

foreach ($payments as $payment) {
    if (array_key_exists($payment['payment_mode'], $paymentValues)) {
        $paymentValues[$payment['payment_mode']] += (float)$payment['amount'];
    }
}

$discountValue = number_format(
    max(
        (float)$invoice['discount_amount'],
        array_sum(array_column($items, 'discount_amount'))
    ),
    2,
    '.',
    ''
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (is_array($_POST['items'] ?? null)) {
        $formItems = array_values(array_filter($_POST['items'], 'is_array'));
    }

    if (is_scalar($_POST['discount'] ?? null)) {
        $discountValue = $_POST['discount'];
    }

    foreach ($paymentValues as $mode => $amount) {
        if (is_scalar($_POST['payments'][$mode] ?? null)) {
            $paymentValues[$mode] = $_POST['payments'][$mode];
        }
    }

    // Do not refresh a stale form's version automatically.
    $version = is_string($_POST['version'] ?? null)
        ? $_POST['version']
        : '';
}

$pageTitle = 'Edit Invoice';
require __DIR__ . '/../../includes/header.php';
?>

<h2>Edit Invoice <?= invoiceEditEscape($invoiceNo) ?></h2>

<p>
    Update items and enter the complete revised payment amounts.
    Product or size changes restore the original stock and deduct
    the replacement stock.
</p>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?= invoiceEditEscape($error) ?>
    </div>
<?php endif; ?>

<form method="post" id="invoice-edit-form">
    <input
        type="hidden"
        name="csrf"
        value="<?= invoiceEditEscape($_SESSION['invoice_edit_token']) ?>">
    <input
        type="hidden"
        name="version"
        value="<?= invoiceEditEscape($version) ?>">

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Product / Size / Current Stock</th>
                    <th>Quantity</th>
                    <th>Unit MRP including GST</th>
                    <th>Remove</th>
                </tr>
            </thead>

            <tbody id="invoice-edit-items">
                <?php foreach ($formItems as $index => $row): ?>
                    <tr>
                        <td>
                            <input
                                type="hidden"
                                data-field="id"
                                name="items[<?= $index ?>][id]"
                                value="<?= invoiceEditEscape($row['id'] ?? '') ?>">

                            <select
                                class="form-select"
                                data-field="stock_id"
                                name="items[<?= $index ?>][stock_id]"
                                required>
                                <option value="">Select product / size</option>

                                <?php foreach ($options as $option): ?>
                                    <option
                                        value="<?= (int)$option['id'] ?>"
                                        <?= (string)$option['id'] ===
                                            (string)($row['stock_id'] ?? '')
                                            ? 'selected' : '' ?>>
                                        <?= invoiceEditEscape(
                                            $option['product_code'] . ' — ' .
                                                $option['product_name'] . ' / ' .
                                                $option['size_name'] . ' / Stock: ' .
                                                $option['quantity']
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <td>
                            <input
                                class="form-control"
                                data-field="quantity"
                                type="number"
                                min="1"
                                max="1000000"
                                name="items[<?= $index ?>][quantity]"
                                value="<?= invoiceEditEscape($row['quantity'] ?? 1) ?>"
                                required>
                        </td>

                        <td>
                            <input
                                class="form-control"
                                data-field="mrp"
                                type="number"
                                min="0"
                                max="99999999.99"
                                step="0.01"
                                name="items[<?= $index ?>][mrp]"
                                value="<?= invoiceEditEscape($row['mrp'] ?? '') ?>"
                                required>
                        </td>

                        <td>
                            <input
                                data-field="remove"
                                type="checkbox"
                                name="items[<?= $index ?>][remove]"
                                value="1"
                                <?= !empty($row['remove']) ? 'checked' : '' ?>
                                aria-label="Remove item">
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <button type="button" id="add-invoice-item" class="btn btn-secondary">
        Add item
    </button>

    <div class="my-3">
        <label>
            Invoice discount amount
            <input
                class="form-control"
                type="number"
                name="discount"
                min="0"
                step="0.01"
                value="<?= invoiceEditEscape($discountValue) ?>"
                required>
        </label>
    </div>

    <p id="invoice-edit-total" aria-live="polite"></p>

    <?php foreach ($paymentValues as $mode => $amount): ?>
        <label class="me-3">
            <?= invoiceEditEscape($mode) ?>

            <input
                class="form-control"
                type="number"
                min="0"
                step="0.01"
                name="payments[<?= $mode ?>]"
                value="<?= invoiceEditEscape($amount) ?>"
                required>
        </label>
    <?php endforeach; ?>

    <div class="my-3">
        <button type="submit" class="btn btn-primary">
            Save invoice and adjust stock
        </button>

        <a
            class="btn btn-secondary"
            href="view.php?invoice=<?= invoiceEditEscape(rawurlencode($invoiceNo)) ?>">
            Cancel
        </a>
    </div>

    <input type="hidden" name="complete_form" value="1">
</form>

<script>
    const editForm = document.getElementById('invoice-edit-form');
    const editItems = document.getElementById('invoice-edit-items');
    let nextItemIndex = editItems.rows.length;

    function updateEditTotal() {
        let subtotal = 0;

        editItems.querySelectorAll('tr').forEach(row => {
            const removed = row.querySelector('[data-field="remove"]').checked;
            const quantity = row.querySelector('[data-field="quantity"]');
            const price = row.querySelector('[data-field="mrp"]');
            const product = row.querySelector('[data-field="stock_id"]');

            quantity.required = !removed;
            price.required = !removed;
            product.required = !removed;

            if (!removed) {
                subtotal +=
                    Math.round(Number(price.value) * 100) *
                    Number(quantity.value);
            }
        });

        const discount = Math.round(
            Number(editForm.elements.discount.value) * 100
        );

        document.getElementById('invoice-edit-total').textContent =
            'Subtotal: ' + (subtotal / 100).toFixed(2) +
            ' | New total: ' + ((subtotal - discount) / 100).toFixed(2);
    }

    document.getElementById('add-invoice-item').addEventListener('click', () => {
        const original = editItems.querySelector('tr');

        if (!original || editItems.rows.length >= 150) {
            return;
        }

        const row = original.cloneNode(true);

        row.querySelectorAll('[data-field]').forEach(input => {
            input.name = `items[${nextItemIndex}][${input.dataset.field}]`;

            switch (input.dataset.field) {
                case 'quantity':
                    input.value = '1';
                    break;
                case 'remove':
                    input.checked = false;
                    break;
                default:
                    input.value = '';
            }
        });

        nextItemIndex++;
        editItems.appendChild(row);
        updateEditTotal();
    });

    editForm.addEventListener('input', updateEditTotal);
    updateEditTotal();
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>