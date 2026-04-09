<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: login.php"); exit(); }
require 'config/db_connect.php';

$admin_name = isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin';

// 1. KUNIN ANG TOTAL COLLECTIONS
$col_query = $conn->query("SELECT SUM(amount_paid) as total FROM payments");
$total_collected = $col_query->fetch_assoc()['total'] ?? 0;

// 2. KUNIN ANG TOTAL RECEIVABLES (UTANG NG MGA BATA)
$inv_query = $conn->query("SELECT SUM(total_amount + penalty) as grand_total FROM invoices");
$total_invoices = $inv_query->fetch_assoc()['grand_total'] ?? 0;
$total_receivables = max(0, $total_invoices - $total_collected);

// 3. KUNIN ANG TOTAL STUDENTS
$stud_query = $conn->query("SELECT COUNT(student_id) as total FROM students");
$total_students = $stud_query->fetch_assoc()['total'] ?? 0;

// 4. KUNIN ANG TOTAL SCHOLARSHIPS GRANTED
$scho_query = $conn->query("SELECT SUM(discount_amount) as total FROM student_scholarships");
$total_scholarships = $scho_query->fetch_assoc()['total'] ?? 0;

// 5. KUNIN ANG RECENT 5 PAYMENTS
$recent_payments = $conn->query("SELECT p.payment_id, p.amount_paid, p.payment_method, p.payment_date, i.student_id FROM payments p JOIN invoices i ON p.invoice_id = i.invoice_id ORDER BY p.payment_date DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Finance & Fee System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; } 
        :root { --bg-color: #FAF9F7; --card-bg: #FFFFFF; --text-primary: #111827; --text-secondary: #6B7280; --border-color: #E5E7EB; --button-dark: #111827; --button-text: #FFFFFF; --nav-bg: rgba(250, 249, 247, 0.95); --hover-bg: #F3F4F6; --shadow-color: rgba(0,0,0,0.05); } 
        [data-theme="dark"] { --bg-color: #111827; --card-bg: #1F2937; --text-primary: #F9FAFB; --text-secondary: #9CA3AF; --border-color: #374151; --button-dark: #F9FAFB; --button-text: #111827; --nav-bg: rgba(17, 24, 39, 0.95); --hover-bg: #374151; --shadow-color: rgba(0,0,0,0.2); } 
        
        body { margin: 0; background-color: var(--bg-color); font-family: 'Inter', sans-serif; color: var(--text-primary); transition: background-color 0.4s, color 0.4s; overflow-x: hidden; } 
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } } 
        .animate-fade-up { opacity: 0; animation: fadeInUp 0.6s forwards; } .delay-1 { animation-delay: 0.1s; } .delay-2 { animation-delay: 0.2s; }
        
        /* NAVBAR */
        .top-navbar { display: flex; justify-content: space-between; align-items: center; padding: 15px 5%; background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 1000; backdrop-filter: blur(10px); } 
        .nav-left { display: flex; flex-direction: column; } .nav-title { font-size: 1.2rem; font-weight: 700; margin: 0 0 4px 0; letter-spacing: 0.5px;} .nav-subtitle { font-size: 0.85rem; color: var(--text-secondary); margin: 0; } 
        .nav-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: flex-end;} 
        .nav-btn { text-decoration: none; padding: 8px 14px; border-radius: 30px; font-size: 0.85rem; font-weight: 500; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); cursor: pointer; transition: 0.3s; white-space: nowrap; } 
        .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } .nav-btn:hover:not(.active) { background-color: var(--hover-bg); transform: translateY(-2px); }
        .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; } 
        
        .container { width: 95%; max-width: 1400px; margin: 40px auto; } 
        
        /* WELCOME BANNER */
        .welcome-banner { background: linear-gradient(135deg, #1e3a8a, #3b82f6); color: white; padding: 40px; border-radius: 24px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;}
        .welcome-text h1 { font-family: 'Playfair Display', serif; font-size: 2.8rem; margin: 0 0 10px 0; }
        .welcome-text p { margin: 0; font-size: 1.1rem; opacity: 0.9; }
        .date-badge { background: rgba(255,255,255,0.2); backdrop-filter: blur(5px); padding: 10px 20px; border-radius: 30px; font-weight: 600; font-size: 0.9rem; }

        /* DASHBOARD GRID */
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background-color: var(--card-bg); border-radius: 20px; padding: 30px; border: 1px solid var(--border-color); box-shadow: 0 4px 20px var(--shadow-color); transition: 0.3s; position: relative; overflow: hidden; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px var(--shadow-color); border-color: #3b82f6; }
        .stat-icon { position: absolute; top: 20px; right: 20px; font-size: 2.5rem; opacity: 0.1; }
        .stat-card h3 { font-size: 0.9rem; color: var(--text-secondary); text-transform: uppercase; margin: 0 0 10px 0; letter-spacing: 1px; }
        .stat-card .amount { font-family: 'Playfair Display', serif; font-size: 2.2rem; font-weight: 700; color: var(--text-primary); margin: 0; }
        .text-green { color: #10b981 !important; } .text-red { color: #ef4444 !important; } .text-blue { color: #3b82f6 !important; }

        /* SPLIT LAYOUT */
        .split-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .content-card { background-color: var(--card-bg); border-radius: 20px; padding: 30px; border: 1px solid var(--border-color); box-shadow: 0 4px 20px var(--shadow-color); }
        .content-card h2 { font-family: 'Playfair Display', serif; margin-top: 0; font-size: 1.5rem; border-bottom: 2px dashed var(--border-color); padding-bottom: 15px; margin-bottom: 20px;}
        
        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px 10px; text-align: left; border-bottom: 1px solid var(--border-color); font-size: 0.95rem; }
        th { color: var(--text-secondary); font-size: 0.8rem; text-transform: uppercase; }
        tr:hover td { background-color: var(--hover-bg); }
        
        /* QUICK ACTIONS */
        .action-grid { display: grid; grid-template-columns: 1fr; gap: 15px; }
        .action-btn { display: flex; align-items: center; gap: 15px; background: var(--bg-color); border: 1px solid var(--border-color); padding: 20px; border-radius: 16px; text-decoration: none; color: var(--text-primary); font-weight: 600; transition: 0.3s; }
        .action-btn:hover { background: #3b82f6; color: white; border-color: #3b82f6; transform: translateX(5px); }
        .action-icon { font-size: 1.5rem; }

        @media (max-width: 900px) { 
            .split-layout { grid-template-columns: 1fr; }
            .menu-toggle { display: block; } .nav-left { flex: none; width: 80%; } .nav-right { display: none; flex-direction: column; background-color: var(--card-bg); padding: 15px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 15px 30px var(--shadow-color); margin-top: 15px; align-items: stretch; position: absolute; top: 100%; left: 5%; right: 5%; z-index: 10000; gap: 5px; } .nav-right.show-menu { display: flex; animation: popDown 0.3s forwards; } @keyframes popDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } } .nav-btn { text-align: left; padding: 12px 20px; font-size: 1rem; margin: 0; border-radius: 8px; width: 100%; } 
        }
        @media (max-width: 768px) { .welcome-text h1 { font-size: 2rem; } table, thead, tbody, th, td, tr { display: block; width: 100%; } table thead { display: none; } table tbody tr { background-color: var(--bg-color); margin-bottom: 15px; border-radius: 12px; padding: 10px; border: 1px solid var(--border-color); } table tbody td { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px dashed var(--border-color); } table tbody td:last-child { border-bottom: none; } table tbody td::before { content: attr(data-label); font-weight: 700; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; } }
    </style>
</head>
<body>
    <header class="top-navbar animate-fade-up">
        <div class="nav-left"><h1 class="nav-title">Finance & Fee System</h1><p class="nav-subtitle">CHCCI Finance Portal</p></div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="index.php" class="nav-btn active">Dashboard</a> 
            <a href="modules/fees/index.php" class="nav-btn">Fees</a> 
            <a href="modules/invoices/index.php" class="nav-btn">Invoices</a> 
            <a href="modules/payments/index.php" class="nav-btn">Payments</a> 
            <a href="modules/ledger/index.php" class="nav-btn">Ledger</a> 
            <a href="modules/scholarships/index.php" class="nav-btn">Scholarships</a> 
            <a href="modules/reports/index.php" class="nav-btn">Reports</a> 
            <button id="theme-toggle" class="nav-btn">🌙 Mode</button> 
            <a href="logout.php" class="nav-btn" style="color: #ef4444;">Logout</a>
        </div>
    </header>

    <div class="container">
        
        <div class="welcome-banner animate-fade-up">
            <div class="welcome-text">
                <h1>Welcome back, <?php echo htmlspecialchars($admin_name); ?>! 👋</h1>
                <p>Here is the overview of the institution's financial status today.</p>
            </div>
            <div class="date-badge">📅 <?php echo date("l, F d, Y"); ?></div>
        </div>

        <div class="dashboard-grid animate-fade-up delay-1">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <h3>Total Collections</h3>
                <p class="amount text-green">₱ <?php echo number_format($total_collected, 2); ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <h3>Total Receivables</h3>
                <p class="amount text-red">₱ <?php echo number_format($total_receivables, 2); ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎓</div>
                <h3>Enrolled Students</h3>
                <p class="amount text-blue"><?php echo number_format($total_students); ?></p>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏅</div>
                <h3>Grants & Scholarships</h3>
                <p class="amount">₱ <?php echo number_format($total_scholarships, 2); ?></p>
            </div>
        </div>

        <div class="split-layout animate-fade-up delay-2">
            
            <div class="content-card">
                <h2>🕒 Recent Transactions</h2>
                <table>
                    <thead>
                        <tr><th>Receipt No.</th><th>Student ID</th><th>Amount</th><th>Method</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($recent_payments && $recent_payments->num_rows > 0) {
                            while($row = $recent_payments->fetch_assoc()) { 
                                echo "<tr>
                                        <td data-label='Receipt No.'><strong>REC-" . str_pad($row['payment_id'], 4, '0', STR_PAD_LEFT) . "</strong></td>
                                        <td data-label='Student ID'>" . htmlspecialchars($row['student_id']) . "</td>
                                        <td data-label='Amount' style='color:#10b981; font-weight:bold;'>₱ " . number_format($row['amount_paid'], 2) . "</td>
                                        <td data-label='Method'>" . htmlspecialchars($row['payment_method']) . "</td>
                                        <td data-label='Date'>" . date("M d, h:i A", strtotime($row['payment_date'])) . "</td>
                                      </tr>"; 
                            }
                        } else { 
                            echo "<tr><td colspan='5' style='text-align:center; color:var(--text-secondary); padding: 30px;'>No transactions yet.</td></tr>"; 
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div class="content-card">
                <h2>⚡ Quick Actions</h2>
                <div class="action-grid">
                    <a href="modules/invoices/index.php" class="action-btn">
                        <span class="action-icon">📑</span> Create New Invoice
                    </a>
                    <a href="modules/payments/index.php" class="action-btn">
                        <span class="action-icon">💵</span> Log Payment
                    </a>
                    <a href="modules/ledger/index.php" class="action-btn">
                        <span class="action-icon">📖</span> View Student Ledger
                    </a>
                    <a href="modules/scholarships/index.php" class="action-btn">
                        <span class="action-icon">🎓</span> Grant Scholarships
                    </a>
                    <a href="modules/reports/index.php" class="action-btn">
                        <span class="action-icon">📊</span> Generate Report
                    </a>
                </div>
            </div>

        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeBtn = document.getElementById('theme-toggle'); 
            if (localStorage.getItem('theme') === 'dark') { 
                document.documentElement.setAttribute('data-theme', 'dark'); 
                themeBtn.innerText = '☀️ Mode'; 
            }
            themeBtn.addEventListener('click', () => { 
                if (document.documentElement.getAttribute('data-theme') === 'dark') { 
                    document.documentElement.removeAttribute('data-theme'); 
                    localStorage.setItem('theme', 'light'); 
                    themeBtn.innerText = '🌙 Mode'; 
                } else { 
                    document.documentElement.setAttribute('data-theme', 'dark'); 
                    localStorage.setItem('theme', 'dark'); 
                    themeBtn.innerText = '☀️ Mode'; 
                } 
            });
            
            const mobileBtn = document.getElementById('mobile-menu-btn'); 
            const navMenu = document.getElementById('nav-menu'); 
            if(mobileBtn) { 
                mobileBtn.addEventListener('click', () => { navMenu.classList.toggle('show-menu'); }); 
            }
        });
    </script>
</body>
</html>