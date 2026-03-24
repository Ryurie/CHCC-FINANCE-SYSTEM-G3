<?php
header("Content-Type: application/json");
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(["message" => "Unauthorized"]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

// 1. Total Collections (Verified Payments)
$q1 = "SELECT SUM(amount_paid) as total FROM payments WHERE status = 'verified'";
$res1 = $db->query($q1)->fetch(PDO::FETCH_ASSOC);

// 2. Outstanding Receivables (Pending Invoices)
$q2 = "SELECT SUM(amount_due) as total FROM invoices WHERE status != 'paid'";
$res2 = $db->query($q2)->fetch(PDO::FETCH_ASSOC);

// 3. Scholarship Total Deductions
$q3 = "SELECT SUM(amount_deducted) as total FROM scholarships";
$res3 = $db->query($q3)->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    "success" => true,
    "collections" => $res1['total'] ?? 0,
    "receivables" => $res2['total'] ?? 0,
    "scholarships" => $res3['total'] ?? 0
]);
?>