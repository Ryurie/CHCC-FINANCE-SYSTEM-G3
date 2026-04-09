<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../../login.php"); exit(); }
require '../../config/db_connect.php';

$message = "";

// 🔥 AUTO-PATCH 1: Fee Details Column
$check_col = $conn->query("SHOW COLUMNS FROM invoices LIKE 'fee_details'");
if ($check_col->num_rows == 0) {
    $conn->query("ALTER TABLE invoices ADD COLUMN fee_details TEXT AFTER student_id");
}

// 🔥 AUTO-PATCH 2: Students Table Checker (WALA NANG DUMMIES DITO)
$conn->query("CREATE TABLE IF NOT EXISTS students (
    student_id VARCHAR(50) PRIMARY KEY,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    course VARCHAR(50)
)");

// KAPAG GUMAWA O NAG-UPDATE NG INVOICE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_invoice_btn'])) {
    $student_id = trim($_POST['student_id']);
    $semester = $_POST['semester'];
    $penalty_input = isset($_POST['penalty']) ? floatval($_POST['penalty']) : 0;
    $form_total_amount = floatval($_POST['total_amount']);
    
    $selected_fees = isset($_POST['selected_fees']) ? $_POST['selected_fees'] : [];

    if (!empty($selected_fees)) {
        
        $new_fees_with_prices = [];
        foreach($selected_fees as $nf) {
            $safe_nf = $conn->real_escape_string(trim($nf));
            $fee_q = $conn->query("SELECT amount FROM fees WHERE fee_name = '$safe_nf'");
            $amt = ($fee_q && $fee_q->num_rows > 0) ? $fee_q->fetch_assoc()['amount'] : 0;
            $new_fees_with_prices[] = trim($nf) . " - ₱" . number_format($amt, 2);
        }

        // I-check kung may existing invoice na si Student para sa sem na ito
        $check_stmt = $conn->prepare("SELECT invoice_id, fee_details, total_amount, penalty FROM invoices WHERE student_id = ? AND semester = ?");
        $check_stmt->bind_param("ss", $student_id, $semester);
        $check_stmt->execute();
        $res = $check_stmt->get_result();

        if ($res->num_rows > 0) {
            // MERGE & UPDATE LOGIC
            $row = $res->fetch_assoc();
            $invoice_id = $row['invoice_id'];
            
            $existing_raw = (!empty($row['fee_details']) && $row['fee_details'] !== "General Billing") ? array_map('trim', explode(', ', $row['fee_details'])) : [];
            $existing_names = [];
            foreach($existing_raw as $er) { $parts = explode(" - ₱", $er); $existing_names[] = trim($parts[0]); }

            $duplicate_found = false; $dup_name = "";
            foreach($selected_fees as $nf) {
                if(in_array(trim($nf), $existing_names)) { $duplicate_found = true; $dup_name = $nf; break; }
            }

            if ($duplicate_found) {
                $message = "<div class='alert-msg alert-error animate-fade-up'>❌ <b>Duplicate Blocked!</b> $student_id already has <i>'$dup_name'</i> for $semester.</div>";
            } else {
                $merged_fees_array = array_merge($existing_raw, $new_fees_with_prices);
                $new_fee_details = implode(", ", $merged_fees_array);

                $new_total_amount = $row['total_amount'] + $form_total_amount;
                $new_penalty = $row['penalty'] + $penalty_input;
                $grand_total = $new_total_amount + $new_penalty;

                $stmt_sum = $conn->prepare("SELECT SUM(amount_paid) as total_paid FROM payments WHERE invoice_id = ?");
                $stmt_sum->bind_param("i", $invoice_id); $stmt_sum->execute();
                $total_paid = $stmt_sum->get_result()->fetch_assoc()['total_paid'] ?? 0;
                
                $new_status = 'Unpaid';
                if ($total_paid > 0) { $new_status = ($total_paid >= $grand_total) ? 'Paid' : 'Partial'; }

                $update_stmt = $conn->prepare("UPDATE invoices SET fee_details = ?, total_amount = ?, penalty = ?, status = ? WHERE invoice_id = ?");
                $update_stmt->bind_param("sddsi", $new_fee_details, $new_total_amount, $new_penalty, $new_status, $invoice_id);
                if ($update_stmt->execute()) { $message = "<div class='alert-msg alert-success animate-fade-up'>✅ <b>Record Updated!</b> Successfully merged fees into existing $semester invoice.</div>"; }
            }
        } else {
            // INSERT NEW LOGIC
            $fee_details = implode(", ", $new_fees_with_prices);
            $status = 'Unpaid'; 
            try {
                $stmt = $conn->prepare("INSERT INTO invoices (student_id, semester, fee_details, total_amount, penalty, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssdds", $student_id, $semester, $fee_details, $form_total_amount, $penalty_input, $status);
                if ($stmt->execute()) { $message = "<div class='alert-msg alert-success animate-fade-up'>✅ Success! New $semester invoice created.</div>"; }
            } catch (mysqli_sql_exception $e) { $message = "<div class='alert-msg alert-error animate-fade-up'>❌ Error: " . $e->getMessage() . "</div>"; }
        }
    } else { $message = "<div class='alert-msg alert-error animate-fade-up'>❌ Error: Please select at least one fee.</div>"; }
}

$query_invoices = "SELECT i.invoice_id, i.student_id, i.semester, i.fee_details, i.total_amount, i.penalty, i.status, IFNULL((SELECT SUM(amount_paid) FROM payments WHERE invoice_id = i.invoice_id), 0) as total_paid FROM invoices i ORDER BY i.invoice_id DESC";
$result = $conn->query($query_invoices);

$fees_list = $conn->query("SELECT * FROM fees ORDER BY category ASC, fee_name ASC");

// Kukunin natin ang mga students para sa Dropdown (Totoong records na lang ang lalabas)
$students_list = $conn->query("SELECT * FROM students ORDER BY last_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - Finance & Fee System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; } :root { --bg-color: #FAF9F7; --card-bg: #FFFFFF; --text-primary: #111827; --text-secondary: #6B7280; --border-color: #E5E7EB; --button-dark: #111827; --button-text: #FFFFFF; --nav-bg: rgba(250, 249, 247, 0.95); --hover-bg: #F3F4F6; --shadow-color: rgba(0,0,0,0.05); } [data-theme="dark"] { --bg-color: #111827; --card-bg: #1F2937; --text-primary: #F9FAFB; --text-secondary: #9CA3AF; --border-color: #374151; --button-dark: #F9FAFB; --button-text: #111827; --nav-bg: rgba(17, 24, 39, 0.95); --hover-bg: #374151; --shadow-color: rgba(0,0,0,0.2); } body { margin: 0; background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-primary); transition: 0.4s; overflow-x: hidden; } @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .animate-fade-up { opacity: 0; animation: fadeInUp 0.6s forwards; } .delay-1 { animation-delay: 0.1s; } .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px); } .nav-left { display: flex; flex-direction: column; } .nav-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; } .nav-subtitle { font-size: 0.85rem; color: var(--text-secondary); margin: 0; } .nav-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end;} .nav-btn { text-decoration: none; padding: 8px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); cursor: pointer; transition: 0.3s; white-space: nowrap; } .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } .nav-btn:hover:not(.active) { background-color: var(--hover-bg); transform: translateY(-2px); } .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; } .container { width: 95%; max-width: 1400px; margin: 40px auto; } .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; } h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin: 0; } .btn-add { background-color: #3b82f6; color: white; padding: 12px 24px; border-radius: 30px; border: none; font-size: 0.95rem; cursor: pointer; font-weight: 600; transition: 0.3s; box-shadow: 0 4px 10px var(--shadow-color);} .btn-add:hover { background-color: #2563eb; transform: translateY(-2px); } .table-card { background-color: var(--card-bg); border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-color); padding: 30px; border: 1px solid var(--border-color); overflow-x: auto; } table { width: 100%; border-collapse: collapse; min-width: 1000px; } th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; } th { color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; } tr:hover td { background-color: var(--hover-bg); } .amount { font-weight: 600; color: var(--text-primary); } .paid-amount { font-weight: 600; color: #3b82f6; } .balance-amount { font-weight: 700; color: #ef4444; } .balance-cleared { font-weight: 700; color: #10b981; } .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: bold; } .status-paid { background: rgba(16, 185, 129, 0.1); color: #10b981; } .status-partial { background: rgba(245, 158, 11, 0.1); color: #f59e0b; } .status-unpaid { background: rgba(239, 68, 68, 0.1); color: #ef4444; } .alert-msg { padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; font-weight: 500; font-size: 0.95rem; } .alert-success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); } .alert-error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); } .btn-view-fees { background: var(--bg-color); color: var(--text-primary); border: 1px solid var(--border-color); padding: 5px 10px; border-radius: 8px; font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: 0.3s; display: inline-flex; align-items: center; gap: 5px; } .btn-view-fees:hover { background: var(--hover-bg); border-color: #3b82f6; color: #3b82f6; } .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 2000; justify-content: center; align-items: center; } .modal-box { background-color: var(--card-bg); padding: 40px; border-radius: 24px; width: 90%; max-width: 500px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2); position: relative; animation: popIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); border: 1px solid var(--border-color); max-height: 90vh; overflow-y: auto; } @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } } .close-btn { position: absolute; top: 20px; right: 25px; font-size: 1.8rem; cursor: pointer; color: var(--text-secondary); transition: 0.2s; line-height: 1; } .close-btn:hover { color: var(--text-primary); transform: scale(1.1); } .modal-box h2 { margin-top: 0; font-family: 'Playfair Display', serif; color: var(--text-primary); font-size: 1.8rem; } .modal-box label { display: block; margin-top: 15px; font-size: 0.9rem; font-weight: 600; color: var(--text-secondary); } .modal-box input[type="text"], .modal-box input[type="number"], .modal-box select { width: 100%; padding: 14px 16px; margin-top: 8px; border: 1px solid var(--border-color); border-radius: 12px; background-color: var(--bg-color); color: var(--text-primary); font-family: 'Inter', sans-serif; box-sizing: border-box; transition: 0.3s; } .modal-box input:focus, .modal-box select:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); } .modal-submit { width: 100%; margin-top: 25px; padding: 16px; border-radius: 12px; background: #10b981; color: white; border: none; font-size: 1rem; font-weight: bold; cursor: pointer; transition: 0.3s; } .modal-submit:hover { background: #059669; } .fees-list-container { margin-top: 10px; border: 1px solid var(--border-color); border-radius: 12px; padding: 15px; max-height: 250px; overflow-y: auto; background-color: var(--bg-color); } .fee-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px dashed var(--border-color); transition: 0.3s; } .fee-item:last-child { border-bottom: none; } .fee-item label { margin: 0; display: flex; align-items: center; gap: 10px; cursor: pointer; color: var(--text-primary); font-weight: 500; font-size: 0.95rem; width: 100%; } .fee-item input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #3b82f6; } .fee-price { color: #10b981; font-weight: 600; font-size: 0.95rem; white-space: nowrap; } .cat-badge { font-size: 0.65rem; background: rgba(107, 114, 128, 0.1); color: var(--text-secondary); padding: 3px 6px; border-radius: 4px; margin-left: 5px; text-transform: uppercase; } .details-ul { list-style: none; padding: 0; margin: 15px 0; border: 1px solid var(--border-color); border-radius: 12px; background: var(--bg-color); overflow: hidden; } .details-ul li { padding: 12px 20px; border-bottom: 1px solid var(--border-color); font-weight: 500; font-size: 0.95rem; color: var(--text-primary); display: flex; align-items: center; justify-content: space-between;} .details-ul li:last-child { border-bottom: none; } .details-ul li span.fee-title { display: flex; align-items: center; } .details-ul li span.fee-title::before { content: "✅"; margin-right: 10px; font-size: 0.8rem; }
        @media (max-width: 900px) { .menu-toggle { display: block; } .nav-left { flex: none; width: 80%; } .nav-right { display: none; flex-direction: column; background-color: var(--card-bg); padding: 15px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 15px 30px var(--shadow-color); margin-top: 15px; align-items: stretch; position: absolute; top: 100%; left: 5%; right: 5%; z-index: 10000; gap: 5px; } .nav-right.show-menu { display: flex; animation: popDown 0.3s forwards; } @keyframes popDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } } .nav-btn { text-align: left; padding: 12px 20px; font-size: 1rem; margin: 0; border-radius: 8px; width: 100%; } }
        @media (max-width: 768px) { .header-flex { flex-direction: column; align-items: stretch; gap: 15px; } h1 { font-size: 2rem; } .btn-add { width: 100%; text-align: center; } .table-card { background: transparent !important; padding: 0 !important; box-shadow: none !important; border: none !important; overflow-x: visible !important; } table, thead, tbody, th, td, tr { display: block; width: 100%; min-width: 0 !important; } table thead { display: none; } table tbody tr { background-color: var(--card-bg); margin-bottom: 20px; border-radius: 16px; box-shadow: 0 5px 15px var(--shadow-color); border: 1px solid var(--border-color); overflow: hidden; } table tbody td { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--border-color); text-align: right; font-size: 0.95rem; } table tbody td:last-child { border-bottom: none; background-color: var(--hover-bg); justify-content: flex-end; gap: 10px; flex-wrap: wrap;} table tbody td::before { content: attr(data-label); font-weight: 700; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; margin-right: 15px; text-align: left; } table tbody td.empty-state { display: block; text-align: center !important; padding: 30px 20px; } table tbody td.empty-state::before { display: none; } }
    </style>
</head>
<body>
    <header class="top-navbar animate-fade-up">
        <div class="nav-left"><h1 class="nav-title">Finance & Fee System</h1><p class="nav-subtitle">Invoice Management</p></div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="../../index.php" class="nav-btn">Dashboard</a> 
            <a href="../fees/index.php" class="nav-btn">Fees</a> 
            <a href="index.php" class="nav-btn active">Invoices</a> 
            <a href="../payments/index.php" class="nav-btn">Payments</a> 
            <a href="../ledger/index.php" class="nav-btn">Ledger</a> 
            <a href="../scholarships/index.php" class="nav-btn">Scholarships</a> 
            <a href="../reports/index.php" class="nav-btn">Reports</a> 
            <button id="theme-toggle" class="nav-btn">🌙 Mode</button> 
            <a href="../../logout.php" class="nav-btn" style="color: #ef4444;">Logout</a>
        </div>
    </header>

    <div class="container animate-fade-up delay-1">
        <div class="header-flex">
            <h1>📑 Student Invoices</h1>
            <button onclick="openModal()" class="btn-add">+ Create Invoice</button>
        </div>
        <?php echo $message; ?>
        <div class="table-card">
            <table>
                <thead>
                    <tr><th>Invoice ID</th><th>Student ID</th><th>Semester</th><th>Fee Details</th><th>Grand Total</th><th>Amount Paid</th><th>Balance</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) { 
                            $grand = $row['total_amount'] + $row['penalty'];
                            $total_paid = $row['total_paid'];
                            $balance = $grand - $total_paid;
                            
                            $status_class = 'status-unpaid';
                            if ($row['status'] == 'Paid') $status_class = 'status-paid';
                            if ($row['status'] == 'Partial') $status_class = 'status-partial';
                            
                            $balance_class = ($balance > 0) ? 'balance-amount' : 'balance-cleared';
                            $fee_details_safe = !empty($row['fee_details']) ? htmlspecialchars($row['fee_details'], ENT_QUOTES) : "";

                            echo "<tr>
                                    <td data-label='Invoice ID'><strong>INV-" . str_pad($row['invoice_id'], 4, '0', STR_PAD_LEFT) . "</strong></td>
                                    <td data-label='Student ID'>" . htmlspecialchars($row['student_id']) . "</td>
                                    <td data-label='Semester'><span style='background:rgba(59, 130, 246, 0.1); color:#3b82f6; padding:4px 8px; border-radius:6px; font-size:0.8rem; font-weight:bold;'>" . htmlspecialchars($row['semester'] ?? '1st Semester') . "</span></td>
                                    <td data-label='Fee Details'><button class='btn-view-fees' onclick='openDetailsModal(\"{$fee_details_safe}\")'>👁️ View Fees</button></td>
                                    <td data-label='Grand Total' class='amount'>₱ " . number_format($grand, 2) . "</td>
                                    <td data-label='Amount Paid' class='paid-amount'>₱ " . number_format($total_paid, 2) . "</td>
                                    <td data-label='Balance' class='{$balance_class}'>₱ " . number_format(max(0, $balance), 2) . "</td>
                                    <td data-label='Status'><span class='status-badge {$status_class}'>" . htmlspecialchars($row['status']) . "</span></td>
                                  </tr>"; 
                        }
                    } else { echo "<tr><td colspan='8' class='empty-state' style='color:var(--text-secondary);'>Walang naka-record na invoices.</td></tr>"; }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="viewDetailsModal" class="modal-overlay">
        <div class="modal-box">
            <span class="close-btn" onclick="closeDetailsModal()">&times;</span>
            <h2>Billed Subjects / Fees</h2>
            <ul class="details-ul" id="details-list-container"></ul>
            <button onclick="closeDetailsModal()" class="modal-submit" style="background: var(--card-bg); color: var(--text-primary); border: 1px solid var(--border-color);">Close Window</button>
        </div>
    </div>

    <div id="addInvoiceModal" class="modal-overlay">
        <div class="modal-box">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2>Create / Update Invoice</h2>
            <form method="POST" action="">
                
                <label for="student_id">Select Enrolled Student:</label> 
                <select id="student_id" name="student_id" required onchange="autoSelectDept()">
                    <option value="" disabled selected>Pumili ng estudyante...</option>
                    <?php
                    if ($students_list && $students_list->num_rows > 0) {
                        while($stud = $students_list->fetch_assoc()) {
                            echo "<option value='{$stud['student_id']}' data-course='{$stud['course']}'>{$stud['student_id']} - {$stud['last_name']}, {$stud['first_name']}</option>";
                        }
                    } else {
                        echo "<option value='' disabled>Walang naka-enroll na estudyante. Magdagdag muna sa Students Database.</option>";
                    }
                    ?>
                </select>

                <label for="semester">Semester:</label>
                <select id="semester" name="semester" style="margin-bottom: 10px;" required>
                    <option value="1st Semester" selected>1st Semester</option>
                    <option value="2nd Semester">2nd Semester</option>
                    <option value="Summer Class">Summer Class</option>
                </select>
                
                <label for="student_dept">Student's Department (Filter):</label>
                <select id="student_dept" style="margin-bottom: 10px;">
                    <option value="All">Show All Fees</option>
                    <option value="BSIT" selected>BSIT</option>
                    <option value="BSBA">BSBA</option>
                    <option value="BSEd">BSEd</option>
                </select>

                <label>Select Applicable Fees:</label>
                <div class="fees-list-container" id="fees-container">
                    <?php
                    if ($fees_list && $fees_list->num_rows > 0) {
                        while($fee = $fees_list->fetch_assoc()) {
                            $cat = !empty($fee['category']) ? htmlspecialchars($fee['category']) : 'General';
                            echo "<div class='fee-item' data-category='{$cat}'><label><input type='checkbox' class='fee-checkbox' name='selected_fees[]' value='" . htmlspecialchars($fee['fee_name']) . "' data-amount='{$fee['amount']}'><span>" . htmlspecialchars($fee['fee_name']) . " <span class='cat-badge'>{$cat}</span></span></label><span class='fee-price'>₱" . number_format($fee['amount'], 2) . "</span></div>";
                        }
                    }
                    ?>
                </div>

                <label for="total_amount">New Fees Total (₱):</label> 
                <input type="number" id="total_amount" name="total_amount" step="0.01" readonly required style="background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; font-weight: bold; border-color: #3b82f6;" value="0.00">
                <label for="penalty">Add Penalty (₱) - Optional:</label> 
                <input type="number" id="penalty" name="penalty" step="0.01" value="0">
                
                <button type="submit" name="add_invoice_btn" class="modal-submit">Save Invoice</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeBtn = document.getElementById('theme-toggle'); if (localStorage.getItem('theme') === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); themeBtn.innerText = '☀️ Mode'; }
            themeBtn.addEventListener('click', () => { if (document.documentElement.getAttribute('data-theme') === 'dark') { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); themeBtn.innerText = '🌙 Mode'; } else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); themeBtn.innerText = '☀️ Mode'; } });
            
            const mobileBtn = document.getElementById('mobile-menu-btn'); const navMenu = document.getElementById('nav-menu'); 
            if(mobileBtn) { mobileBtn.addEventListener('click', () => { navMenu.classList.toggle('show-menu'); }); }

            const deptSelect = document.getElementById('student_dept');
            const feeItems = document.querySelectorAll('.fee-item');
            const checkboxes = document.querySelectorAll('.fee-checkbox');
            const totalInput = document.getElementById('total_amount');

            window.updateFees = function() {
                const selectedDept = deptSelect.value;
                let total = 0;
                feeItems.forEach(item => {
                    const checkbox = item.querySelector('.fee-checkbox');
                    const category = item.getAttribute('data-category');
                    if (selectedDept === 'All' || category === 'General' || category === 'Lab Fees' || category === selectedDept) {
                        item.style.display = 'flex';
                        if (checkbox.checked) { total += parseFloat(checkbox.getAttribute('data-amount')); }
                    } else { item.style.display = 'none'; checkbox.checked = false; }
                });
                totalInput.value = total.toFixed(2);
            }
            deptSelect.addEventListener('change', updateFees); checkboxes.forEach(cb => { cb.addEventListener('change', updateFees); }); updateFees();
        });

        function autoSelectDept() {
            const studSelect = document.getElementById('student_id');
            const selectedOption = studSelect.options[studSelect.selectedIndex];
            const course = selectedOption.getAttribute('data-course');
            
            const deptSelect = document.getElementById('student_dept');
            if(course) {
                for(let i=0; i<deptSelect.options.length; i++) {
                    if(deptSelect.options[i].value === course) {
                        deptSelect.selectedIndex = i;
                        break;
                    }
                }
                updateFees();
            }
        }
        
        const modal = document.getElementById('addInvoiceModal'); function openModal() { modal.style.display = 'flex'; } function closeModal() { modal.style.display = 'none'; } 
        const detailsModal = document.getElementById('viewDetailsModal');
        function openDetailsModal(detailsString) {
            const listContainer = document.getElementById('details-list-container'); listContainer.innerHTML = ''; 
            if(detailsString.trim() === "" || detailsString === "General Billing") { listContainer.innerHTML = "<li><span class='fee-title'>General Billing</span></li>"; } else {
                const feesArray = detailsString.split(', ');
                feesArray.forEach(fee => {
                    if (fee.trim()) { let parts = fee.split(' - ₱'); if(parts.length > 1) { listContainer.innerHTML += `<li><span class='fee-title'>${parts[0].trim()}</span> <strong style='color:#10b981;'>₱${parts[1].trim()}</strong></li>`; } else { listContainer.innerHTML += `<li><span class='fee-title'>${fee.trim()}</span></li>`; } }
                });
            } detailsModal.style.display = 'flex';
        }
        function closeDetailsModal() { detailsModal.style.display = 'none'; }
        window.onclick = function(event) { if (event.target == modal) closeModal(); if (event.target == detailsModal) closeDetailsModal(); }
    </script>
</body>
</html>