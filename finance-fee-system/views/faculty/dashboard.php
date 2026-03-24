<?php 
// views/faculty/dashboard.php
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

// I-setup ang mga default variables (0 lahat sa simula)
$pending_count = 0;
$approved_today = 0;
$total_cleared = 0;
$recent_requests = [];

try {
    // 1. Kunin ang bilang ng mga PENDING
    $stmt1 = $db->query("SELECT COUNT(*) FROM clearances WHERE status = 'pending'");
    $pending_count = $stmt1->fetchColumn();

    // 2. Kunin ang bilang ng APPROVED NGAYONG ARAW
    $stmt2 = $db->query("SELECT COUNT(*) FROM clearances WHERE status = 'approved' AND DATE(updated_at) = CURDATE()");
    $approved_today = $stmt2->fetchColumn();

    // 3. Kunin ang TOTAL na nabigyan ng clearance
    $stmt3 = $db->query("SELECT COUNT(*) FROM clearances WHERE status = 'approved'");
    $total_cleared = $stmt3->fetchColumn();

    // 4. Kunin ang 5 pinakabagong pending request para sa table
    $stmt4 = $db->query("SELECT c.id, c.status, c.created_at, s.student_number, u.name as student_name 
                         FROM clearances c 
                         JOIN students s ON c.student_id = s.id 
                         JOIN users u ON s.user_id = u.id 
                         WHERE c.status = 'pending' 
                         ORDER BY c.created_at ASC LIMIT 5");
    $recent_requests = $stmt4->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    // Kung sakaling wala pang table, hindi mag-ca-crash ang system
    $error_msg = "Please ensure the clearances table is set up in the database.";
}
?>

<div style="width: 100%; box-sizing: border-box; margin-top: 1rem;">

    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem; color: #1e293b;">CHCC Faculty Dashboard</h1>
        <p style="color: #64748b; margin-top: 0;">Welcome, CHCC Faculty! Manage academic clearances for student enrollment and billing.</p>
    </div>

    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; width: 100%;">
        
        <div class="card" style="border-left: 4px solid #f59e0b; padding: 1.5rem; background: #fff; border-radius: 8px; box-shadow: var(--shadow);">
            <h3 style="color: #64748b; font-size: 0.9rem; margin-top: 0;">Pending Requests</h3>
            <p style="font-size: 2.5rem; font-weight: bold; margin: 0.5rem 0; color: #f59e0b;">
                <?php echo $pending_count; ?>
            </p>
        </div>

        <div class="card" style="border-left: 4px solid #10b981; padding: 1.5rem; background: #fff; border-radius: 8px; box-shadow: var(--shadow);">
            <h3 style="color: #64748b; font-size: 0.9rem; margin-top: 0;">Approved Today</h3>
            <p style="font-size: 2.5rem; font-weight: bold; margin: 0.5rem 0; color: #10b981;">
                <?php echo $approved_today; ?>
            </p>
        </div>

        <div class="card" style="border-left: 4px solid #3b82f6; padding: 1.5rem; background: #fff; border-radius: 8px; box-shadow: var(--shadow);">
            <h3 style="color: #64748b; font-size: 0.9rem; margin-top: 0;">Total Cleared Students</h3>
            <p style="font-size: 2.5rem; font-weight: bold; margin: 0.5rem 0; color: #3b82f6;">
                <?php echo $total_cleared; ?>
            </p>
        </div>
    </div>

    <div class="card" style="padding: 1.5rem; background: #fff; border-radius: 8px; box-shadow: var(--shadow); width: 100%; overflow-x: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.2rem; margin: 0; color: #1e293b;">Action Required: Pending Clearances</h2>
            <a href="clearance.php" style="font-size: 0.8rem; text-decoration: none; padding: 0.4rem 0.8rem; border: 1px solid #cbd5e1; border-radius: 4px; color: #3b82f6;">View All Requests</a>
        </div>
        
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; background-color: #f8fafc;">
                    <th style="padding: 0.75rem 1rem; color: #475569;">Date Requested</th>
                    <th style="padding: 0.75rem 1rem; color: #475569;">Student No.</th>
                    <th style="padding: 0.75rem 1rem; color: #475569;">Student Name</th>
                    <th style="padding: 0.75rem 1rem; color: #475569;">Status</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; color: #475569;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_requests)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 3rem; color: #94a3b8;">
                            🎉 All caught up! There are no pending clearance requests right now.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_requests as $req): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.75rem 1rem; color: #64748b;">
                                <?php echo date('M d, Y h:i A', strtotime($req['created_at'])); ?>
                            </td>
                            <td style="padding: 0.75rem 1rem; font-weight: bold; color: #475569;">
                                <?php echo htmlspecialchars($req['student_number']); ?>
                            </td>
                            <td style="padding: 0.75rem 1rem; color: #1e293b; font-weight: 500;">
                                <?php echo htmlspecialchars($req['student_name']); ?>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <span style="background: #fef3c7; color: #92400e; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase;">
                                    <?php echo htmlspecialchars($req['status']); ?>
                                </span>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                <a href="clearance.php" class="btn btn-primary" style="padding: 0.3rem 0.6rem; font-size: 0.8rem;">Review</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php include '../layouts/footer.php'; ?>