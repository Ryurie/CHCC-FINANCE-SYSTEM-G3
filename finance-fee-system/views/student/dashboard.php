<?php 
// views/student/dashboard.php
session_start();
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$unpaid_balance = 0;
try {
    $unpaid_balance = $db->query("SELECT SUM(amount_due) FROM invoices i 
                                  JOIN students s ON i.student_id = s.id 
                                  WHERE s.user_id = $user_id AND i.status != 'paid'")->fetchColumn() ?: 0;
} catch(PDOException $e) { }
?>

<style>
    .student-hero { 
        background: linear-gradient(135deg, #3b82f6, #8b5cf6); 
        padding: 2.5rem; border-radius: 24px; color: white; 
        box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);
        margin-bottom: 2rem;
    }
    .quick-card { background: #fff; padding: 1.5rem; border-radius: 16px; border: 1px solid #f1f5f9; transition: 0.3s; text-decoration: none; color: inherit; }
    .quick-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px rgba(0,0,0,0.05); }
    
    body.dark-mode .quick-card { background: #1e293b; border-color: #334155; }
</style>

<div style="width: 100%; margin-top: 1rem;">
    <div class="student-hero">
        <h3 style="margin: 0; opacity: 0.9; font-weight: 400;">Welcome back, Student!</h3>
        <h1 style="margin: 0.5rem 0; font-size: 2.5rem; font-weight: 900;">₱<?php echo number_format($unpaid_balance, 2); ?></h1>
        <p style="margin: 0; opacity: 0.8; font-weight: 600;">Current Outstanding Balance</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
        <a href="history.php" class="quick-card">
            <span style="font-size: 1.5rem;">📜</span>
            <h4 style="margin: 0.5rem 0 0 0;">Payment History</h4>
            <p style="font-size: 0.75rem; color: #64748b;">View all your past transactions</p>
        </a>
        <a href="support.php" class="quick-card">
            <span style="font-size: 1.5rem;">💬</span>
            <h4 style="margin: 0.5rem 0 0 0;">Help & Support</h4>
            <p style="font-size: 0.75rem; color: #64748b;">Submit tickets for billing issues</p>
        </a>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>