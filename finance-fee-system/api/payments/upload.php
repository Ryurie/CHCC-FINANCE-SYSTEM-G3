<?php
// api/payments/upload.php
header("Content-Type: application/json");
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(["success" => false, "message" => "Unauthorized. Students only."]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Kukunin ang data mula sa $_POST dahil FormData ang ginamit sa frontend
$invoice_id = $_POST['invoice_id'] ?? null;
$student_id = $_POST['student_id'] ?? null;
$amount = $_POST['amount'] ?? null;

if ($invoice_id && $student_id && $amount && isset($_FILES['receipt'])) {
    
    $file = $_FILES['receipt'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["success" => false, "message" => "File upload failed. Error code: " . $file['error']]);
        exit;
    }

    // Set upload directory and create it if it doesn't exist
    $upload_dir = '../../uploads/receipts/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Generate a unique file name to prevent overriding (e.g., inv1_168432.jpg)
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'inv' . $invoice_id . '_' . time() . '.' . $file_extension;
    $target_file = $upload_dir . $new_filename;

    // Move the uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        
        try {
            // Save to payments table
            $query = "INSERT INTO payments (invoice_id, student_id, amount, proof_image, status) 
                      VALUES (:inv, :stu, :amt, :img, 'pending')";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':inv' => $invoice_id,
                ':stu' => $student_id,
                ':amt' => $amount,
                ':img' => $new_filename
            ]);

            echo json_encode(["success" => true, "message" => "Receipt uploaded successfully."]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
        }

    } else {
        echo json_encode(["success" => false, "message" => "Failed to move uploaded file to folder."]);
    }

} else {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Incomplete form data or missing file."]);
}
?>