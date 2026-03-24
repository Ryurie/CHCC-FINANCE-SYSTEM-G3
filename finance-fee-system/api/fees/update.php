<?php
// api/fees/update.php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$id = intval($data->id ?? 0);
$name = trim($data->name ?? '');
$amount = floatval($data->amount ?? 0);
$description = trim($data->description ?? '');

if ($id <= 0 || empty($name) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please provide valid details.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("UPDATE fees SET name = :name, description = :description, amount = :amount WHERE id = :id");
    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':amount' => $amount,
        ':id' => $id
    ]);

    echo json_encode(['success' => true, 'message' => 'Fee category successfully updated!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>