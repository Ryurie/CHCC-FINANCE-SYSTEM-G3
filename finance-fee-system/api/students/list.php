<?php
header("Content-Type: application/json");
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
// Security: Registrar and Admin only
if (!in_array($_SESSION['role'], ['registrar', 'admin'])) {
    http_response_code(403);
    echo json_encode(["message" => "Forbidden"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT s.id, u.name, u.email, s.student_number, s.course, s.year_level, s.clearance_status 
          FROM students s 
          JOIN users u ON s.user_id = u.id 
          ORDER BY u.name ASC";

$stmt = $db->prepare($query);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(["success" => true, "data" => $students]);
?>