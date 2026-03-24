<?php
// api/payments/mock_pay.php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$invoice_id = $data->invoice_id ?? 0;
$amount_paid = $data->amount ?? 0;
$student_id = $data->student_id ?? 0;

if ($invoice_id <= 0 || $amount_paid <= 0 || $student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment details.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // 1. Kunin ang amount due ng invoice
    $stmt = $db->prepare("SELECT amount_due FROM invoices WHERE id = :id");
    $stmt->execute([':id' => $invoice_id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        throw new Exception("Invoice not found.");
    }

    $amount_due = $invoice['amount_due'];
    $excess = 0;

    // 2. Kung sumobra ang bayad, ilagay sa Wallet
    if ($amount_paid > $amount_due) {
        $excess = $amount_paid - $amount_due;
        
        $walletStmt = $db->prepare("UPDATE students SET wallet_balance = wallet_balance + :excess WHERE id = :sid");
        $walletStmt->execute([':excess' => $excess, ':sid' => $student_id]);
        
        // Ang i-re-record lang natin na bayad sa resibo ay yung saktong amount due
        $amount_paid = $amount_due; 
    }

    // 3. I-save ang payment (Awtomatikong 'verified' kasi e-Payment)
    $payStmt = $db->prepare("INSERT INTO payments (student_id, invoice_id, fee_id, amount, status, created_at) 
                             SELECT :sid, :inv_id, fee_id, :amt, 'verified', NOW() 
                             FROM invoices WHERE id = :inv_id");
    $payStmt->execute([
        ':sid' => $student_id,
        ':inv_id' => $invoice_id,
        ':amt' => $amount_paid
    ]);

    // 4. I-update ang Invoice status to 'paid'
    $updateInv = $db->prepare("UPDATE invoices SET status = 'paid' WHERE id = :id");
    $updateInv->execute([':id' => $invoice_id]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Payment successful!', 'excess' => $excess]);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>