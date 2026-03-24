<?php
// models/User.php

class User {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Fetch user by email for authentication
    public function findByEmail($email) {
        $query = "SELECT id, name, email, password, role FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function register($name, $email, $password) {
        // I-hash ang password bago i-save
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name = :name, email = :email, password = :password, role = 'student'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);

        return $stmt->execute();
    }
}
?>