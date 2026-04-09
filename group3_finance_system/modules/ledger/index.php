<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../../login.php"); exit(); }
require '../../config/db_connect.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_query = "";

if ($search !== "") {
    $search_safe = $conn->real_escape_string($search);
    $search_query = " WHERE i.student_id LIKE '%$search_safe%' OR i.invoice_id LIKE '%$search_safe%' ";
}

// 🔥 KUKUNIN ANG SEMESTER, TINANGGAL ANG WALLET LOGIC 🔥
$query = "SELECT 
            i.invoice_id, 
            i.student_id, 
            i.semester,
            (i.total_amount + i.penalty) as grand_total,
            IFNULL((SELECT SUM(amount_paid) FROM payments WHERE invoice_id = i.invoice_id), 0) as total_paid,
            i.status
          FROM invoices i
          $search_query
          ORDER BY i.invoice_id DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>General Ledger - Finance & Fee System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        :root { --bg-color: #FAF9F7; --card-bg: #FFFFFF; --text-primary: #111827; --text-secondary: #6B7280; --border-color: #E5E7EB; --button-dark: #111827; --button-text: #FFFFFF; --nav-bg: rgba(250, 249, 247, 0.95); --hover-bg: #F3F4F6; --shadow-color: rgba(0,0,0,0.05); } 
        [data-theme="dark"] { --bg-color: #111827; --card-bg: #1F2937; --text-primary: #F9FAFB; --text-secondary: #9CA3AF; --border-color: #374151; --button-dark: #F9FAFB; --button-text: #111827; --nav-bg: rgba(17, 24, 39, 0.95); --hover-bg: #374151; --shadow-color: rgba(0,0,0,0.2); } 
        body { margin: 0; background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-primary); transition: 0.4s; overflow-x: hidden; } 
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } .animate-fade-up { opacity: 0; animation: fadeInUp 0.6s forwards; } .delay-1 { animation-delay: 0.1s; } 
        .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px); } 
        .nav-left { display: flex; flex-direction: column; } .nav-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; } .nav-subtitle { font-size: 0.85rem; color: var(--text-secondary); margin: 0; } 
        .nav-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end;} 
        .nav-btn { text-decoration: none; padding: 8px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); cursor: pointer; transition: 0.3s; white-space: nowrap; } 
        .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } .nav-btn:hover:not(.active) { background-color: var(--hover-bg); transform: translateY(-2px); }
        .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; } 
        
        .container { width: 90%; max-width: 1200px; margin: 40px auto; } 
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; } 
        h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin: 0; } 
        
        .search-form { display: flex; gap: 10px; width: 100%; max-width: 450px; }
        .search-input { flex: 1; padding: 12px 20px; border-radius: 30px; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); font-family: 'Inter', sans-serif; outline: none; transition: 0.3s; }
        .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        .btn-search { background-color: #3b82f6; color: white; padding: 12px 24px; border-radius: 30px; border: none; font-weight: 600; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2);} 
        .btn-search:hover { background-color: #2563eb; transform: translateY(-2px); }
        .btn-clear { background-color: var(--card-bg); color: var(--text-secondary); border: 1px solid var(--border-color); padding: 12px 15px; border-radius: 30px; cursor: pointer; text-decoration: none; display: flex; align-items: center; justify-content: center; transition: 0.3s;}
        .btn-clear:hover { background-color: var(--hover-bg); color: var(--text-primary); }
        
        .table-card { background-color: var(--card-bg); border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-color); padding: 30px; border: 1px solid var(--border-color); overflow-x: auto; } 
        table { width: 100%; border-collapse: collapse; min-width: 800px; } th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); } th { color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; } tr:hover td { background-color: var(--hover-bg); } 
        
        .amount { font-weight: 600; color: var(--text-primary); } .balance-red { font-weight: 700; color: #ef4444; } .balance-green { font-weight: 700; color: #10b981; }
        .status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.85rem; font-weight: bold; } .status-paid { background: rgba(16, 185, 129, 0.1); color: #10b981; } .status-partial { background: rgba(245, 158, 11, 0.1); color: #f59e0b; } .status-unpaid { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        @media (max-width: 900px) { .menu-toggle { display: block; } .nav-left { flex: none; width: 80%; } .nav-right { display: none; flex-direction: column; background-color: var(--card-bg); padding: 15px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 15px 30px var(--shadow-color); margin-top: 15px; align-items: stretch; position: absolute; top: 100%; left: 5%; right: 5%; z-index: 10000; gap: 5px; } .nav-right.show-menu { display: flex; animation: popDown 0.3s forwards; } @keyframes popDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } } .nav-btn { text-align: left; padding: 12px 20px; font-size: 1rem; margin: 0; border-radius: 8px; width: 100%; } }
        @media (max-width: 768px) { .header-flex { flex-direction: column; align-items: stretch; gap: 15px; } h1 { font-size: 2rem; } .search-form { max-width: 100%; flex-wrap: wrap; } .search-input { width: 100%; flex: none; } .btn-search, .btn-clear { flex: 1; text-align: center; } .table-card { background: transparent !important; padding: 0 !important; box-shadow: none !important; border: none !important; overflow-x: visible !important; } table, thead, tbody, th, td, tr { display: block; width: 100%; min-width: 0 !important; } table thead { display: none; } table tbody tr { background-color: var(--card-bg); margin-bottom: 20px; border-radius: 16px; box-shadow: 0 5px 15px var(--shadow-color); border: 1px solid var(--border-color); overflow: hidden; } table tbody td { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--border-color); text-align: right; font-size: 0.95rem; } table tbody td:last-child { border-bottom: none; background-color: var(--hover-bg); justify-content: flex-end; gap: 10px; flex-wrap: wrap;} table tbody td::before { content: attr(data-label); font-weight: 700; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; margin-right: 15px; text-align: left; } table tbody td.empty-state { display: block; text-align: center !important; padding: 30px 20px; } table tbody td.empty-state::before { display: none; } }
    </style>
</head>
<body>
    <header class="top-navbar animate-fade-up">
        <div class="nav-left"><h1 class="nav-title">Finance & Fee System</h1><p class="nav-subtitle">Ledger & Statements</p></div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="../../index.php" class="nav-btn">Dashboard</a> 
            <a href="../fees/index.php" class="nav-btn">Fees</a> 
            <a href="../invoices/index.php" class="nav-btn">Invoices</a> 
            <a href="../payments/index.php" class="nav-btn">Payments</a> 
            <a href="index.php" class="nav-btn active">Ledger</a> 
            <a href="../scholarships/index.php" class="nav-btn">Scholarships</a> 
            <a href="../reports/index.php" class="nav-btn">Reports</a> 
            <button id="theme-toggle" class="nav-btn">🌙 Mode</button> 
            <a href="../../logout.php" class="nav-btn" style="color: #ef4444;">Logout</a>
        </div>
    </header>

    <div class="container animate-fade-up delay-1">
        <div class="header-flex">
            <h1>📖 Student Ledger</h1>
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Search Student ID or INV..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search">🔍 Search</button>
                <?php if($search !== ''): ?> <a href="index.php" class="btn-clear">✖ Clear</a> <?php endif; ?>
            </form>
        </div>
        
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Invoice Ref</th>
                        <th>Student ID</th>
                        <th>Semester</th> <th>Total Billed</th>
                        <th>Amount Paid</th>
                        <th>Rem. Balance</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) { 
                            $balance = $row['grand_total'] - $row['total_paid'];
                            $balance_class = ($balance > 0) ? 'balance-red' : 'balance-green';
                            
                            $status_class = 'status-unpaid';
                            if ($row['status'] == 'Paid') $status_class = 'status-paid';
                            if ($row['status'] == 'Partial') $status_class = 'status-partial';

                            // Fallback kung sakaling lumang record na walang semester
                            $sem_display = !empty($row['semester']) ? htmlspecialchars($row['semester']) : '1st Semester';

                            echo "<tr>
                                    <td data-label='Invoice Ref'><strong>INV-" . str_pad($row['invoice_id'], 4, '0', STR_PAD_LEFT) . "</strong></td>
                                    <td data-label='Student ID'>" . htmlspecialchars($row['student_id']) . "</td>
                                    <td data-label='Semester'><span style='background:rgba(59, 130, 246, 0.1); color:#3b82f6; padding:4px 8px; border-radius:6px; font-size:0.8rem; font-weight:bold;'>" . $sem_display . "</span></td>
                                    <td data-label='Total Billed' class='amount'>₱ " . number_format($row['grand_total'], 2) . "</td>
                                    <td data-label='Amount Paid' style='color: #3b82f6; font-weight: 600;'>₱ " . number_format($row['total_paid'], 2) . "</td>
                                    <td data-label='Rem. Balance' class='{$balance_class}'>₱ " . number_format(max(0, $balance), 2) . "</td>
                                    <td data-label='Status'><span class='status-badge {$status_class}'>" . htmlspecialchars($row['status']) . "</span></td>
                                  </tr>"; 
                        }
                    } else { 
                        if ($search !== '') { echo "<tr><td colspan='7' class='empty-state' style='color:var(--text-secondary);'>Walang nahanap na record para sa '<strong>" . htmlspecialchars($search) . "</strong>'.</td></tr>"; } 
                        else { echo "<tr><td colspan='7' class='empty-state' style='color:var(--text-secondary);'>Walang naka-record sa ledger.</td></tr>"; }
                    }
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