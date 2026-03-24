<?php
header("Content-Type: application/json");
require_once '../../config/database.php';
require_once '../../models/User.php';

$database = new Database();
$db = $database->getConnection();
$userModel = new User($db);

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['name']) && !empty($data['email']) && !empty($data['password'])) {
    // I-check muna kung existing na ang email
    if ($userModel->findByEmail($data['email'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Email already registered."]);
        exit;
    }

    if ($userModel->register($data['name'], $data['email'], $data['password'])) {
        echo json_encode(["success" => true, "message" => "Account created successfully."]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Registration failed."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Incomplete data."]);
}
?>