<?php
// api/tickets/create.php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Student lang ang pwedeng gumawa nito
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$student_id = intval($data->student_id ?? 0);
$subject = trim($data->subject ?? '');
$message = trim($data->message ?? '');

if ($student_id <= 0 || empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("INSERT INTO tickets (student_id, subject, message, status) VALUES (:sid, :subject, :msg, 'open')");
    $stmt->execute([
        ':sid' => $student_id,
        ':subject' => $subject,
        ':msg' => $message
    ]);

    echo json_encode(['success' => true, 'message' => 'Your support ticket has been submitted! The Registrar will review it shortly.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>