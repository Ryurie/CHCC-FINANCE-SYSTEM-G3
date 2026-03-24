<?php
// api/clearance/update_status.php
header("Content-Type: application/json");
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Security Check: Faculty or Admin only
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['faculty', 'admin'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['student_id']) && !empty($data['status'])) {
    // I-update ang clearance_status sa students table
    // Note: Sa mas complex na system, may hiwalay na table para sa 'academic_clearance' 
    // pero para sa version na ito, i-uupdate natin ang main status.
    
    $query = "UPDATE students SET clearance_status = :status WHERE id = :student_id";
    $stmt = $db->prepare($query);
    
    $stmt->bindParam(':status', $data['status']);
    $stmt->bindParam(':student_id', $data['student_id']);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Clearance status updated to " . $data['status']]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database update failed."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Incomplete data."]);
}
?>