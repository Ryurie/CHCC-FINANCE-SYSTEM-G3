<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../../login.php"); exit(); }
require '../../config/db_connect.php';
$message = "";

// KAPAG NAG-LOG NG PAYMENT
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_payment_btn'])) {
    $invoice_id = intval($_POST['invoice_id']); 
    $amount_paid = floatval($_POST['amount_paid']); 
    $payment_method = $_POST['payment_method']; 
    $reference_number = $_POST['reference_number'];
    
    // 🔥 NEW: Kukunin natin ang pangalan ng naka-login na admin
    $cashier_name = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';
    
    $check_inv = $conn->query("SELECT invoice_id FROM invoices WHERE invoice_id = $invoice_id");
    if($check_inv->num_rows == 0) { 
        $message = "<div class='alert-msg alert-error animate-fade-up'>❌ Error: Invoice ID does not exist.</div>"; 
    } else {
        try {
            // Updated SQL Insert to include Cashier Name
            $stmt_insert = $conn->prepare("INSERT INTO payments (invoice_id, amount_paid, payment_method, reference_number, cashier_name) VALUES (?, ?, ?, ?, ?)");
            $stmt_insert->bind_param("idsss", $invoice_id, $amount_paid, $payment_method, $reference_number, $cashier_name);
            
            if ($stmt_insert->execute()) {
                $stmt_inv = $conn->prepare("SELECT (total_amount + penalty) as grand_total FROM invoices WHERE invoice_id = ?"); 
                $stmt_inv->bind_param("i", $invoice_id); $stmt_inv->execute(); 
                $grand_total = $stmt_inv->get_result()->fetch_assoc()['grand_total'];
                
                $stmt_sum = $conn->prepare("SELECT SUM(amount_paid) as total_paid FROM payments WHERE invoice_id = ?"); 
                $stmt_sum->bind_param("i", $invoice_id); $stmt_sum->execute(); 
                $total_paid = $stmt_sum->get_result()->fetch_assoc()['total_paid'];
                
                $new_status = ($total_paid >= $grand_total) ? 'Paid' : 'Partial';
                $conn->query("UPDATE invoices SET status = '$new_status' WHERE invoice_id = $invoice_id");
                
                $message = "<div class='alert-msg alert-success animate-fade-up'>✅ Success! Payment Logged by {$cashier_name}.</div>";
            }
        } catch (mysqli_sql_exception $e) { 
            $message = "<div class='alert-msg alert-error animate-fade-up'>❌ Database Error: " . $e->getMessage() . "</div>"; 
        }
    }
}

// Updated Select para isama ang cashier_name
$result = $conn->query("SELECT p.payment_id, p.amount_paid, p.payment_method, p.reference_number, p.payment_date, p.cashier_name, i.student_id, i.invoice_id FROM payments p JOIN invoices i ON p.invoice_id = i.invoice_id ORDER BY p.payment_date DESC");

$query_invoices = "SELECT i.invoice_id, i.student_id, i.semester, (i.total_amount + i.penalty) as grand_total, IFNULL((SELECT SUM(amount_paid) FROM payments WHERE invoice_id = i.invoice_id), 0) as total_paid FROM invoices i WHERE i.status != 'Paid' ORDER BY i.invoice_id DESC";
$invoices_list = $conn->query($query_invoices);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - Finance & Fee System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; } :root { --bg-color: #FAF9F7; --card-bg: #FFFFFF; --text-primary: #111827; --text-secondary: #6B7280; --border-color: #E5E7EB; --button-dark: #111827; --button-text: #FFFFFF; --nav-bg: rgba(250, 249, 247, 0.95); --hover-bg: #F3F4F6; --shadow-color: rgba(0,0,0,0.05); } [data-theme="dark"] { --bg-color: #111827; --card-bg: #1F2937; --text-primary: #F9FAFB; --text-secondary: #9CA3AF; --border-color: #374151; --button-dark: #F9FAFB; --button-text: #111827; --nav-bg: rgba(17, 24, 39, 0.95); --hover-bg: #374151; --shadow-color: rgba(0,0,0,0.2); } body { margin: 0; background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-primary); transition: 0.4s; overflow-x: hidden; } @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .animate-fade-up { opacity: 0; animation: fadeInUp 0.6s forwards; } .delay-1 { animation-delay: 0.1s; } .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px); } .nav-left { display: flex; flex-direction: column; } .nav-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; } .nav-subtitle { font-size: 0.85rem; color: var(--text-secondary); margin: 0; } .nav-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end;} .nav-btn { text-decoration: none; padding: 8px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); cursor: pointer; transition: 0.3s; white-space: nowrap; } .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; } .container { width: 90%; max-width: 1300px; margin: 40px auto; } .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; } h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin: 0; } .btn-add { background-color: #3b82f6; color: white; padding: 12px 24px; border-radius: 30px; border: none; font-size: 0.95rem; cursor: pointer; font-weight: 600; transition: 0.3s; box-shadow: 0 4px 10px var(--shadow-color);} .btn-add:hover { background-color: #2563eb; transform: translateY(-2px); } .table-card { background-color: var(--card-bg); border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-color); padding: 30px; border: 1px solid var(--border-color); overflow-x: auto; } table { width: 100%; border-collapse: collapse; min-width: 900px; } th, td { padding: 15px 10px; text-align: left; border-bottom: 1px solid var(--border-color); } th { color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; } tr:hover td { background-color: var(--hover-bg); } .amount { font-weight: 600; color: #10b981; } .alert-msg { padding: 12px 20px; border-radius: 10px; margin-bottom: 20px; font-weight: 500; font-size: 0.95rem; } .alert-success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); } .alert-error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); } .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 2000; justify-content: center; align-items: center; } .modal-box { background-color: var(--card-bg); padding: 40px; border-radius: 24px; width: 90%; max-width: 500px; position: relative; border: 1px solid var(--border-color); } .close-btn { position: absolute; top: 20px; right: 25px; font-size: 1.8rem; cursor: pointer; color: var(--text-secondary); transition: 0.2s;} .close-btn:hover { color: var(--text-primary); transform: scale(1.1); } .modal-box h2 { margin-top: 0; font-family: 'Playfair Display', serif; font-size: 1.8rem; color: var(--text-primary); } .modal-box label { display: block; margin-top: 15px; font-size: 0.9rem; font-weight: 600; color: var(--text-secondary); } .modal-box input, .modal-box select { width: 100%; padding: 14px 16px; margin-top: 8px; border: 1px solid var(--border-color); border-radius: 12px; background-color: var(--bg-color); color: var(--text-primary); font-family: 'Inter', sans-serif; box-sizing: border-box; transition: 0.3s; } .modal-box input:focus, .modal-box select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); } .modal-submit { width: 100%; margin-top: 25px; padding: 16px; border-radius: 12px; background: #10b981; color: white; border: none; font-size: 1rem; font-weight: bold; cursor: pointer; transition: 0.3s; } .modal-submit:hover { background: #059669; }
        .btn-print { display: inline-flex; align-items: center; gap: 5px; background: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3); padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.8rem; font-weight: bold; transition: 0.3s; } .btn-print:hover { background: #3b82f6; color: white; }
        @media (max-width: 900px) { .menu-toggle { display: block; } .nav-left { flex: none; width: 80%; } .nav-right { display: none; flex-direction: column; background-color: var(--card-bg); padding: 15px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 15px 30px var(--shadow-color); margin-top: 15px; align-items: stretch; position: absolute; top: 100%; left: 5%; right: 5%; z-index: 10000; gap: 5px; } .nav-right.show-menu { display: flex; animation: popDown 0.3s forwards; } @keyframes popDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } } .nav-btn { text-align: left; padding: 12px 20px; font-size: 1rem; margin: 0; border-radius: 8px; width: 100%; } }
        @media (max-width: 768px) { .header-flex { flex-direction: column; align-items: stretch; gap: 15px; } h1 { font-size: 2rem; } .btn-add { width: 100%; text-align: center; } .table-card { background: transparent !important; padding: 0 !important; box-shadow: none !important; border: none !important; overflow-x: visible !important; } table, thead, tbody, th, td, tr { display: block; width: 100%; min-width: 0 !important; } table thead { display: none; } table tbody tr { background-color: var(--card-bg); margin-bottom: 20px; border-radius: 16px; box-shadow: 0 5px 15px var(--shadow-color); border: 1px solid var(--border-color); overflow: hidden; } table tbody td { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--border-color); text-align: right; font-size: 0.95rem; } table tbody td:last-child { border-bottom: none; background-color: var(--hover-bg); justify-content: flex-end; gap: 10px; } table tbody td::before { content: attr(data-label); font-weight: 700; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; margin-right: 15px; text-align: left; } table tbody td.empty-state { display: block; text-align: center !important; padding: 30px 20px; } table tbody td.empty-state::before { display: none; } }
    </style>
</head>
<body>
    <header class="top-navbar animate-fade-up">
        <div class="nav-left"><h1 class="nav-title">Finance & Fee System</h1><p class="nav-subtitle">Payment Logging Module</p></div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="../../index.php" class="nav-btn">Dashboard</a> <a href="../fees/index.php" class="nav-btn">Fees</a> <a href="../invoices/index.php" class="nav-btn">Invoices</a> <a href="index.php" class="nav-btn active">Payments</a> <a href="../ledger/index.php" class="nav-btn">Ledger</a> <a href="../scholarships/index.php" class="nav-btn">Scholarships</a> <a href="../reports/index.php" class="nav-btn">Reports</a> <button id="theme-toggle" class="nav-btn">🌙 Mode</button> <a href="../../logout.php" class="nav-btn" style="color: #ef4444;">Logout</a>
        </div>
    </header>

    <div class="container animate-fade-up delay-1">
        <div class="header-flex"><h1>💰 Payment History</h1><button onclick="openModal()" class="btn-add">+ Log Payment</button></div>
        <?php echo $message; ?>
        <div class="table-card">
            <table>
                <thead><tr><th>Receipt No.</th><th>Student ID</th><th>Amount Paid</th><th>Method</th><th>Cashier</th><th>Date Paid</th><th>Action</th></tr></thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) { 
                            echo "<tr>
                                    <td data-label='Receipt No.'><strong>REC-" . str_pad($row['payment_id'], 4, '0', STR_PAD_LEFT) . "</strong></td>
                                    <td data-label='Student ID'>" . htmlspecialchars($row['student_id']) . "</td>
                                    <td data-label='Amount Paid' class='amount'>₱ " . number_format($row['amount_paid'], 2) . "</td>
                                    <td data-label='Method'>" . htmlspecialchars($row['payment_method']) . "</td>
                                    <td data-label='Cashier' style='color:var(--text-secondary);'>" . htmlspecialchars($row['cashier_name'] ?? 'Admin') . "</td>
                                    <td data-label='Date Paid'>" . date("M d, Y h:i A", strtotime($row['payment_date'])) . "</td>
                                    <td data-label='Action'><a href='receipt.php?id={$row['payment_id']}' target='_blank' class='btn-print'>🖨️ Print</a></td>
                                  </tr>"; 
                        }
                    } else { echo "<tr><td colspan='7' class='empty-state' style='color:var(--text-secondary);'>No payment has been recorded.</td></tr>"; }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="addPaymentModal" class="modal-overlay">
        <div class="modal-box">
            <span class="close-btn" onclick="closeModal()">&times;</span><h2>Log Payment</h2>
            <form method="POST" action="">
                <label for="invoice_id">Select Invoice / Student:</label> 
                <select id="invoice_id" name="invoice_id" required>
                    <option value="" disabled selected>Select Invoices</option>
                    <?php
                    if ($invoices_list && $invoices_list->num_rows > 0) {
                        while($inv = $invoices_list->fetch_assoc()) {
                            $balance = $inv['grand_total'] - $inv['total_paid'];
                            $sem_disp = htmlspecialchars($inv['semester'] ?? '1st Sem');
                            echo "<option value='{$inv['invoice_id']}' data-balance='{$balance}'>INV-" . str_pad($inv['invoice_id'], 4, '0', STR_PAD_LEFT) . " | " . htmlspecialchars($inv['student_id']) . " ({$sem_disp}) - Bal: ₱" . number_format($balance, 2) . "</option>";
                        }
                    } else { echo "<option value='' disabled>No pending invoices.</option>"; }
                    ?>
                </select>

                <label for="amount_paid">Amount Paid (₱):</label> 
                <input type="number" id="amount_paid" name="amount_paid" step="0.01" required placeholder="0.00">
                <p style="font-size: 0.8rem; color: #3b82f6; margin-top: 5px; margin-bottom: 0;">* Auto-filled with remaining balance.</p>

                <label for="payment_method">Payment Method:</label>
                <select id="payment_method" name="payment_method" required>
                    <option value="Cash">Cash (Walk-in)</option>
                    <option value="GCash">GCash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>

                <label for="reference_number">Reference Number (Optional):</label> 
                <input type="text" id="reference_number" name="reference_number" placeholder="e.g. Transaction ID">
                
                <button type="submit" name="save_payment_btn" class="modal-submit">Submit Payment</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeBtn = document.getElementById('theme-toggle'); if (localStorage.getItem('theme') === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); themeBtn.innerText = '☀️ Mode'; }
            themeBtn.addEventListener('click', () => { if (document.documentElement.getAttribute('data-theme') === 'dark') { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); themeBtn.innerText = '🌙 Mode'; } else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); themeBtn.innerText = '☀️ Mode'; } });
            const mobileBtn = document.getElementById('mobile-menu-btn'); const navMenu = document.getElementById('nav-menu'); if(mobileBtn) { mobileBtn.addEventListener('click', () => { navMenu.classList.toggle('show-menu'); }); }

            const invoiceSelect = document.getElementById('invoice_id'); const amountInput = document.getElementById('amount_paid');
            invoiceSelect.addEventListener('change', function() { const selectedOption = this.options[this.selectedIndex]; const balance = selectedOption.getAttribute('data-balance'); if(balance) { amountInput.value = parseFloat(balance).toFixed(2); } });
        });
        const modal = document.getElementById('addPaymentModal'); function openModal() { modal.style.display = 'flex'; } function closeModal() { modal.style.display = 'none'; } window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
    </script>
</body>
</html>