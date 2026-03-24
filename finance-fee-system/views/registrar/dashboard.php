<?php 
// views/registrar/dashboard.php
session_start();
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// DEFAULT VARIABLES
$total_students = 0;
$total_receivables = 0;
$invoices_today = 0;
$recent_invoices = [];

try {
    $total_students = $db->query("SELECT COUNT(*) FROM students")->fetchColumn() ?: 0;
    $total_receivables = $db->query("SELECT SUM(amount_due) FROM invoices WHERE status != 'paid'")->fetchColumn() ?: 0;
    $invoices_today = $db->query("SELECT COUNT(*) FROM invoices WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;

    $stmt = $db->query("SELECT i.*, u.name as student_name, s.student_number, f.name as fee_name 
                        FROM invoices i 
                        JOIN students s ON i.student_id = s.id 
                        JOIN users u ON s.user_id = u.id 
                        JOIN fees f ON i.fee_id = f.id 
                        ORDER BY i.created_at DESC LIMIT 5");
    $recent_invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) { }
?>

<style>
    .dashboard-wrapper { width: 100%; box-sizing: border-box; margin-top: 1rem; }
    .modern-card {
        background: #ffffff; border-radius: 16px; padding: 1.5rem;
        border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    .modern-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
    .card-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; justify-content: center; align-items: center; font-size: 1.2rem; }
    .bg-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
    .bg-orange { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
    .bg-emerald { background: linear-gradient(135deg, #10b981, #059669); color: white; }
    
    .modern-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .modern-table th { padding: 1rem; background: #f8fafc; color: #64748b; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; border-bottom: 2px solid #e2e8f0; }
    .modern-table td { padding: 1rem; border-bottom: 1px solid #f1f5f9; }
    
    .status-pill { padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-paid { background: #dcfce3; color: #166534; }
    
    .btn-view { background: #f1f5f9; color: #3b82f6; padding: 0.4rem 0.8rem; border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 0.75rem; border: 1px solid #e2e8f0; }
    .btn-view:hover { background: #3b82f6; color: white; border-color: #3b82f6; }

    body.dark-mode .modern-card { background: #1e293b; border-color: #334155; }
    body.dark-mode .modern-table th { background: #0f172a; color: #94a3b8; border-color: #334155; }
    body.dark-mode .modern-table td { color: #e2e8f0; border-color: #334155; }
    body.dark-mode .btn-view { background: #334155; border-color: #475569; }
</style>

<div class="dashboard-wrapper">
    <div style="margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="margin: 0; font-weight: 900; font-size: 2rem;">Registrar Dashboard</h1>
            <p style="color: #64748b; margin: 0.2rem 0 0 0;">Quick overview of billing and student accounts.</p>
        </div>
        <a href="invoices.php" style="background: #3b82f6; color: white; padding: 0.8rem 1.5rem; border-radius: 10px; text-decoration: none; font-weight: 700;">🧾 Manage Invoices</a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        <div class="modern-card">
            <div style="display: flex; justify-content: space-between;">
                <div><span style="color: #64748b; font-weight: 700; font-size: 0.8rem;">RECEIVABLES</span><h2 style="margin: 5px 0; font-size: 1.8rem;">₱<?php echo number_format($total_receivables, 2); ?></h2></div>
                <div class="card-icon bg-orange">⏳</div>
            </div>
        </div>
        <div class="modern-card">
            <div style="display: flex; justify-content: space-between;">
                <div><span style="color: #64748b; font-weight: 700; font-size: 0.8rem;">STUDENTS</span><h2 style="margin: 5px 0; font-size: 1.8rem;"><?php echo $total_students; ?></h2></div>
                <div class="card-icon bg-blue">👨‍🎓</div>
            </div>
        </div>
        <div class="modern-card">
            <div style="display: flex; justify-content: space-between;">
                <div><span style="color: #64748b; font-weight: 700; font-size: 0.8rem;">ISSUED TODAY</span><h2 style="margin: 5px 0; font-size: 1.8rem;"><?php echo $invoices_today; ?></h2></div>
                <div class="card-icon bg-emerald">📝</div>
            </div>
        </div>
    </div>

    <div class="modern-card" style="padding: 0;">
        <div style="padding: 1.5rem; border-bottom: 1px solid #f1f5f9; font-weight: 800;">Recent Invoices</div>
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Student</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_invoices as $inv): ?>
                        <tr>
                            <td style="font-weight: 700; color: #64748b;">#<?php echo str_pad($inv['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td><div style="font-weight: 700;"><?php echo htmlspecialchars($inv['student_name']); ?></div></td>
                            <td style="font-weight: 800; color: #ef4444;">₱<?php echo number_format($inv['amount_due'], 2); ?></td>
                            <td><span class="status-pill <?php echo $inv['status'] == 'paid' ? 'status-paid' : 'status-pending'; ?>"><?php echo $inv['status']; ?></span></td>
                            <td style="text-align: center;">
                                <a href="view_invoice.php?id=<?php echo $inv['id']; ?>" target="_blank" class="btn-view">📄 View PDF</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>