<?php
// models/Payment.php

class Payment {
    private $conn;
    private $table_name = "payments";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET invoice_id = :invoice_id, 
                      amount_paid = :amount_paid, 
                      payment_method = :payment_method, 
                      reference_number = :reference_number, 
                      status = 'pending'";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':invoice_id', $data['invoice_id']);
        $stmt->bindParam(':amount_paid', $data['amount_paid']);
        $stmt->bindParam(':payment_method', $data['payment_method']);
        $stmt->bindParam(':reference_number', $data['reference_number']);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
}
?>