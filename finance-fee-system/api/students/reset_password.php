<?php
header("Content-Type: application/json");
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!in_array($_SESSION['role'], ['registrar', 'admin'])) {
    http_response_code(403);
    exit(json_encode(["success" => false, "message" => "Unauthorized"]));
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['user_id'])) {
    $new_password = password_hash("password", PASSWORD_BCRYPT);
    $query = "UPDATE users SET password = :password WHERE id = :id";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute([':password' => $new_password, ':id' => $data['user_id']])) {
        echo json_encode(["success" => true, "message" => "Password reset to 'password'."]);
    } else {
        echo json_encode(["success" => false, "message" => "Reset failed."]);
    }
}
?>