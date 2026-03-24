<?php
// controllers/ScholarshipController.php

class ScholarshipController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function applyScholarship($student_id, $scholarship_name, $discount_percent) {
        try {
            $this->db->beginTransaction();

            // 1. Hanapin ang Tuition Fee invoice ng estudyante (kadalasan dito ina-apply ang discount)
            $query = "SELECT id, amount_due FROM invoices 
                      WHERE student_id = :student_id AND status != 'paid' 
                      ORDER BY amount_due DESC LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':student_id' => $student_id]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($invoice) {
                $deduction = $invoice['amount_due'] * ($discount_percent / 100);
                $new_balance = $invoice['amount_due'] - $deduction;

                // 2. I-update ang Invoice amount
                $updateInvoice = "UPDATE invoices SET amount_due = :new_balance WHERE id = :invoice_id";
                $this->db->prepare($updateInvoice)->execute([
                    ':new_balance' => $new_balance,
                    ':invoice_id' => $invoice['id']
                ]);

                // 3. I-record ang scholarship entry
                $recordScholarship = "INSERT INTO scholarships (student_id, name, discount_percentage, amount_deducted) 
                                     VALUES (:sid, :name, :percent, :deducted)";
                $this->db->prepare($recordScholarship)->execute([
                    ':sid' => $student_id,
                    ':name' => $scholarship_name,
                    ':percent' => $discount_percent,
                    ':deducted' => $deduction
                ]);
            }

            $this->db->commit();
            return ["success" => true, "message" => "Scholarship applied successfully."];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
}
?>