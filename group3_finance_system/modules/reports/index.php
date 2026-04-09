<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../../login.php"); exit(); }
require '../../config/db_connect.php';

// 🔥 NEW: DATE RANGE LOGIC 🔥
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Default: First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Default: Last day of current month

// Magdagdag ng time para makuha ang buong araw
$db_start = $start_date . " 00:00:00";
$db_end = $end_date . " 23:59:59";

$stmt_total = $conn->prepare("SELECT SUM(amount_paid) as total_collected FROM payments WHERE payment_date BETWEEN ? AND ?");
$stmt_total->bind_param("ss", $db_start, $db_end); $stmt_total->execute(); 
$grand_total = $stmt_total->get_result()->fetch_assoc()['total_collected'] ?? 0;

$stmt_methods = $conn->prepare("SELECT payment_method, SUM(amount_paid) as method_total FROM payments WHERE payment_date BETWEEN ? AND ? GROUP BY payment_method");
$stmt_methods->bind_param("ss", $db_start, $db_end); $stmt_methods->execute(); 
$methods_result = $stmt_methods->get_result();

$stmt_details = $conn->prepare("SELECT p.payment_id, p.amount_paid, p.payment_method, p.reference_number, p.cashier_name, p.payment_date, i.student_id FROM payments p JOIN invoices i ON p.invoice_id = i.invoice_id WHERE p.payment_date BETWEEN ? AND ? ORDER BY p.payment_date DESC");
$stmt_details->bind_param("ss", $db_start, $db_end); $stmt_details->execute(); 
$details_result = $stmt_details->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Reports - Finance & Fee System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; } :root { --bg-color: #FAF9F7; --card-bg: #FFFFFF; --text-primary: #111827; --text-secondary: #6B7280; --border-color: #E5E7EB; --button-dark: #111827; --button-text: #FFFFFF; --nav-bg: rgba(250, 249, 247, 0.95); --hover-bg: #F3F4F6; --shadow-color: rgba(0,0,0,0.05); } [data-theme="dark"] { --bg-color: #111827; --card-bg: #1F2937; --text-primary: #F9FAFB; --text-secondary: #9CA3AF; --border-color: #374151; --button-dark: #F9FAFB; --button-text: #111827; --nav-bg: rgba(17, 24, 39, 0.95); --hover-bg: #374151; --shadow-color: rgba(0,0,0,0.2); } body { margin: 0; background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-primary); transition: background-color 0.4s, color 0.4s; overflow-x: hidden;} @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .animate-fade-up { opacity: 0; animation: fadeInUp 0.6s forwards; } .delay-1 { animation-delay: 0.1s; } .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px); } .nav-left { display: flex; flex-direction: column; } .nav-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; } .nav-subtitle { font-size: 0.85rem; color: var(--text-secondary); margin: 0; } .nav-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end;} .nav-btn { text-decoration: none; padding: 8px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); } .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; } .container { width: 90%; max-width: 1200px; margin: 40px auto; } .page-header { text-align: center; margin-bottom: 40px; } .page-header h1 { font-family: 'Playfair Display', serif; font-size: 2.8rem; margin: 0; }
        
        /* UPDATED FILTER CARD */
        .filter-card { background-color: var(--card-bg); border-radius: 16px; padding: 20px; border: 1px solid var(--border-color); display: flex; justify-content: center; align-items: center; gap: 15px; margin-bottom: 30px; box-shadow: 0 4px 15px var(--shadow-color); flex-wrap: wrap;} 
        .date-group { display: flex; flex-direction: column; text-align: left; }
        .date-group label { font-size: 0.8rem; color: var(--text-secondary); font-weight: bold; margin-bottom: 5px; text-transform: uppercase; }
        .filter-card input[type="date"] { padding: 12px 15px; border-radius: 8px; border: 1px solid var(--border-color); background-color: var(--bg-color); color: var(--text-primary); font-family: 'Inter', sans-serif; outline: none; } 
        .filter-card input[type="date"]:focus { border-color: #3b82f6; }
        .btn-generate { background-color: #10b981; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; margin-top: 18px; transition: 0.3s; }
        .btn-generate:hover { background-color: #059669; }

        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; } .stat-card { background-color: var(--card-bg); border-radius: 20px; padding: 30px; text-align: center; border: 1px solid var(--border-color); box-shadow: 0 4px 20px var(--shadow-color); } .stat-card h3 { font-size: 0.9rem; color: var(--text-secondary); text-transform: uppercase; } .stat-card .amount { font-family: 'Playfair Display', serif; font-size: 2.5rem; font-weight: 700; color: var(--text-primary); margin: 0; } .amount-green { color: #10b981 !important; }
        .breakdown-list { list-style: none; padding: 0; margin: 15px 0 0 0; text-align: left; } .breakdown-list li { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed var(--border-color); font-size: 0.95rem; color: var(--text-secondary); } .breakdown-list li strong { color: var(--text-primary); }
        .table-card { background-color: var(--card-bg); border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-color); padding: 30px; border: 1px solid var(--border-color); overflow-x: auto; } table { width: 100%; border-collapse: collapse; min-width: 800px; margin-top: 15px; } th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); } th { color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; background-color: rgba(0,0,0,0.02); } [data-theme="dark"] th { background-color: rgba(255,255,255,0.02); } tr:hover td { background-color: var(--hover-bg); } .table-amount { font-weight: 600; color: #10b981; } 
        
        @media (max-width: 900px) { .menu-toggle { display: block; } .nav-left { flex: none; width: 80%; } .nav-right { display: none; flex-direction: column; background-color: var(--card-bg); padding: 15px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 15px 30px var(--shadow-color); margin-top: 15px; align-items: stretch; position: absolute; top: 100%; left: 5%; right: 5%; z-index: 10000; gap: 5px; } .nav-right.show-menu { display: flex; animation: popDown 0.3s forwards; } @keyframes popDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } } .nav-btn { text-align: left; padding: 12px 20px; font-size: 1rem; margin: 0; border-radius: 8px; width: 100%; } .summary-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .page-header h1 { font-size: 2rem; } .filter-card { flex-direction: column; align-items: stretch; } .date-group { width: 100%; } .btn-generate { width: 100%; margin-top: 5px; } .table-card { background: transparent !important; padding: 0 !important; box-shadow: none !important; border: none !important; overflow-x: visible !important; } table, thead, tbody, th, td, tr { display: block; width: 100%; min-width: 0 !important; } table thead { display: none; } table tbody tr { background-color: var(--card-bg); margin-bottom: 20px; border-radius: 16px; box-shadow: 0 5px 15px var(--shadow-color); border: 1px solid var(--border-color); overflow: hidden; } table tbody td { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--border-color); text-align: right; font-size: 0.95rem; } table tbody td:last-child { border-bottom: none; background-color: var(--hover-bg); justify-content: flex-end; gap: 10px; } table tbody td::before { content: attr(data-label); font-weight: 700; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; margin-right: 15px; text-align: left; } table tbody td.empty-state { display: block; text-align: center !important; padding: 30px 20px; } table tbody td.empty-state::before { display: none; } }
    </style>
</head>
<body>
    <header class="top-navbar animate-fade-up">
        <div class="nav-left"><h1 class="nav-title">Finance & Fee System</h1><p class="nav-subtitle">End-of-Day / Periodic Reports</p></div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="../../index.php" class="nav-btn">Dashboard</a> <a href="../fees/index.php" class="nav-btn">Fees</a> <a href="../invoices/index.php" class="nav-btn">Invoices</a> <a href="../payments/index.php" class="nav-btn">Payments</a> <a href="../ledger/index.php" class="nav-btn">Ledger</a> <a href="../scholarships/index.php" class="nav-btn">Scholarships</a> <a href="index.php" class="nav-btn active">Reports</a> <button id="theme-toggle" class="nav-btn">🌙 Mode</button> <a href="../../logout.php" class="nav-btn" style="color: #ef4444;">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="page-header animate-fade-up"><h1>📊 Collection Report</h1></div>
        
        <form method="GET" action="" class="filter-card animate-fade-up delay-1">
            <div class="date-group">
                <label for="start_date">Date From:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
            </div>
            <div class="date-group">
                <label for="end_date">Date To:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
            </div>
            <button type="submit" class="btn-generate">Generate Report</button>
        </form>

        <div class="summary-grid animate-fade-up delay-1">
            <div class="stat-card">
                <h3>Total Collections <br><span style="font-size: 0.75rem; font-weight: normal; color: var(--text-secondary);">(<?php echo date("M d, Y", strtotime($start_date)) . " - " . date("M d, Y", strtotime($end_date)); ?>)</span></h3>
                <p class="amount amount-green">₱ <?php echo number_format($grand_total, 2); ?></p>
            </div>
            <div class="stat-card">
                <h3>Collection Breakdown</h3>
                <?php if ($methods_result->num_rows > 0): ?>
                    <ul class="breakdown-list">
                        <?php while($row = $methods_result->fetch_assoc()): ?>
                            <li><span><?php echo htmlspecialchars($row['payment_method']); ?></span><strong>₱ <?php echo number_format($row['method_total'], 2); ?></strong></li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?><p style="color: var(--text-secondary); margin-top: 20px;">No collections recorded in this period.</p><?php endif; ?>
            </div>
        </div>

        <div class="table-card animate-fade-up delay-1">
            <h2>🧾 Detailed Transactions</h2>
            <table>
                <thead><tr><th>Date & Time</th><th>Receipt No.</th><th>Student ID</th><th>Cashier</th><th>Method</th><th>Amount</th></tr></thead>
                <tbody>
                    <?php
                    if ($details_result->num_rows > 0) {
                        while($row = $details_result->fetch_assoc()) { 
                            echo "<tr>
                                    <td data-label='Date & Time'>" . date("M d, h:i A", strtotime($row['payment_date'])) . "</td>
                                    <td data-label='Receipt No.'><strong>REC-" . str_pad($row['payment_id'], 4, '0', STR_PAD_LEFT) . "</strong></td>
                                    <td data-label='Student ID'>" . htmlspecialchars($row['student_id']) . "</td>
                                    <td data-label='Cashier' style='color: var(--text-secondary);'>" . htmlspecialchars($row['cashier_name'] ?? 'Admin') . "</td>
                                    <td data-label='Method'>" . htmlspecialchars($row['payment_method']) . "</td>
                                    <td data-label='Amount' class='table-amount'>₱ " . number_format($row['amount_paid'], 2) . "</td>
                                  </tr>"; 
                        }
                    } else { echo "<tr><td colspan='6' class='empty-state' style='text-align:center; padding: 40px; color:var(--text-secondary);'>No money was received on these dates.</td></tr>"; }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeBtn = document.getElementById('theme-toggle'); if (localStorage.getItem('theme') === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); themeBtn.innerText = '☀️ Mode'; }
            themeBtn.addEventListener('click', () => { if (document.documentElement.getAttribute('data-theme') === 'dark') { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); themeBtn.innerText = '🌙 Mode'; } else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); themeBtn.innerText = '☀️ Mode'; } });
            const mobileBtn = document.getElementById('mobile-menu-btn'); const navMenu = document.getElementById('nav-menu'); if(mobileBtn) { mobileBtn.addEventListener('click', () => { navMenu.classList.toggle('show-menu'); }); }
        });
    </script>
</body>
</html>