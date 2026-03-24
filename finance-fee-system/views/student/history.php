<?php 
// views/student/history.php
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$student_id = 0;
$total_paid = 0;
$total_pending = 0;
$payments = [];

try {
    // 1. Kunin ang Student ID ng naka-login na user
    $stmt1 = $db->prepare("SELECT id FROM students WHERE user_id = :uid");
    $stmt1->execute([':uid' => $user_id]);
    $student = $stmt1->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $student_id = $student['id'];

        // 2. Kunin ang Total na nabayaran na (Verified)
        $stmt2 = $db->prepare("SELECT SUM(amount) FROM payments WHERE student_id = :sid AND status = 'verified'");
        $stmt2->execute([':sid' => $student_id]);
        $total_paid = $stmt2->fetchColumn() ?: 0;

        // 3. Kunin ang Total na nakabinbin pa (Pending)
        $stmt3 = $db->prepare("SELECT SUM(amount) FROM payments WHERE student_id = :sid AND status = 'pending'");
        $stmt3->execute([':sid' => $student_id]);
        $total_pending = $stmt3->fetchColumn() ?: 0;

        // 4. Kunin ang buong Transaction History niya
        $stmt4 = $db->prepare("SELECT p.id, p.amount, p.status, p.created_at, 
                                      i.id as invoice_id, f.name as fee_name
                               FROM payments p
                               JOIN invoices i ON p.invoice_id = i.id
                               JOIN fees f ON i.fee_id = f.id
                               WHERE p.student_id = :sid
                               ORDER BY p.created_at DESC");
        $stmt4->execute([':sid' => $student_id]);
        $payments = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    $error = "Failed to load payment history.";
}
?>

<style>
    .card-animate {
        transition: transform 0.3s ease, box-shadow 0.3s ease !important;
    }
    .card-animate:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08) !important;
    }
    .btn-animate {
        transition: all 0.2s ease-in-out !important;
        display: inline-block;
    }
    .btn-animate:hover {
        transform: translateY(-2px) scale(1.03) !important;
        filter: brightness(1.1) !important;
    }
</style>

<div style="width: 100%; box-sizing: border-box; margin-top: 1rem;">

    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem; color: #1e293b;">My Payment History</h1>
        <p style="color: #64748b; margin-top: 0;">Track your recent transactions and download official receipts.</p>
    </div>

    <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; width: 100%;">
        
        <div class="card card-animate" style="border-left: 4px solid #10b981; padding: 1.5rem; background: #fff; border-radius: 8px;">
            <h3 style="color: #64748b; font-size: 0.9rem; margin-top: 0;">Total Verified Payments</h3>
            <p style="font-size: 2rem; font-weight: bold; margin: 0.5rem 0; color: #10b981;">
                ₱<?php echo number_format($total_paid, 2); ?>
            </p>
            <small style="color: #94a3b8;">Payments officially cleared by the Cashier.</small>
        </div>

        <div class="card card-animate" style="border-left: 4px solid #f59e0b; padding: 1.5rem; background: #fff; border-radius: 8px;">
            <h3 style="color: #64748b; font-size: 0.9rem; margin-top: 0;">Pending Verification</h3>
            <p style="font-size: 2rem; font-weight: bold; margin: 0.5rem 0; color: #f59e0b;">
                ₱<?php echo number_format($total_pending, 2); ?>
            </p>
            <small style="color: #94a3b8;">Currently being reviewed by the Admin.</small>
        </div>
    </div>

    <div class="card card-animate" style="padding: 1.5rem; background: #fff; border-radius: 8px; width: 100%; overflow-x: auto;">
        
        <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
            <h2 style="font-size: 1.2rem; margin: 0; color: #1e293b;">Transaction Ledger</h2>
            <input type="text" id="historySearch" placeholder="Search fee or status..." 
                   style="width: 100%; max-width: 300px; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 4px;">
        </div>

        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; background-color: #f8fafc;">
                    <th style="padding: 1rem; color: #475569;">Date Submitted</th>
                    <th style="padding: 1rem; color: #475569;">Payment For</th>
                    <th style="padding: 1rem; color: #475569;">Amount (₱)</th>
                    <th style="padding: 1rem; color: #475569;">Status</th>
                    <th style="padding: 1rem; text-align: center; color: #475569;">Action</th>
                </tr>
            </thead>
            <tbody id="historyTableBody">
                <?php if (empty($payments)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 3rem; color: #94a3b8;">You have no payment records yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                            <td style="padding: 1rem; color: #64748b; font-size: 0.9rem;">
                                <?php echo date('M d, Y', strtotime($pay['created_at'])); ?><br>
                                <small><?php echo date('h:i A', strtotime($pay['created_at'])); ?></small>
                            </td>
                            <td style="padding: 1rem;">
                                <div style="font-weight: 500; color: #3b82f6;"><?php echo htmlspecialchars($pay['fee_name']); ?></div>
                                <div style="font-size: 0.8rem; color: #94a3b8;">Invoice #INV-<?php echo str_pad($pay['invoice_id'], 4, '0', STR_PAD_LEFT); ?></div>
                            </td>
                            <td style="padding: 1rem; font-weight: bold; color: #1e293b;">
                                ₱<?php echo number_format($pay['amount'], 2); ?>
                            </td>
                            <td style="padding: 1rem;">
                                <?php 
                                    $bg = '#fef3c7'; $color = '#92400e';
                                    if ($pay['status'] === 'verified') { $bg = '#dcfce3'; $color = '#166534'; }
                                    if ($pay['status'] === 'rejected') { $bg = '#fee2e2'; $color = '#991b1b'; }
                                ?>
                                <span class="history-status" style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
                                    <?php echo $pay['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php if ($pay['status'] === 'verified'): ?>
                                    <button onclick="alert('Printing Receipt functionality will be available soon!')" class="btn-animate" style="background: transparent; color: #10b981; border: 1px solid #10b981; padding: 0.4rem 0.8rem; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">
                                        🖨️ Receipt
                                    </button>
                                <?php elseif ($pay['status'] === 'rejected'): ?>
                                    <span style="font-size: 0.8rem; color: #ef4444; font-weight: bold;">✕ Invalid</span>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: #f59e0b; font-style: italic;">Under Review</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Search Filter Logic
document.getElementById('historySearch').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    const rows = document.querySelectorAll('#historyTableBody tr');
    
    rows.forEach(row => {
        if(row.cells.length < 5) return;
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});
</script>

<?php include '../layouts/footer.php'; ?>