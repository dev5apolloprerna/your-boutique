<?php
ob_start();
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$mobile = $_REQUESI['mobile'] ?? '';

if (empty($mobile) || strlen($mobile) != 10) {
    echo json_encode(['found' => false]);
    exit();
}

$conn = getDBConnection();

echo $sql = "SELECT * FROM parties WHERE mobile = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $mobile);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $party = $result->fetch_assoc();
    echo json_encode([
        'found' => true,
        'id' => $party['id'],
        'name' => $party['party_name'],
        'address' => $party['address'],
        'notes' => $party['notes']
    ]);
} else {
    echo json_encode(['found' => false]);
}

$conn->close();
?>
