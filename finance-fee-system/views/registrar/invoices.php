<?php 
// views/registrar/invoices.php
session_start();
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$message = ''; $msg_color = '';

// --- ✨ PHP LOGIC: CREATE NEW INVOICE ✨ ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_invoice') {
    $student_id = $_POST['student_id'] ?? '';
    $fee_id = $_POST['fee_id'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    
    if ($student_id && $fee_id && $due_date) {
        try {
            // Kunin ang original price ng fee
            $stmtFee = $db->prepare("SELECT amount FROM fees WHERE id = ?");
            $stmtFee->execute([$fee_id]);
            $amount = $stmtFee->fetchColumn();

            $stmt = $db->prepare("INSERT INTO invoices (student_id, fee_id, amount_due, status, due_date) VALUES (?, ?, ?, 'pending', ?)");
            $stmt->execute([$student_id, $fee_id, $amount, $due_date]);
            
            $message = "✨ Success: Invoice generated successfully!";
            $msg_color = "#10b981";
        } catch(PDOException $e) {
            $message = "⚠️ Error: " . $e->getMessage();
            $msg_color = "#ef4444";
        }
    }
}

// Kunin ang listahan para sa Invoices Table
$invoices = [];
try {
    $query = "SELECT i.*, u.name as student_name, s.student_number, f.name as fee_name 
              FROM invoices i 
              JOIN students s ON i.student_id = s.id 
              JOIN users u ON s.user_id = u.id 
              JOIN fees f ON i.fee_id = f.id 
              ORDER BY i.created_at DESC";
    $stmt = $db->query($query);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) { }

// Kunin ang listahan ng Students at Fees para sa Modal Dropdown
$students_list = $db->query("SELECT s.id, u.name FROM students s JOIN users u ON s.user_id = u.id ORDER BY u.name ASC")->fetchAll(PDO::FETCH_ASSOC);
$fees_list = $db->query("SELECT id, name, amount FROM fees ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* Main Layout Correction */
    .invoice-container { width: 100%; box-sizing: border-box; padding: 10px; }

    /* Modern Button */
    .btn-create-invoice {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white; padding: 0.8rem 1.5rem; border-radius: 12px; border: none;
        font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 10px;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); transition: all 0.3s ease;
    }
    .btn-create-invoice:hover { transform: translateY(-2px); filter: brightness(1.1); box-shadow: 0 8px 15px rgba(16, 185, 129, 0.4); }

    /* Cards & Tables */
    .modern-card { background: #ffffff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 2rem; }
    .modern-table { width: 100%; border-collapse: separate; border-spacing: 0; text-align: left; }
    .modern-table th { padding: 1.2rem 1rem; background: #f8fafc; color: #64748b; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; border-bottom: 2px solid #e2e8f0; }
    .modern-table td { padding: 1rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    
    .status-pill { padding: 0.35rem 0.8rem; border-radius: 20px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; display: inline-block; }
    .status-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .status-paid { background: #dcfce3; color: #166534; border: 1px solid #bbf7d0; }
    
    .btn-pdf { color: #3b82f6; text-decoration: none; font-weight: 700; font-size: 0.85rem; padding: 0.5rem 0.8rem; border-radius: 6px; background: #f0f7ff; transition: 0.2s; }
    .btn-pdf:hover { background: #3b82f6; color: white; }

    /* Modal Styling */
    .modal-overlay { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); justify-content: center; align-items: center; }
    .modal-content { background: #fff; padding: 2.5rem; border-radius: 20px; width: 90%; max-width: 500px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); animation: modalIn 0.3s ease; }
    @keyframes modalIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    .form-group { margin-bottom: 1.5rem; }
    .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 700; color: #475569; font-size: 0.9rem; }
    .form-control { width: 100%; padding: 0.9rem; border: 1px solid #cbd5e1; border-radius: 10px; box-sizing: border-box; font-family: inherit; font-size: 1rem; transition: 0.2s; }
    .form-control:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }

    /* Dark Mode Fixes */
    body.dark-mode .modern-card, body.dark-mode .modal-content { background: #1e293b; border-color: #334155; color: #f8fafc; }
    body.dark-mode .modern-table th { background: #0f172a; border-color: #334155; color: #94a3b8; }
    body.dark-mode .modern-table td { border-color: #334155; color: #e2e8f0; }
    body.dark-mode .form-control { background: #0f172a; border-color: #475569; color: white; }
    body.dark-mode .btn-pdf { background: #334155; color: #60a5fa; }
</style>

<div class="invoice-container">
    <div style="margin-bottom: 2.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="margin: 0; font-weight: 900; font-size: 2rem; letter-spacing: -0.8px; color: var(--text-main);">Invoice Management</h1>
            <p style="color: #64748b; margin-top: 5px; font-weight: 500;">Monitor and generate student billing statements.</p>
        </div>
        <button onclick="openInvoiceModal()" class="btn-create-invoice">
            <span style="font-size: 1.2rem;">📝</span> Create New Invoice
        </button>
    </div>

    <?php if($message): ?>
        <div style="background: <?php echo $msg_color; ?>15; color: <?php echo $msg_color; ?>; padding: 1.2rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 700; border-left: 6px solid <?php echo $msg_color; ?>; display: flex; align-items: center; gap: 10px;">
            <span><?php echo $message; ?></span>
        </div>
    <?php endif; ?>

    <div class="modern-card">
        <div style="overflow-x: auto;">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Issue Date</th>
                        <th>Student Information</th>
                        <th>Fee Type</th>
                        <th>Amount Due</th>
                        <th>Status</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($invoices)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 4rem; color: #94a3b8; font-style: italic;">No records found. Start by creating an invoice.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($invoices as $inv): ?>
                            <tr>
                                <td style="color: #64748b; font-weight: 600; font-size: 0.85rem;">
                                    <?php echo date('M d, Y', strtotime($inv['created_at'])); ?>
                                </td>
                                <td>
                                    <div style="font-weight: 800;"><?php echo htmlspecialchars($inv['student_name']); ?></div>
                                    <div style="font-size: 0.75rem; color: #3b82f6; font-family: monospace;"><?php echo $inv['student_number']; ?></div>
                                </td>
                                <td style="font-weight: 700;"><?php echo htmlspecialchars($inv['fee_name']); ?></td>
                                <td style="font-weight: 900; color: #ef4444; font-size: 1.1rem;">
                                    ₱<?php echo number_format($inv['amount_due'], 2); ?>
                                </td>
                                <td>
                                    <span class="status-pill <?php echo $inv['status'] == 'paid' ? 'status-paid' : 'status-pending'; ?>">
                                        <?php echo $inv['status']; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <a href="view_invoice.php?id=<?php echo $inv['id']; ?>" target="_blank" class="btn-pdf">📄 PDF</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="invoiceModal" class="modal-overlay">
    <div class="modal-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h2 style="margin: 0; font-weight: 900; font-size: 1.5rem;">Generate Invoice</h2>
            <button onclick="closeInvoiceModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>
        
        <form method="POST">
            <input type="hidden" name="action" value="create_invoice">
            
            <div class="form-group">
                <label>Select Student</label>
                <select name="student_id" class="form-control" required>
                    <option value="">-- Search Student --</option>
                    <?php foreach($students_list as $s): ?>
                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Billing Item (Fee)</label>
                <select name="fee_id" class="form-control" required>
                    <option value="">-- Choose Fee Type --</option>
                    <?php foreach($fees_list as $f): ?>
                        <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?> (₱<?php echo number_format($f['amount'], 2); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>">
            </div>

            <div style="display: flex; gap: 12px; margin-top: 2.5rem;">
                <button type="button" onclick="closeInvoiceModal()" style="flex: 1; padding: 0.9rem; border-radius: 12px; border: 1px solid #cbd5e1; background: none; cursor: pointer; font-weight: 700; color: #64748b;">Cancel</button>
                <button type="submit" style="flex: 1; padding: 0.9rem; border-radius: 12px; border: none; background: #3b82f6; color: white; cursor: pointer; font-weight: 700; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">Create Bill</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openInvoiceModal() { 
        document.getElementById('invoiceModal').style.display = 'flex'; 
    }
    function closeInvoiceModal() { 
        document.getElementById('invoiceModal').style.display = 'none'; 
    }
    
    // Close modal when clicking outside content
    window.onclick = function(event) {
        if (event.target == document.getElementById('invoiceModal')) {
            closeInvoiceModal();
        }
    }
</script>

<?php include '../layouts/footer.php'; ?>