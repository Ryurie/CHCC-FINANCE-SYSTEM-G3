<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../../login.php"); exit(); }
require '../../config/db_connect.php';
$message = "";

// KAPAG CLINICK ANG "ISSUE CHEQUE" BUTTON
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_claimed_btn'])) {
    $grant_id = intval($_POST['grant_id']);
    $conn->query("UPDATE student_scholarships SET claim_status = 'Claimed' WHERE grant_id = $grant_id");
    $message = "<div class='alert-msg alert-success animate-fade-up'>✅ Success! Cheque/Cash excess marked as CLAIMED.</div>";
}

// MASS GRANTING LOGIC (NO WALLETS)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['grant_scholarship_btn'])) {
    $invoice_ids = isset($_POST['invoice_ids']) ? $_POST['invoice_ids'] : [];
    $scholarship_name = trim($_POST['scholarship_name']); 
    $discount_amount = floatval($_POST['discount_amount']);

    if (empty($invoice_ids)) {
        $message = "<div class='alert-msg alert-error animate-fade-up'>❌ Error: Please select at least one student.</div>";
    } else {
        $success_count = 0;
        foreach($invoice_ids as $inv_id) {
            $invoice_id = intval($inv_id);
            try {
                $stmt_bal = $conn->query("SELECT student_id, (total_amount + penalty) - IFNULL((SELECT SUM(amount_paid) FROM payments WHERE invoice_id = $invoice_id), 0) as remaining FROM invoices WHERE invoice_id = $invoice_id");
                $inv_data = $stmt_bal->fetch_assoc();
                
                if($inv_data) {
                    $remaining_balance = $inv_data['remaining'];
                    $student_id = $inv_data['student_id'];
                    $actual_payment = $discount_amount;
                    
                    // EXCESS CALCULATION (FOR CHEQUE)
                    $excess_amount = 0;
                    $claim_status = 'N/A';
                    if ($discount_amount > $remaining_balance) {
                        $actual_payment = $remaining_balance; 
                        $excess_amount = $discount_amount - $remaining_balance; 
                        $claim_status = 'Pending'; // 🔥 NEW: PENDING FOR CHEQUE CLAIM
                    }

                    $stmt_grant = $conn->prepare("INSERT INTO student_scholarships (invoice_id, scholarship_name, discount_amount, excess_amount, claim_status) VALUES (?, ?, ?, ?, ?)");
                    $stmt_grant->bind_param("isdds", $invoice_id, $scholarship_name, $discount_amount, $excess_amount, $claim_status); 
                    $stmt_grant->execute();

                    if ($actual_payment > 0) {
                        $method = "Scholarship ($scholarship_name)"; 
                        $ref_no = "GRANT-" . time() . rand(10,99); 
                        $stmt_pay = $conn->prepare("INSERT INTO payments (invoice_id, amount_paid, payment_method, reference_number) VALUES (?, ?, ?, ?)");
                        $stmt_pay->bind_param("idss", $invoice_id, $actual_payment, $method, $ref_no); 
                        $stmt_pay->execute();
                    }

                    $stmt_sum = $conn->prepare("SELECT SUM(amount_paid) as total_paid FROM payments WHERE invoice_id = ?"); 
                    $stmt_sum->bind_param("i", $invoice_id); $stmt_sum->execute(); 
                    $total_paid = $stmt_sum->get_result()->fetch_assoc()['total_paid'];
                    
                    $stmt_inv_total = $conn->query("SELECT (total_amount + penalty) as grand_total FROM invoices WHERE invoice_id = $invoice_id");
                    $grand_total = $stmt_inv_total->fetch_assoc()['grand_total'];

                    $new_status = ($total_paid >= $grand_total) ? 'Paid' : 'Partial'; 
                    $conn->query("UPDATE invoices SET status = '$new_status' WHERE invoice_id = $invoice_id");
                    $success_count++;
                }
            } catch (mysqli_sql_exception $e) { continue; }
        }
        $message = "<div class='alert-msg alert-success animate-fade-up'>✅ Success! <b>$scholarship_name</b> granted to $success_count student(s).</div>";
    }
}

$result = $conn->query("SELECT s.*, i.student_id FROM student_scholarships s JOIN invoices i ON s.invoice_id = i.invoice_id ORDER BY s.date_granted DESC");
$query_invoices = "SELECT i.invoice_id, i.student_id, i.semester, (i.total_amount + i.penalty) as grand_total, IFNULL((SELECT SUM(amount_paid) FROM payments WHERE invoice_id = i.invoice_id), 0) as total_paid FROM invoices i WHERE i.status != 'Paid' ORDER BY i.invoice_id DESC";
$invoices_list = $conn->query($query_invoices);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholarships - Finance & Fee System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        /* MINIFIED CSS PARA MABILIS (SAME AS INVOICES) */
        * { box-sizing: border-box; } :root { --bg-color: #FAF9F7; --card-bg: #FFFFFF; --text-primary: #111827; --text-secondary: #6B7280; --border-color: #E5E7EB; --button-dark: #111827; --button-text: #FFFFFF; --nav-bg: rgba(250, 249, 247, 0.95); --hover-bg: #F3F4F6; --shadow-color: rgba(0,0,0,0.05); } [data-theme="dark"] { --bg-color: #111827; --card-bg: #1F2937; --text-primary: #F9FAFB; --text-secondary: #9CA3AF; --border-color: #374151; --button-dark: #F9FAFB; --button-text: #111827; --nav-bg: rgba(17, 24, 39, 0.95); --hover-bg: #374151; --shadow-color: rgba(0,0,0,0.2); } body { margin: 0; background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-primary); transition: 0.4s; overflow-x: hidden; } @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .animate-fade-up { opacity: 0; animation: fadeInUp 0.6s forwards; } .delay-1 { animation-delay: 0.1s; } .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px); } .nav-left { display: flex; flex-direction: column; } .nav-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; } .nav-subtitle { font-size: 0.85rem; color: var(--text-secondary); margin: 0; } .nav-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end;} .nav-btn { text-decoration: none; padding: 8px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); cursor: pointer; transition: 0.3s; } .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; } .container { width: 95%; max-width: 1400px; margin: 40px auto; } .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; } h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin: 0; } .btn-add { background-color: #3b82f6; color: white; padding: 10px 20px; border-radius: 30px; border: none; font-size: 1rem; cursor: pointer; font-weight: 500; transition: 0.3s;} .table-card { background-color: var(--card-bg); border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-color); padding: 30px; border: 1px solid var(--border-color); overflow-x: auto; } table { width: 100%; border-collapse: collapse; min-width: 900px; } th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); } th { color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; } tr:hover td { background-color: var(--hover-bg); } .amount { font-weight: 600; color: #3b82f6; } .alert-msg { padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; font-weight: 500; font-size: 0.95rem; } .alert-success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); } .alert-error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); } .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 2000; justify-content: center; align-items: center; } .modal-box { background-color: var(--card-bg); padding: 40px; border-radius: 24px; width: 90%; max-width: 500px; position: relative; border: 1px solid var(--border-color); max-height: 90vh; overflow-y: auto;} .close-btn { position: absolute; top: 20px; right: 25px; font-size: 1.8rem; cursor: pointer; color: var(--text-secondary); } .modal-box h2 { margin-top: 0; font-family: 'Playfair Display', serif; font-size: 1.8rem; } .modal-box label { display: block; margin-top: 15px; font-size: 0.9rem; font-weight: 600; color: var(--text-secondary); } .modal-box input[type="text"], .modal-box input[type="number"], .modal-box select { width: 100%; padding: 14px 16px; margin-top: 8px; border: 1px solid var(--border-color); border-radius: 12px; background-color: var(--bg-color); color: var(--text-primary); font-family: 'Inter', sans-serif; box-sizing: border-box; } .modal-submit { width: 100%; margin-top: 25px; padding: 16px; border-radius: 12px; background: #3b82f6; color: white; border: none; font-size: 1rem; font-weight: bold; cursor: pointer; transition: 0.3s;} .modal-submit:hover { background: #2563eb; } .fees-list-container { margin-top: 10px; border: 1px solid var(--border-color); border-radius: 12px; padding: 15px; max-height: 250px; overflow-y: auto; background-color: var(--bg-color); } .fee-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed var(--border-color); transition: 0.3s; } .fee-item:last-child { border-bottom: none; } .fee-item label { margin: 0; display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-primary); font-weight: 500; font-size: 0.95rem; width: 100%; } .fee-item input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #3b82f6; } .fee-price { color: #ef4444; font-weight: 600; font-size: 0.85rem; white-space: nowrap; }
        
        .btn-claim { background: #10b981; color: white; padding: 6px 12px; border-radius: 8px; border: none; font-size: 0.8rem; font-weight: bold; cursor: pointer; } .btn-claim:hover { background: #059669; }
        
        @media (max-width: 900px) { .menu-toggle { display: block; } .nav-left { flex: none; width: 80%; } .nav-right { display: none; flex-direction: column; background-color: var(--card-bg); padding: 15px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 15px 30px var(--shadow-color); margin-top: 15px; align-items: stretch; position: absolute; top: 100%; left: 5%; right: 5%; z-index: 10000; gap: 5px; } .nav-right.show-menu { display: flex; animation: popDown 0.3s forwards; } .nav-btn { text-align: left; padding: 12px 20px; border-radius: 8px; width: 100%; } }
        @media (max-width: 768px) { .header-flex { flex-direction: column; align-items: stretch; gap: 15px; } h1 { font-size: 2rem; } .btn-add { width: 100%; text-align: center; } .table-card { background: transparent !important; padding: 0 !important; box-shadow: none !important; border: none !important; overflow-x: visible !important; } table, thead, tbody, th, td, tr { display: block; width: 100%; min-width: 0 !important; } table thead { display: none; } table tbody tr { background-color: var(--card-bg); margin-bottom: 20px; border-radius: 16px; box-shadow: 0 5px 15px var(--shadow-color); border: 1px solid var(--border-color); overflow: hidden; } table tbody td { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--border-color); text-align: right; font-size: 0.95rem; } table tbody td:last-child { border-bottom: none; background-color: var(--hover-bg); justify-content: flex-end; gap: 10px; } table tbody td::before { content: attr(data-label); font-weight: 700; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; margin-right: 15px; text-align: left; } table tbody td.empty-state { display: block; text-align: center !important; padding: 30px 20px; } table tbody td.empty-state::before { display: none; } }
    </style>
</head>
<body>
    <header class="top-navbar animate-fade-up">
        <div class="nav-left"><h1 class="nav-title">Finance & Fee System</h1><p class="nav-subtitle">Scholarship Management Module</p></div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="../../index.php" class="nav-btn">Dashboard</a> <a href="../fees/index.php" class="nav-btn">Fees</a> <a href="../invoices/index.php" class="nav-btn">Invoices</a> <a href="../payments/index.php" class="nav-btn">Payments</a> <a href="../ledger/index.php" class="nav-btn">Ledger</a> <a href="index.php" class="nav-btn active">Scholarships</a> <a href="../reports/index.php" class="nav-btn">Reports</a> <button id="theme-toggle" class="nav-btn">🌙 Mode</button> <a href="../../logout.php" class="nav-btn" style="color: #ef4444;">Logout</a>
        </div>
    </header>

    <div class="container animate-fade-up delay-1">
        <div class="header-flex">
            <h1>🎓 Scholarships & Grants</h1>
            <button onclick="openModal()" class="btn-add">+ Mass Grant Scholarship</button>
        </div>
        
        <?php echo $message; ?>
        
        <div class="table-card">
            <table>
                <thead><tr><th>Grant ID</th><th>Student ID</th><th>Invoice Ref</th><th>Type</th><th>Discount Amount</th><th>Excess / Cheque Claim</th><th>Date Granted</th></tr></thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) { 
                            $excess_html = "<span style='color:var(--text-secondary);'>N/A</span>";
                            if ($row['excess_amount'] > 0) {
                                if ($row['claim_status'] === 'Pending') {
                                    $excess_html = "<span style='color:#ef4444; font-weight:bold;'>₱" . number_format($row['excess_amount'], 2) . " (Pending)</span> 
                                                    <form method='POST' style='display:inline;' onsubmit='return confirm(\"Are you sure you have issued the cheque/cash?\");'>
                                                        <input type='hidden' name='grant_id' value='{$row['grant_id']}'>
                                                        <button type='submit' name='mark_claimed_btn' class='btn-claim'>Issue Cheque</button>
                                                    </form>";
                                } else {
                                    $excess_html = "<span style='color:#10b981; font-weight:bold;'>₱" . number_format($row['excess_amount'], 2) . " (Claimed)</span>";
                                }
                            }

                            echo "<tr>
                                    <td data-label='Grant ID'><strong>GRNT-" . str_pad($row['grant_id'], 4, '0', STR_PAD_LEFT) . "</strong></td>
                                    <td data-label='Student ID'>" . htmlspecialchars($row['student_id']) . "</td>
                                    <td data-label='Invoice Ref'>INV-" . str_pad($row['invoice_id'], 4, '0', STR_PAD_LEFT) . "</td>
                                    <td data-label='Type'>" . htmlspecialchars($row['scholarship_name']) . "</td>
                                    <td data-label='Discount Amount' class='amount'>₱ " . number_format($row['discount_amount'], 2) . "</td>
                                    <td data-label='Excess / Cheque Claim' style='display:flex; align-items:center; gap:10px;'>{$excess_html}</td>
                                    <td data-label='Date Granted'>" . date("M d, Y", strtotime($row['date_granted'])) . "</td>
                                  </tr>"; 
                        }
                    } else { echo "<tr><td colspan='7' class='empty-state' style='color:var(--text-secondary);'>No scholarships granted yet.</td></tr>"; }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="addGrantModal" class="modal-overlay">
        <div class="modal-box">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2>Batch Grant Scholarship</h2>
            <p style="color: #3b82f6; font-size: 0.85rem; background: rgba(59, 130, 246, 0.1); padding: 10px; border-radius: 8px; font-weight: 500; margin-top: -5px;">💡 If the scholarship exceeds the bill, the excess will be marked as "Pending" for physical cheque claiming.</p>
            
            <form method="POST" action="">
                
                <label>Select Grantees (Pending Invoices):</label>
                <div class="fees-list-container">
                    <div class="fee-item" style="background: rgba(59, 130, 246, 0.1); padding: 10px 15px; border-radius: 8px; margin-bottom: 10px; border: 1px solid rgba(59, 130, 246, 0.3);">
                        <label style="color: #3b82f6; font-weight: bold;"><input type="checkbox" id="selectAllStudents"> Select All Students</label>
                    </div>

                    <?php
                    if ($invoices_list && $invoices_list->num_rows > 0) {
                        while($inv = $invoices_list->fetch_assoc()) {
                            $balance = $inv['grand_total'] - $inv['total_paid'];
                            $sem_disp = htmlspecialchars($inv['semester'] ?? '1st Sem');
                            echo "<div class='fee-item'>
                                    <label>
                                        <input type='checkbox' class='student-checkbox' name='invoice_ids[]' value='{$inv['invoice_id']}'>
                                        <span>" . htmlspecialchars($inv['student_id']) . " <span style='color:var(--text-secondary); font-size:0.8rem;'>({$sem_disp})</span></span>
                                    </label>
                                    <span class='fee-price' style='color:#ef4444;'>Bal: ₱" . number_format($balance, 2) . "</span>
                                  </div>";
                        }
                    } else { echo "<p style='font-size: 0.85rem; color: var(--text-secondary); margin: 0;'>Walang pending na invoices.</p>"; }
                    ?>
                </div>

                <label for="scholarship_name">Scholarship Name / Type:</label> 
                <input type="text" id="scholarship_name" name="scholarship_name" required placeholder="e.g. TES Grant">
                
                <label for="discount_amount">Grant Amount per Student (₱):</label> 
                <input type="number" id="discount_amount" name="discount_amount" step="0.01" required>
                
                <button type="submit" name="grant_scholarship_btn" class="modal-submit">Apply Batch Scholarship</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeBtn = document.getElementById('theme-toggle'); if (localStorage.getItem('theme') === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); themeBtn.innerText = '☀️ Mode'; }
            themeBtn.addEventListener('click', () => { if (document.documentElement.getAttribute('data-theme') === 'dark') { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); themeBtn.innerText = '🌙 Mode'; } else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); themeBtn.innerText = '☀️ Mode'; } });
            const mobileBtn = document.getElementById('mobile-menu-btn'); const navMenu = document.getElementById('nav-menu'); if(mobileBtn) { mobileBtn.addEventListener('click', () => { navMenu.classList.toggle('show-menu'); }); }

            const selectAllBtn = document.getElementById('selectAllStudents'); const studentCheckboxes = document.querySelectorAll('.student-checkbox');
            if(selectAllBtn) { selectAllBtn.addEventListener('change', function() { const isChecked = this.checked; studentCheckboxes.forEach(cb => { cb.checked = isChecked; }); }); }
            studentCheckboxes.forEach(cb => { cb.addEventListener('change', function() { if (!this.checked) { selectAllBtn.checked = false; } else { const allChecked = Array.from(studentCheckboxes).every(box => box.checked); selectAllBtn.checked = allChecked; } }); });
        });
        const modal = document.getElementById('addGrantModal'); function openModal() { modal.style.display = 'flex'; } function closeModal() { modal.style.display = 'none'; } window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
    </script>
</body>
</html> 