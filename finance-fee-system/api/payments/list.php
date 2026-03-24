<?php
// api/payments/list.php
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

try {
    // Kinukuha natin ang detalye ng payment kasama ang pangalan ng student at invoice ID
    $query = "SELECT p.id, p.invoice_id, p.amount, p.proof_image, p.status, p.created_at as date_submitted,
                     s.student_number, u.name as student_name
              FROM payments p
              JOIN students s ON p.student_id = s.id
              JOIN users u ON s.user_id = u.id
              ORDER BY p.status = 'pending' DESC, p.created_at DESC"; 
              // Inuna natin ang 'pending' para laging nasa taas ang kailangang asikasuhin
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // I-format ang date para mas magandang basahin
    foreach ($payments as &$pay) {
        $pay['date_submitted'] = date('M d, Y h:i A', strtotime($pay['date_submitted']));
    }
    
    echo json_encode(["success" => true, "data" => $payments]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
}
?>