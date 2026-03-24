<?php
// api/auth/login.php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Kunin ang data na ipinasa mula sa Login Form (UI)
$data = json_decode(file_get_contents("php://input"));
$username = trim($data->username ?? '');
$password = trim($data->password ?? '');

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please enter both your email/ID and password.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // SMART LOGIN: Hahanapin natin kung nag-match sa Email (users table) o sa Student Number (students table)
    $query = "SELECT u.id, u.name, u.password, u.role 
              FROM users u 
              LEFT JOIN students s ON u.id = s.user_id 
              WHERE u.email = :username OR s.student_number = :username 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $is_password_correct = false;
        
        // CHECK 1: Kung gumagamit kayo ng secure hashed passwords (Standard)
        if (password_verify($password, $user['password'])) {
            $is_password_correct = true;
        } 
        // CHECK 2: Fallback kung plain text lang ang naka-save sa database ninyo para sa school project
        else if ($password === $user['password']) { 
            $is_password_correct = true;
        }

        if ($is_password_correct) {
            // I-save ang login details sa Session para magamit sa buong system
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];

            // Ipadala ang success message pabalik sa Login Form para makapag-redirect
            echo json_encode([
                'success' => true, 
                'message' => 'Authentication successful!',
                'role' => $user['role']
            ]);
        } else {
            // Tama ang email/ID pero mali ang password
            echo json_encode(['success' => false, 'message' => 'Incorrect password. Please try again.']);
        }
    } else {
        // Walang nahanap na record
        echo json_encode(['success' => false, 'message' => 'Account not found. Check your email or Student ID.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'System error connecting to database.']);
}
?>