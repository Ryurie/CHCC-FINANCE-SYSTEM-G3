<?php
// api/fees/delete.php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$id = intval($data->id ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid fee ID.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("DELETE FROM fees WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true, 'message' => 'Fee category deleted successfully.']);
} catch (PDOException $e) {
    // Error 1452 / 1451: Foreign Key Constraint (Ibig sabihin, nagamit na ang fee na ito sa invoices)
    if ($e->getCode() == '23000') {
        echo json_encode(['success' => false, 'message' => 'Cannot delete this fee. It is already being used in active student invoices or payments.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>