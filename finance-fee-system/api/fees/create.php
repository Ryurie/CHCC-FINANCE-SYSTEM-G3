<?php
// api/fees/create.php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Admin lang ang pwedeng gumawa ng Fee
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$name = trim($data->name ?? '');
$amount = floatval($data->amount ?? 0);
$description = trim($data->description ?? '');

if (empty($name) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid fee name and amount.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("INSERT INTO fees (name, description, amount) VALUES (:name, :description, :amount)");
    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':amount' => $amount
    ]);

    echo json_encode(['success' => true, 'message' => 'New fee category successfully added!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>