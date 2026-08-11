<?php

ob_start();

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {

    $name = trim($_POST['name'] ?? '');

    if (strlen($name) < 2) {
        echo json_encode([
            'found' => false,
            'customers' => []
        ]);
        exit;
    }

    $conn = getDBConnection();

    if (!$conn) {
        throw new Exception('Database connection failed.');
    }

    $sql = "SELECT id, party_name, mobile, notes
            FROM parties
            WHERE party_name LIKE ?
            ORDER BY party_name, id
            LIMIT 20";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $search = '%' . $name . '%';

    if (!$stmt->bind_param('s', $search)) {
        throw new Exception('Bind parameter failed: ' . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    /*
     * Do NOT use $stmt->get_result()
     * because mysqlnd may not be available.
     */

    $stmt->store_result();

    $stmt->bind_result(
        $id,
        $party_name,
        $mobile,
        $notes
    );

    $customers = [];

    while ($stmt->fetch()) {

        $customers[] = [
            'id'     => (int) $id,
            'name'   => $party_name,
            'mobile' => $mobile,
            'notes'  => $notes
        ];
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'found' => !empty($customers),
        'customers' => $customers
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'found' => false,
        'customers' => [],
        'error' => $e->getMessage()
    ]);
}

exit;