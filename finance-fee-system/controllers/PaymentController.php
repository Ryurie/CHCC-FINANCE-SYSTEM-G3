<?php
// controllers/PaymentController.php
require_once dirname(__DIR__) . '/models/Payment.php';

class PaymentController {
    private $db;
    private $paymentModel;

    public function __construct($db) {
        $this->db = $db;
        $this->paymentModel = new Payment($db);
    }

    public function processPayment($data) {
        $paymentId = $this->paymentModel->create($data);

        if ($paymentId) {
            // Logic: Update Invoice status after payment
            $this->updateInvoiceStatus($data['invoice_id']);
            
            return [
                "success" => true,
                "message" => "Payment submitted and is now pending verification.",
                "payment_id" => $paymentId
            ];
        }

        return ["success" => false, "message" => "Could not record payment."];
    }

    private function updateInvoiceStatus($invoiceId) {
        // Kunin ang total amount due vs total amount paid
        $query = "SELECT 
                    (SELECT amount_due FROM invoices WHERE id = :id) as due,
                    (SELECT SUM(amount_paid) FROM payments WHERE invoice_id = :id AND status != 'rejected') as paid";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $invoiceId);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $status = 'pending';
        if ($row['paid'] >= $row['due']) {
            $status = 'paid';
        } elseif ($row['paid'] > 0) {
            $status = 'partial';
        }

        $updateQuery = "UPDATE invoices SET status = :status WHERE id = :id";
        $updStmt = $this->db->prepare($updateQuery);
        $updStmt->bindParam(':status', $status);
        $updStmt->bindParam(':id', $invoiceId);
        $updStmt->execute();
    }
}
?>