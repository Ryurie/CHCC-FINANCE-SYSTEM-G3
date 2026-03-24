<?php
// api/payments/create.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once '../../config/database.php';
require_once '../../controllers/PaymentController.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$controller = new PaymentController($db);

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['invoice_id']) && !empty($data['amount_paid']) && !empty($data['payment_method'])) {
    $response = $controller->processPayment($data);
    echo json_encode($response);
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Incomplete payment data."]);
}
?>