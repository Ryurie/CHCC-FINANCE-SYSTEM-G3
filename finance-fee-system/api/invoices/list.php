<?php
// api/invoices/list.php
header("Content-Type: application/json");
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Gumagamit tayo ng JOIN para makuha ang kumpletong detalye
    $query = "SELECT i.id, i.amount_due, i.due_date, i.status, 
                     s.student_number, f.name as fee_name 
              FROM invoices i
              LEFT JOIN students s ON i.student_id = s.id
              LEFT JOIN fees f ON i.fee_id = f.id
              ORDER BY i.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "data" => $invoices]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
}
?>