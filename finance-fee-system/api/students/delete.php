<?php
// api/students/delete.php
session_start();
header('Content-Type: application/json');
require_once '../../config/database.php';

// Siguraduhing admin o registrar lang ang pwedeng magbura
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'registrar'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$student_id = $data->id ?? 0;

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // 1. Kunin ang user_id ng estudyante bago burahin
    $stmt = $db->prepare("SELECT user_id FROM students WHERE id = :id");
    $stmt->execute([':id' => $student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception("Student not found.");
    }

    // 2. Burahin ang student record
    $delStudent = $db->prepare("DELETE FROM students WHERE id = :id");
    $delStudent->execute([':id' => $student_id]);

    // 3. Burahin ang login account (user record) nila para walang kalat
    $delUser = $db->prepare("DELETE FROM users WHERE id = :uid");
    $delUser->execute([':uid' => $student['user_id']]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Student successfully removed.']);

} catch (PDOException $e) {
    $db->rollBack();
    // Kung mag-error, ibig sabihin may existing na Invoices o Payments yung bata.
    echo json_encode(['success' => false, 'message' => 'Cannot delete this student because they have existing invoices or payment records.']);
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>