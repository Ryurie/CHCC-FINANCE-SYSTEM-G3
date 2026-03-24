<?php
// api/payments/receipt_upload.php
header("Content-Type: application/json");
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['receipt'])) {
    $targetDir = "../../public/uploads/receipts/";
    
    // Siguraduhin na exist ang folder
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileExtension = pathinfo($_FILES["receipt"]["name"], PATHINFO_EXTENSION);
    $fileName = "REC_" . time() . "_" . uniqid() . "." . $fileExtension;
    $targetFilePath = $targetDir . $fileName;

    // I-allow lang ang images
    $allowTypes = array('jpg', 'png', 'jpeg', 'pdf');
    if (in_array(strtolower($fileExtension), $allowTypes)) {
        if (move_uploaded_file($_FILES["receipt"]["tmp_name"], $targetFilePath)) {
            echo json_encode(["success" => true, "file_path" => $fileName]);
        } else {
            echo json_encode(["success" => false, "message" => "Error uploading file."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid file type."]);
    }
}
?>