<?php
require_once '../config/database.php';

class InvoiceService {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function generateInvoice($student_id, $fee_id, $due_date) {
        // 1. Get Fee details
        $feeQuery = "SELECT amount FROM fees WHERE id = ?";
        $stmt = $this->conn->prepare($feeQuery);
        $stmt->execute([$fee_id]);
        $fee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$fee) return false;
        
        $base_amount = $fee['amount'];

        // 2. Check for active scholarships for this student
        $scholarQuery = "SELECT SUM(discount_amount) as total_discount FROM scholarships WHERE student_id = ?";
        $stmt = $this->conn->prepare($scholarQuery);
        $stmt->execute([$student_id]);
        $scholarship = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $discount = $scholarship['total_discount'] ? $scholarship['total_discount'] : 0.00;
        
        // 3. Calculate Net Amount
        $net_amount = max(0, $base_amount - $discount); // Prevents negative invoices

        // 4. Insert Invoice
        $insertQuery = "INSERT INTO invoices (student_id, fee_id, total_amount, discount_applied, net_amount, due_date) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($insertQuery);
        
        return $stmt->execute([$student_id, $fee_id, $base_amount, $discount, $net_amount, $due_date]);
    }
}
?>