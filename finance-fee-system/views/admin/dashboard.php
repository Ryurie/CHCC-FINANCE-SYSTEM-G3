<?php 
// views/admin/dashboard.php
session_start();
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// DEFAULT VARIABLES
$total_revenue = 0;
$total_receivables = 0;
$total_students = 0;
$active_scholarships = 0;
$recent_payments = [];

$schol_error = ""; $rev_error = ""; $rec_error = ""; $stud_error = "";

// 1. Total Revenue
try {
    $stmt1 = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'verified'");
    $total_revenue = $stmt1->fetchColumn() ?: 0;
} catch(PDOException $e) { $rev_error = $e->getMessage(); }

// 2. Total Receivables
try {
    $stmt2 = $db->query("SELECT SUM(amount_due) FROM invoices WHERE status != 'paid'");
    $total_receivables = $stmt2->fetchColumn() ?: 0;
} catch(PDOException $e) { $rec_error = $e->getMessage(); }

// 3. Total Students
try {
    $stmt3 = $db->query("SELECT COUNT(*) FROM students");
    $total_students = $stmt3->fetchColumn() ?: 0;
} catch(PDOException $e) { $stud_error = $e->getMessage(); }

// 4. Active Scholarships
try {
    $stmt4 = $db->query("SELECT COUNT(*) FROM scholarships");
    $active_scholarships = $stmt4->fetchColumn() ?: 0;
} catch(PDOException $e) { $active_scholarships = 0; $schol_error = $e->getMessage(); }

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
    /* Sleek Typography & Layout */
    .dashboard-wrapper { width: 100%; box-sizing: border-box; margin-top: 1rem; font-family: 'Inter', sans-serif; }
    
    /* Text Color Classes (Para madaling mag-Dark Mode) */
    .text-heading { color: #1e293b; }
    .text-sub { color: #64748b; }
    
    /* Modern Cards with Soft Shadows */
    .modern-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .modern-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        border-color: #e2e8f0;
    }

    /* Gradient Icons inside Cards */
    .card-icon-wrapper {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; justify-content: center; align-items: center;
        font-size: 1.5rem; flex-shrink: 0;
    }
    .icon-green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); }
    .icon-orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.3); }
    .icon-blue { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); }
    .icon-purple { background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%); color: white; box-shadow: 0 4px 10px rgba(139, 92, 246, 0.3); }

    /* Modern Buttons */
    .btn-modern {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; font-size: 0.9rem;
        transition: all 0.2s ease; cursor: pointer; text-decoration: none;
    }
    .btn-modern-primary { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
    .btn-modern-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 16px rgba(59, 130, 246, 0.4); filter: brightness(1.1); }
    
    .btn-modern-outline { background: transparent; color: #3b82f6; border: 1px solid #cbd5e1; padding: 0.5rem 1rem; font-size: 0.85rem; border-radius: 8px; }
    .btn-modern-outline:hover { background: #f8fafc; border-color: #3b82f6; }

    /* Sleek Table Design */
    .modern-table { width: 100%; border-collapse: separate; border-spacing: 0; text-align: left; }
    .modern-table th { padding: 1.2rem 1rem; color: #64748b; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; background: #f8fafc; font-weight: 700; }
    .modern-table td { padding: 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; transition: background 0.2s; }
    .modern-table tbody tr:hover td { background-color: #f8fafc; }
    .modern-table tbody tr:last-child td { border-bottom: none; }

    /* Modern Status Pills */
    .status-pill { padding: 0.35rem 0.8rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
    .status-verified { background: #dcfce3; color: #166534; border: 1px solid #bbf7d0; }
    .status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .status-rejected { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

    /* =========================================
       ✨ DARK MODE OVERRIDES
       ========================================= */
    body.dark-mode .modern-card { background: #1e293b; border-color: #334155; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2); }
    body.dark-mode .modern-card:hover { border-color: #475569; background: #233044; }
    
    /* Override Text Colors in Dark Mode */
    body.dark-mode .text-heading { color: #f8fafc !important; }
    body.dark-mode .text-sub { color: #94a3b8 !important; }
    body.dark-mode .modern-card .icon-label { color: #cbd5e1 !important; }

    /* Table Dark Mode */
    body.dark-mode .modern-table th { background: #0f172a; border-color: #334155; color: #38bdf8; }
    body.dark-mode .modern-table td { border-color: #334155; color: #e2e8f0; }
    body.dark-mode .modern-table tbody tr:hover td { background-color: #1e293b; }
    
    /* Buttons Dark Mode */
    body.dark-mode .btn-modern-outline { border-color: #475569; color: #60a5fa; }
    body.dark-mode .btn-modern-outline:hover { background: #334155; color: #f8fafc; }
    
    /* Badges Dark Mode */
    body.dark-mode .status-verified { background: rgba(22, 101, 52, 0.3); color: #4ade80; border-color: #166534; }
    body.dark-mode .status-pending { background: rgba(146, 64, 14, 0.3); color: #fbbf24; border-color: #92400e; }
    body.dark-mode .status-rejected { background: rgba(153, 27, 27, 0.3); color: #f87171; border-color: #991b1b; }
    
    /* Borders inside cards */
    body.dark-mode .card-footer-border { border-top-color: #334155 !important; }
    body.dark-mode .table-header-border { border-bottom-color: #334155 !important; }
</style>

<div class="dashboard-wrapper">

    <div style="margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <p class="text-sub" style="margin: 0 0 0.2rem 0; font-size: 0.9rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">
                <?php echo date('l, F j, Y'); ?>
            </p>
            <h1 class="text-heading" style="margin: 0; font-weight: 900; font-size: 2rem; letter-spacing: -0.5px;">Admin Overview</h1>
        </div>
        <a href="reports.php" class="btn-modern btn-modern-primary">
            <span>📊</span> Generate Reports
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem; width: 100%;">
        
        <div class="modern-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 class="text-sub" style="font-size: 0.85rem; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Total Revenue</h3>
                    <p class="text-heading" style="font-size: 2.2rem; font-weight: 900; margin: 0.5rem 0;">
                        ₱<?php echo number_format($total_revenue, 2); ?>
                    </p>
                </div>
                <div class="card-icon-wrapper icon-green">💰</div>
            </div>
            <div class="card-footer-border" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                <span class="status-verified" style="padding: 2px 6px; border-radius: 4px;">✔ Verified</span> 
                <span class="icon-label" style="color: #64748b;">Cleared payments</span>
            </div>
            <?php if($rev_error): ?><div style="color: red; font-size: 0.7rem; margin-top: 5px;">Error: <?php echo $rev_error; ?></div><?php endif; ?>
        </div>

        <div class="modern-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 class="text-sub" style="font-size: 0.85rem; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Receivables</h3>
                    <p class="text-heading" style="font-size: 2.2rem; font-weight: 900; margin: 0.5rem 0;">
                        ₱<?php echo number_format($total_receivables, 2); ?>
                    </p>
                </div>
                <div class="card-icon-wrapper icon-orange">⏳</div>
            </div>
            <div class="card-footer-border" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                <span class="status-pending" style="padding: 2px 6px; border-radius: 4px;">⚠ Pending</span> 
                <span class="icon-label" style="color: #64748b;">To be collected</span>
            </div>
            <?php if($rec_error): ?><div style="color: red; font-size: 0.7rem; margin-top: 5px;">Error: <?php echo $rec_error; ?></div><?php endif; ?>
        </div>

        <div class="modern-card" onclick="window.location.href='students.php'" style="cursor: pointer;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 class="text-sub" style="font-size: 0.85rem; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Enrolled Students</h3>
                    <p class="text-heading" style="font-size: 2.2rem; font-weight: 900; margin: 0.5rem 0;">
                        <?php echo number_format($total_students); ?>
                    </p>
                </div>
                <div class="card-icon-wrapper icon-blue">👨‍🎓</div>
            </div>
            <div class="card-footer-border" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; font-size: 0.8rem; color: #3b82f6; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                <span>View Directory →</span>
            </div>
            <?php if($stud_error): ?><div style="color: red; font-size: 0.7rem; margin-top: 5px;">Error: <?php echo $stud_error; ?></div><?php endif; ?>
        </div>

        <div class="modern-card" onclick="window.location.href='scholarships.php'" style="cursor: pointer;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <h3 class="text-sub" style="font-size: 0.85rem; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Active Grants</h3>
                    <p class="text-heading" style="font-size: 2.2rem; font-weight: 900; margin: 0.5rem 0;">
                        <?php echo number_format($active_scholarships); ?>
                    </p>
                </div>
                <div class="card-icon-wrapper icon-purple">📜</div>
            </div>
            <div class="card-footer-border" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; font-size: 0.8rem; color: #8b5cf6; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                <span>Manage Grants →</span>
            </div>
            <?php if(!empty($schol_error)): ?><div style="color: #ef4444; font-size: 0.75rem; font-weight: bold; margin-top: 8px;">⚠️ DB Error: <?php echo htmlspecialchars($schol_error); ?></div><?php endif; ?>
        </div>
    </div>

    <div class="modern-card" style="padding: 0;">
        <div class="table-header-border" style="padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9;">
            <h2 class="text-heading" style="font-size: 1.25rem; margin: 0; font-weight: 800;">Recent Transactions</h2>
            <a href="payments.php" class="btn-modern-outline">View All Records</a>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Student Details</th>
                        <th>Amount Paid</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_payments)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 4rem; color: #94a3b8; font-style: italic;">
                                No recent transactions found. Data will appear here once payments are made.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_payments as $pay): ?>
                            <tr>
                                <td>
                                    <div class="text-heading" style="font-weight: 600;"><?php echo date('M d, Y', strtotime($pay['created_at'])); ?></div>
                                    <div class="text-sub" style="font-size: 0.8rem;"><?php echo date('h:i A', strtotime($pay['created_at'])); ?></div>
                                </td>
                                <td>
                                    <div class="text-heading" style="font-weight: 700;"><?php echo htmlspecialchars($pay['student_name']); ?></div>
                                    <div class="text-sub" style="font-size: 0.8rem; font-family: monospace;">ID: <?php echo htmlspecialchars($pay['student_number']); ?></div>
                                </td>
                                <td style="font-weight: 800; color: #10b981; font-size: 1.1rem;">
                                    ₱<?php echo number_format($pay['amount'], 2); ?>
                                </td>
                                <td>
                                    <?php 
                                        $statusClass = 'status-pending';
                                        if ($pay['status'] === 'verified') $statusClass = 'status-verified';
                                        if ($pay['status'] === 'rejected') $statusClass = 'status-rejected';
                                    ?>
                                    <span class="status-pill <?php echo $statusClass; ?>">
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

</div>

<?php include '../layouts/footer.php'; ?>