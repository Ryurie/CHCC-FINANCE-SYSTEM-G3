<?php
// api/scholarships/delete.php
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
    echo json_encode(['success' => false, 'message' => 'Invalid scholarship ID.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("DELETE FROM scholarships WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['success' => true, 'message' => 'Scholarship grant deleted successfully.']);
} catch (PDOException $e) {
    // Kung nagamit na sa estudyante, bawal burahin
    if ($e->getCode() == '23000') {
        echo json_encode(['success' => false, 'message' => 'Cannot delete this grant. It is currently applied to active student accounts.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>