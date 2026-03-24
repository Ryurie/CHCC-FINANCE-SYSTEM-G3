<?php 
// views/admin/dashboard.php
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// DEFAULT VARIABLES (Para hindi mag-error kahit walang laman ang database)
$total_revenue = 0;
$total_receivables = 0;
$total_students = 0;
$active_scholarships = 0;
$recent_payments = [];

// 1. Total Revenue (Verified Payments)
try {
    $stmt1 = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'verified'");
    $total_revenue = $stmt1->fetchColumn() ?: 0;
} catch(PDOException $e) { /* Ignore lang kung wala pang table */ }

// 2. Total Receivables (Pending/Overdue Invoices)
try {
    $stmt2 = $db->query("SELECT SUM(amount_due) FROM invoices WHERE status != 'paid'");
    $total_receivables = $stmt2->fetchColumn() ?: 0;
} catch(PDOException $e) { }

// 3. Total Enrolled Students
try {
    $stmt3 = $db->query("SELECT COUNT(*) FROM students");
    $total_students = $stmt3->fetchColumn() ?: 0;
} catch(PDOException $e) { }

// 4. Active Scholarship Grants (Dito papasok yung in-add mong TES)
try {
    $stmt4 = $db->query("SELECT COUNT(*) FROM scholarships");
    $active_scholarships = $stmt4->fetchColumn() ?: 0;
} catch(PDOException $e) { }

// 5. Recent Transactions
try {
    $stmt5 = $db->query("SELECT p.id, p.amount, p.created_at, p.status, s.student_number, u.name as student_name 
                         FROM payments p 
                         JOIN students s ON p.student_id = s.id 
                         JOIN users u ON s.user_id = u.id 
                         ORDER BY p.created_at DESC LIMIT 5");
    $recent_payments = $stmt5->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) { }
?>

<style>
    .card-animate { transition: transform 0.3s ease, box-shadow 0.3s ease !important; }
    .card-animate:hover { transform: translateY(-5px) !important; box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important; }
    .btn-animate { transition: all 0.2s ease-in-out !important; display: inline-block; }
    .btn-animate:hover { transform: translateY(-2px) scale(1.03) !important; filter: brightness(1.1) !important; }
</style>

<div style="width: 100%; box-sizing: border-box; margin-top: 1rem;">

    <div class="dashboard-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="margin-bottom: 0.5rem; color: #1e293b; font-weight: 800;">Admin Dashboard</h1>
            <p style="color: #64748b; margin-top: 0;">Welcome back, System Administrator. Here's what's happening today.</p>
        </div>
        <a href="reports.php" class="btn btn-primary btn-animate" style="padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 8px; font-weight: bold; background-color: #3b82f6; color: white;">
            📊 View Full Reports
        </a>
    </div>

    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; width: 100%;">
        
        <div class="card card-animate" style="border-left: 4px solid #10b981; padding: 1.5rem; background: #fff; border-radius: 8px; cursor: pointer;">
            <h3 style="color: #64748b; font-size: 0.9rem; margin-top: 0; text-transform: uppercase; letter-spacing: 0.5px;">Total Revenue</h3>
            <p style="font-size: 2.2rem; font-weight: bold; margin: 0.5rem 0; color: #10b981;">
                ₱<?php echo number_format($total_revenue, 2); ?>
            </p>
            <small style="color: #94a3b8;">From verified payments</small>
        </div>

        <div class="card card-animate" style="border-left: 4px solid #f59e0b; padding: 1.5rem; background: #fff; border-radius: 8px; cursor: pointer;">
            <h3 style="color: #64748b; font-size: 0.9rem; margin-top: 0; text-transform: uppercase; letter-spacing: 0.5px;">Total Receivables</h3>
            <p style="font-size: 2.2rem; font-weight: bold; margin: 0.5rem 0; color: #f59e0b;">
                ₱<?php echo number_format($total_receivables, 2); ?>
            </p>
            <small style="color: #94a3b8;">Uncollected/Pending fees</small>
        </div>

        <div class="card card-animate" onclick="window.location.href='students.php'" style="border-left: 4px solid #3b82f6; padding: 1.5rem; background: #fff; border-radius: 8px; cursor: pointer;">
            <h3 style="color: #64748b; font-size: 0.9rem; margin-top: 0; text-transform: uppercase; letter-spacing: 0.5px;">Enrolled Students</h3>
            <p style="font-size: 2.2rem; font-weight: bold; margin: 0.5rem 0; color: #3b82f6;">
                <?php echo number_format($total_students); ?>
            </p>
            <small style="color: #94a3b8;">Active user accounts</small>
        </div>

        <div class="card card-animate" onclick="window.location.href='scholarships.php'" style="border-left: 4px solid #8b5cf6; padding: 1.5rem; background: #fff; border-radius: 8px; cursor: pointer;">
            <h3 style="color: #64748b; font-size: 0.9rem; margin-top: 0; text-transform: uppercase; letter-spacing: 0.5px;">Active Scholarships</h3>
            <p style="font-size: 2.2rem; font-weight: bold; margin: 0.5rem 0; color: #8b5cf6;">
                <?php echo number_format($active_scholarships); ?>
            </p>
            <small style="color: #94a3b8;">Available grants & discounts</small>
        </div>
    </div>

    <div class="card card-animate" style="padding: 1.5rem; background: #fff; border-radius: 8px; width: 100%; overflow-x: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.2rem; margin: 0; color: #1e293b;">Recent Transactions</h2>
            <a href="payments.php" class="btn-animate" style="font-size: 0.85rem; text-decoration: none; padding: 0.4rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 4px; color: #3b82f6; font-weight: bold;">View All</a>
        </div>
        
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; background-color: #f8fafc;">
                    <th style="padding: 1rem; color: #475569;">Transaction Date</th>
                    <th style="padding: 1rem; color: #475569;">Student</th>
                    <th style="padding: 1rem; color: #475569;">Amount Paid</th>
                    <th style="padding: 1rem; color: #475569;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_payments)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">
                            No recent transactions found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_payments as $pay): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                            <td style="padding: 1rem; color: #64748b; font-size: 0.9rem;">
                                <?php echo date('M d, Y h:i A', strtotime($pay['created_at'])); ?>
                            </td>
                            <td style="padding: 1rem;">
                                <div style="font-weight: bold; color: #1e293b;"><?php echo htmlspecialchars($pay['student_name']); ?></div>
                                <div style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($pay['student_number']); ?></div>
                            </td>
                            <td style="padding: 1rem; font-weight: bold; color: #10b981;">
                                ₱<?php echo number_format($pay['amount'], 2); ?>
                            </td>
                            <td style="padding: 1rem;">
                                <?php 
                                    $bg = '#fef3c7'; $color = '#92400e'; // pending
                                    if ($pay['status'] === 'verified') { $bg = '#dcfce3'; $color = '#166534'; }
                                    if ($pay['status'] === 'rejected') { $bg = '#fee2e2'; $color = '#991b1b'; }
                                ?>
                                <span style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase;">
                                    <?php echo $pay['status']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include '../layouts/footer.php'; ?>