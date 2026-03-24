<?php 
// views/admin/payments.php
session_start();
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$payments = [];
try {
    $query = "SELECT p.*, u.name as student_name, s.student_number 
              FROM payments p 
              JOIN students s ON p.student_id = s.id 
              JOIN users u ON s.user_id = u.id 
              ORDER BY p.status DESC, p.created_at DESC";
    $stmt = $db->query($query);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) { }
?>

<style>
    .modern-card { background: #fff; border-radius: 16px; padding: 1.5rem; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
    .status-pill { padding: 0.4rem 0.9rem; border-radius: 20px; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; }
    .status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .status-verified { background: #dcfce3; color: #166534; border: 1px solid #bbf7d0; }
    .status-rejected { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    
    .action-btn { padding: 0.5rem 1rem; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; transition: 0.2s; font-size: 0.8rem; }
    .btn-verify { background: #10b981; color: white; }
    .btn-reject { background: #ef4444; color: white; margin-left: 5px; }
    .action-btn:hover { transform: translateY(-2px); opacity: 0.9; }

    body.dark-mode .modern-card { background: #1e293b; border-color: #334155; }
    body.dark-mode td { border-color: #334155; color: #e2e8f0; }
</style>

<div style="width: 100%; margin-top: 1rem;">
    <div style="margin-bottom: 2rem;">
        <h1 style="margin: 0; font-weight: 900; font-size: 2rem;">Payment Verification</h1>
        <p style="color: #64748b; margin: 0.2rem 0 0 0;">Approve or reject student payment submissions.</p>
    </div>

    <div class="modern-card" style="padding: 0; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 1rem; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Student</th>
                        <th style="padding: 1rem; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Reference #</th>
                        <th style="padding: 1rem; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Amount</th>
                        <th style="padding: 1rem; color: #64748b; font-size: 0.8rem; text-transform: uppercase;">Status</th>
                        <th style="padding: 1rem; color: #64748b; font-size: 0.8rem; text-transform: uppercase; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($payments as $p): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 1rem;">
                                <div style="font-weight: 700;"><?php echo htmlspecialchars($p['student_name']); ?></div>
                                <div style="font-size: 0.75rem; color: #64748b;">ID: <?php echo htmlspecialchars($p['student_number']); ?></div>
                            </td>
                            <td style="padding: 1rem; font-family: monospace; font-weight: 600;"><?php echo $p['reference_number']; ?></td>
                            <td style="padding: 1rem; font-weight: 800; color: #10b981;">₱<?php echo number_format($p['amount'], 2); ?></td>
                            <td style="padding: 1rem;">
                                <span class="status-pill status-<?php echo $p['status']; ?>"><?php echo $p['status']; ?></span>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php if($p['status'] === 'pending'): ?>
                                    <button class="action-btn btn-verify">Verify</button>
                                    <button class="action-btn btn-reject">Reject</button>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 0.8rem; font-style: italic;">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../layouts/footer.php'; ?>