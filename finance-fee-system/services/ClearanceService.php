<?php
// services/ClearanceService.php

class ClearanceService {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function updateStudentClearance($student_id) {
        // 1. Bilangin kung may hindi pa bayad na invoices
        $query = "SELECT COUNT(*) as unpaid FROM invoices 
                  WHERE student_id = :student_id AND status != 'paid'";
        $stmt = $this->db->prepare($query);
        $stmt->execute([':student_id' => $student_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Kung 0 ang unpaid, set to 'cleared'. Kung hindi, 'hold' or 'pending'.
        $new_status = ($result['unpaid'] == 0) ? 'cleared' : 'hold';

        $updateQuery = "UPDATE students SET clearance_status = :status WHERE id = :student_id";
        $this->db->prepare($updateQuery)->execute([
            ':status' => $new_status,
            ':student_id' => $student_id
        ]);

        return $new_status;
    }
}
?>