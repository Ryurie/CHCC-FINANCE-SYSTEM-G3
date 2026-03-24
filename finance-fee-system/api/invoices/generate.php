<?php
// api/invoices/generate.php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Siguraduhing admin o registrar lang ang pwedeng gumawa nito
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'registrar'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

$student_id = $data->student_id ?? 0;
$fee_id = $data->fee_id ?? 0;
$base_due_date = $data->due_date ?? '';
$payment_scheme = $data->payment_scheme ?? 'full'; // 'full' or 'installment'

if (!$student_id || !$fee_id || empty($base_due_date)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // 1. Kunin ang total amount ng Fee
    $feeStmt = $db->prepare("SELECT amount FROM fees WHERE id = :fee_id");
    $feeStmt->execute([':fee_id' => $fee_id]);
    $fee = $feeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$fee) {
        throw new Exception("Selected fee not found.");
    }

    $total_amount = $fee['amount'];

    // 2. I-save base sa piniling Payment Scheme
    if ($payment_scheme === 'installment') {
        // Hatiin sa 4 (Downpayment, Prelim, Midterm, Finals)
        $divided_amount = $total_amount / 4;
        
        $insertStmt = $db->prepare("INSERT INTO invoices (student_id, fee_id, amount_due, due_date, status, is_installment) 
                                    VALUES (:sid, :fid, :amt, :ddate, 'pending', TRUE)");

        // Gumawa ng 4 na magkakahiwalay na bills na may 1 buwan na pagitan
        for ($i = 0; $i < 4; $i++) {
            // Magdagdag ng buwan sa original due date para sa susunod na hulog
            $due_date = date('Y-m-d', strtotime("+$i months", strtotime($base_due_date)));
            
            $insertStmt->execute([
                ':sid' => $student_id,
                ':fid' => $fee_id,
                ':amt' => $divided_amount,
                ':ddate' => $due_date
            ]);
        }
    } else {
        // FULL PAYMENT - Isang bagsakang bill lang
        $insertStmt = $db->prepare("INSERT INTO invoices (student_id, fee_id, amount_due, due_date, status, is_installment) 
                                    VALUES (:sid, :fid, :amt, :ddate, 'pending', FALSE)");
        $insertStmt->execute([
            ':sid' => $student_id,
            ':fid' => $fee_id,
            ':amt' => $total_amount,
            ':ddate' => $base_due_date
        ]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Invoice(s) successfully generated!']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>