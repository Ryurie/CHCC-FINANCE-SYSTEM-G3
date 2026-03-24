<?php
// models/Invoice.php

class Invoice {
    private $conn;
    private $table_name = "invoices";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Fetch invoices based on user role and user_id
    public function getInvoices($userId, $role) {
        $query = "";

        if ($role === 'student') {
            // Get only the student's invoices by linking users -> students -> invoices -> fees
            $query = "SELECT i.id, i.amount_due, i.due_date, i.status, f.name as fee_name, f.academic_year 
                      FROM " . $this->table_name . " i
                      JOIN fees f ON i.fee_id = f.id
                      JOIN students s ON i.student_id = s.id
                      WHERE s.user_id = :user_id
                      ORDER BY i.due_date ASC";
        } else {
            // Admins and Registrars see all invoices
            $query = "SELECT i.id, i.amount_due, i.due_date, i.status, f.name as fee_name, f.academic_year, s.student_number 
                      FROM " . $this->table_name . " i
                      JOIN fees f ON i.fee_id = f.id
                      JOIN students s ON i.student_id = s.id
                      ORDER BY i.due_date ASC";
        }

        $stmt = $this->conn->prepare($query);

        if ($role === 'student') {
            $stmt->bindParam(":user_id", $userId);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generate($student_id, $fee_id, $due_date) {
        // 1. Kunin muna ang amount ng fee mula sa fees table
        $feeQuery = "SELECT amount FROM fees WHERE id = :fee_id LIMIT 1";
        $stmtFee = $this->conn->prepare($feeQuery);
        $stmtFee->bindParam(':fee_id', $fee_id);
        $stmtFee->execute();
        $fee = $stmtFee->fetch(PDO::FETCH_ASSOC);

        if (!$fee) return false;

        // 2. I-insert ang bagong invoice gamit ang amount mula sa fee
        $query = "INSERT INTO " . $this->table_name . " 
                  SET student_id = :student_id, 
                      fee_id = :fee_id, 
                      amount_due = :amount, 
                      due_date = :due_date, 
                      status = 'pending'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':fee_id', $fee_id);
        $stmt->bindParam(':amount', $fee['amount']);
        $stmt->bindParam(':due_date', $due_date);

        return $stmt->execute();
    }
}
?>