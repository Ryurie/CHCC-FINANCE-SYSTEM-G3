<?php
// api/payments/update_status.php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Siguraduhing Admin o Registrar lang ang pwedeng mag-verify ng pera
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'registrar'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$payment_id = intval($data->id ?? 0);
$new_status = $data->status ?? '';

if ($payment_id <= 0 || !in_array($new_status, ['verified', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment data.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Umpisahan ang Transaction para sabay ang update sa Payments at Invoices
    $db->beginTransaction();

    // 1. Kunin muna kung anong Invoice ID ang binabayaran
    $stmt = $db->prepare("SELECT invoice_id FROM payments WHERE id = :id");
    $stmt->execute([':id' => $payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payment) {
        throw new Exception("Payment record not found.");
    }

    $invoice_id = $payment['invoice_id'];

    // 2. I-update ang status sa Payments table
    $updatePay = $db->prepare("UPDATE payments SET status = :status WHERE id = :id");
    $updatePay->execute([
        ':status' => $new_status,
        ':id' => $payment_id
    ]);

    // 3. Kung VERIFIED ang bayad, awtomatikong i-clear ang Invoice ng estudyante
    if ($new_status === 'verified') {
        $updateInv = $db->prepare("UPDATE invoices SET status = 'paid' WHERE id = :inv_id");
        $updateInv->execute([':inv_id' => $invoice_id]);
    } 
    // Kung REJECTED, ibabalik natin sa pending ang Invoice para alam ng bata na kailangan niya ulit magbayad
    else if ($new_status === 'rejected') {
        $updateInv = $db->prepare("UPDATE invoices SET status = 'pending' WHERE id = :inv_id");
        $updateInv->execute([':inv_id' => $invoice_id]);
    }

    // I-save lahat ng pagbabago sa database
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Payment status updated successfully.']);

} catch (Exception $e) {
    // Kung may nag-error, i-cancel ang lahat ng pagbabago (Rollback)
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
}
?>