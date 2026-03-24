<?php
// controllers/AuthController.php
require_once dirname(__DIR__) . '/models/User.php';

class AuthController {
    private $db;
    private $userModel;

    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new User($db);
    }

    public function login($email, $password) {
        $user = $this->userModel->findByEmail($email);

        // Verify user exists and password is correct. 
        // Note: We use password_verify() assuming passwords are hashed in the DB using password_hash()
        if ($user && password_verify($password, $user['password'])) {
            
            // Start session and store core user data
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            return [
                "success" => true,
                "message" => "Login successful.",
                "user" => [
                    "id" => $user['id'],
                    "name" => $user['name'],
                    "role" => $user['role']
                ]
            ];
        }

        return [
            "success" => false,
            "message" => "Invalid email or password."
        ];
    }
}
?>