<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../../login.php"); exit(); }
require '../../config/db_connect.php';

if (!isset($_GET['id'])) { die("Invalid Receipt ID."); }
$payment_id = intval($_GET['id']);

$query = "SELECT p.*, i.student_id, i.semester, i.fee_details, (i.total_amount + i.penalty) as grand_total 
          FROM payments p 
          JOIN invoices i ON p.invoice_id = i.invoice_id 
          WHERE p.payment_id = $payment_id";
$result = $conn->query($query);

if ($result->num_rows == 0) { die("Receipt not found."); }
$row = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - REC-<?php echo str_pad($row['payment_id'], 4, '0', STR_PAD_LEFT); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; padding: 20px; display: flex; justify-content: center; }
        .receipt-card { background: white; padding: 40px; width: 100%; max-width: 400px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); color: #111827; }
        .r-header { text-align: center; border-bottom: 2px dashed #e5e7eb; padding-bottom: 20px; margin-bottom: 20px; }
        .r-header h1 { margin: 0; font-size: 1.5rem; letter-spacing: 1px; }
        .r-header p { margin: 5px 0 0; color: #6b7280; font-size: 0.9rem; }
        .r-details { margin-bottom: 20px; font-size: 0.95rem; }
        .r-details p { margin: 8px 0; display: flex; justify-content: space-between; }
        .r-details span { font-weight: 600; }
        .r-amount { text-align: center; background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .r-amount h2 { margin: 0; font-size: 2.5rem; color: #10b981; }
        .r-amount p { margin: 5px 0 0; font-size: 0.85rem; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
        .r-footer { text-align: center; font-size: 0.85rem; color: #9ca3af; border-top: 2px dashed #e5e7eb; padding-top: 20px; }
        @media print {
            body { background: white; padding: 0; }
            .receipt-card { box-shadow: none; max-width: 100%; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt-card">
        <div class="r-header">
            <h1>CHCC Finance Management System</h1>
            <p>Official Official Receipt</p>
        </div>
        
        <div class="r-details">
            <p>Receipt No: <span>REC-<?php echo str_pad($row['payment_id'], 4, '0', STR_PAD_LEFT); ?></span></p>
            <p>Date: <span><?php echo date("F d, Y h:i A", strtotime($row['payment_date'])); ?></span></p>
            <p>Student ID: <span><?php echo htmlspecialchars($row['student_id']); ?></span></p>
            <p>Semester: <span><?php echo htmlspecialchars($row['semester']); ?></span></p>
            <p>Payment Method: <span><?php echo htmlspecialchars($row['payment_method']); ?></span></p>
            <p>Cashier: <span><?php echo htmlspecialchars($row['cashier_name'] ?? 'Admin'); ?></span></p>
        </div>

        <div class="r-amount">
            <h2>₱ <?php echo number_format($row['amount_paid'], 2); ?></h2>
            <p>Amount Paid</p>
        </div>

        <div class="r-footer">
            <p>This document serves as proof of payment.</p>
            <p>Thank you!</p>
        </div>
    </div>
</body>
</html>