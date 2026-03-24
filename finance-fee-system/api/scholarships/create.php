<?php
// api/scholarships/create.php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Admin lang ang pwedeng gumawa ng Scholarship
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$name = trim($data->name ?? '');
$discount = floatval($data->discount_percentage ?? 0);
$description = trim($data->description ?? '');

if (empty($name) || $discount <= 0 || $discount > 100) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid scholarship name and a discount percentage between 1 and 100.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("INSERT INTO scholarships (name, description, discount_percentage) VALUES (:name, :description, :discount)");
    $stmt->execute([
        ':name' => $name,
        ':description' => $description,
        ':discount' => $discount
    ]);

    echo json_encode(['success' => true, 'message' => 'New scholarship grant successfully added!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>