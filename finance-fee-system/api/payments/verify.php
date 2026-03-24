<?php
// api/payments/verify.php
header("Content-Type: application/json");
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!in_array($_SESSION['role'], ['admin', 'registrar'])) {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['payment_id']) && !empty($data['invoice_id']) && !empty($data['status'])) {
    try {
        // Simulan ang transaction para safe ang update
        $db->beginTransaction();

        // 1. I-update ang Payment Table
        $payQuery = "UPDATE payments SET status = :status WHERE id = :payment_id";
        $payStmt = $db->prepare($payQuery);
        $payStmt->execute([
            ':status' => $data['status'],
            ':payment_id' => $data['payment_id']
        ]);

        // 2. I-update ang Invoice Table KUNG verified ang payment
        if ($data['status'] === 'verified') {
            $invQuery = "UPDATE invoices SET status = 'paid' WHERE id = :invoice_id";
            $invStmt = $db->prepare($invQuery);
            $invStmt->execute([':invoice_id' => $data['invoice_id']]);
        } else if ($data['status'] === 'rejected') {
            // Kung na-reject, ibabalik natin sa pending ang invoice
            $invQuery = "UPDATE invoices SET status = 'pending' WHERE id = :invoice_id";
            $invStmt = $db->prepare($invQuery);
            $invStmt->execute([':invoice_id' => $data['invoice_id']]);
        }

        // I-save ang mga pagbabago
        $db->commit();
        echo json_encode(["success" => true, "message" => "Payment status updated."]);

    } catch (Exception $e) {
        // Kung may nag-error, i-rollback (i-cancel) ang lahat ng update
        $db->rollBack();
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Incomplete data provided."]);
}
?>