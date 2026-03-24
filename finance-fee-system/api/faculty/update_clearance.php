<?php
// api/faculty/update_clearance.php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Faculty lang dapat ang pwedeng mag-update nito
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$clearance_id = $data->id ?? 0;
$status = $data->status ?? '';
$remarks = $data->remarks ?? '';

if (!$clearance_id || !in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("UPDATE clearances SET status = :status, remarks = :remarks WHERE id = :id");
    $stmt->execute([
        ':status' => $status,
        ':remarks' => $remarks,
        ':id' => $clearance_id
    ]);

    echo json_encode(['success' => true, 'message' => 'Clearance updated successfully.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>