<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: ../../login.php"); exit(); }
require '../../config/db_connect.php';

$message = "";

// KAPAG GUMAWA NG BAGONG FEE (ADD FEE)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_fee_btn'])) {
    $fee_name = $_POST['fee_name'];
    $category = $_POST['category']; // NEW: Kukunin na natin ang category
    $amount = floatval($_POST['amount']);
    $description = $_POST['description'];

    try {
        $stmt = $conn->prepare("INSERT INTO fees (fee_name, category, amount, description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $fee_name, $category, $amount, $description);
        
        if ($stmt->execute()) {
            $message = "<div class='alert-msg alert-success animate-fade-up'>✅ Success! New fee successfully added.</div>";
        }
    } catch (mysqli_sql_exception $e) {
        $message = "<div class='alert-msg alert-error animate-fade-up'>❌ Error: " . $e->getMessage() . "</div>";
    }
}

// KAPAG NAG-DELETE NG FEE
if (isset($_GET['delete'])) {
    $id_to_delete = intval($_GET['delete']);
    try {
        $conn->query("DELETE FROM fees WHERE fee_id = $id_to_delete");
        header("Location: index.php");
        exit();
    } catch (mysqli_sql_exception $e) {
        $message = "<div class='alert-msg alert-error animate-fade-up'>❌ Cannot delete this fee. It might be linked to existing student invoices.</div>";
    }
}

// FILTER LOGIC
$cat_filter = isset($_GET['category']) ? $_GET['category'] : 'All';
$query = "SELECT * FROM fees";
if ($cat_filter !== 'All') {
    $safe_cat = $conn->real_escape_string($cat_filter);
    $query .= " WHERE category = '$safe_cat'";
}
$query .= " ORDER BY category ASC, fee_name ASC";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Management - Finance & Fee System</title>
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
        .nav-btn.active { background-color: var(--button-dark); color: var(--button-text); border-color: var(--button-dark); } 
        .nav-btn:hover:not(.active) { background-color: var(--hover-bg); transform: translateY(-2px); }
        .menu-toggle { display: none; background: none; border: none; font-size: 1.8rem; color: var(--text-primary); cursor: pointer; } 
        
        .container { width: 90%; max-width: 1200px; margin: 40px auto; } 
        .header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; } 
        h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin: 0; } 
        
        /* ACTIONS (FILTER + BUTTON) */
        .actions-wrapper { display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
        .filter-select { padding: 10px 15px; border-radius: 30px; border: 1px solid var(--border-color); background-color: var(--card-bg); color: var(--text-primary); font-family: 'Inter', sans-serif; font-weight: 500; outline: none; cursor: pointer; }
        .btn-add { background-color: var(--button-dark); color: var(--button-text); padding: 10px 24px; border-radius: 30px; border: none; font-size: 0.95rem; cursor: pointer; font-weight: 600; transition: 0.3s; box-shadow: 0 4px 10px var(--shadow-color);} 
        .btn-add:hover { transform: translateY(-2px); } 
        
        .table-card { background-color: var(--card-bg); border-radius: 20px; box-shadow: 0 4px 20px var(--shadow-color); padding: 30px; border: 1px solid var(--border-color); overflow-x: auto; } 
        table { width: 100%; border-collapse: collapse; min-width: 600px; } th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border-color); } th { color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; } tr:hover td { background-color: var(--hover-bg); } .amount { font-weight: 600; color: #10b981; } 
        
        /* CATEGORY BADGE */
        .cat-badge { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; }

        .btn-edit { background: #3b82f6; color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.8rem; font-weight: bold; margin-right: 5px; transition: 0.2s;} .btn-edit:hover { background: #2563eb; }
        .btn-delete { background: #ef4444; color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.8rem; font-weight: bold; transition: 0.2s;} .btn-delete:hover { background: #dc2626; }

        .alert-msg { padding: 15px 20px; border-radius: 12px; margin-bottom: 25px; font-weight: 500; font-size: 0.95rem; } .alert-success { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); } .alert-error { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
        
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); z-index: 2000; justify-content: center; align-items: center; } 
        .modal-box { background-color: var(--card-bg); padding: 40px; border-radius: 24px; width: 90%; max-width: 500px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2); position: relative; animation: popIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); border: 1px solid var(--border-color); } @keyframes popIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } } 
        .close-btn { position: absolute; top: 20px; right: 25px; font-size: 1.8rem; cursor: pointer; color: var(--text-secondary); transition: 0.2s; line-height: 1; } .close-btn:hover { color: var(--text-primary); transform: scale(1.1); } 
        .modal-box h2 { margin-top: 0; font-family: 'Playfair Display', serif; color: var(--text-primary); font-size: 1.8rem; } 
        .modal-box label { display: block; margin-top: 15px; font-size: 0.9rem; font-weight: 600; color: var(--text-secondary); } 
        .modal-box input, .modal-box select, .modal-box textarea { width: 100%; padding: 14px 16px; margin-top: 8px; border: 1px solid var(--border-color); border-radius: 12px; background-color: var(--bg-color); color: var(--text-primary); font-family: 'Inter', sans-serif; box-sizing: border-box; transition: 0.3s; } 
        .modal-box input:focus, .modal-box select:focus, .modal-box textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); } 
        .modal-submit { width: 100%; margin-top: 25px; padding: 16px; border-radius: 12px; background: #10b981; color: white; border: none; font-size: 1rem; font-weight: bold; cursor: pointer; transition: 0.3s; } .modal-submit:hover { background: #059669; }

        @media (max-width: 900px) { 
            .menu-toggle { display: block; } .nav-left { flex: none; width: 80%; } 
            .nav-right { display: none; flex-direction: column; background-color: var(--card-bg); padding: 15px; border-radius: 16px; border: 1px solid var(--border-color); box-shadow: 0 15px 30px var(--shadow-color); margin-top: 15px; align-items: stretch; position: absolute; top: 100%; left: 5%; right: 5%; z-index: 10000; gap: 5px; } 
            .nav-right.show-menu { display: flex; animation: popDown 0.3s forwards; } 
            @keyframes popDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } } 
            .nav-btn { text-align: left; padding: 12px 20px; font-size: 1rem; margin: 0; border-radius: 8px; width: 100%; } 
        }

        @media (max-width: 768px) {
            .header-flex { flex-direction: column; align-items: stretch; gap: 15px; } h1 { font-size: 2rem; }
            .actions-wrapper { flex-direction: column; width: 100%; }
            .filter-select { width: 100%; }
            .btn-add { width: 100%; text-align: center; }
            .table-card { background: transparent !important; padding: 0 !important; box-shadow: none !important; border: none !important; overflow-x: visible !important; } 
            table, thead, tbody, th, td, tr { display: block; width: 100%; min-width: 0 !important; } table thead { display: none; }
            table tbody tr { background-color: var(--card-bg); margin-bottom: 20px; border-radius: 16px; box-shadow: 0 5px 15px var(--shadow-color); border: 1px solid var(--border-color); overflow: hidden; }
            table tbody td { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; border-bottom: 1px solid var(--border-color); text-align: right; font-size: 0.95rem; }
            table tbody td:last-child { border-bottom: none; background-color: var(--hover-bg); justify-content: flex-end; gap: 10px; }
            table tbody td::before { content: attr(data-label); font-weight: 700; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; margin-right: 15px; text-align: left; }
            table tbody td.empty-state { display: block; text-align: center !important; padding: 30px 20px; } table tbody td.empty-state::before { display: none; }
        }
    </style>
</head>
<body>
    <header class="top-navbar animate-fade-up">
        <div class="nav-left"><h1 class="nav-title">Finance & Fee System</h1><p class="nav-subtitle">Fee Management Module</p></div>
        <button id="mobile-menu-btn" class="menu-toggle">☰</button>
        <div class="nav-right" id="nav-menu">
            <a href="../../index.php" class="nav-btn">Dashboard</a> 
            <a href="index.php" class="nav-btn active">Fees</a> 
            <a href="../invoices/index.php" class="nav-btn">Invoices</a> 
            <a href="../payments/index.php" class="nav-btn">Payments</a> 
            <a href="../ledger/index.php" class="nav-btn">Ledger</a> 
            <a href="../scholarships/index.php" class="nav-btn">Scholarships</a> 
            <a href="../reports/index.php" class="nav-btn">Reports</a> 
            <button id="theme-toggle" class="nav-btn">🌙 Mode</button> 
            <a href="../../logout.php" class="nav-btn" style="background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);">Logout</a>
        </div>
    </header>

    <div class="container animate-fade-up delay-1">
        <div class="header-flex">
            <h1>📚 Fee Structures</h1>
            
            <div class="actions-wrapper">
                <form method="GET" action="">
                    <select name="category" class="filter-select" onchange="this.form.submit()">
                        <option value="All" <?php if($cat_filter=='All') echo 'selected'; ?>>All Departments / Categories</option>
                        <option value="General" <?php if($cat_filter=='General') echo 'selected'; ?>>General / Misc</option>
                        <option value="BSIT" <?php if($cat_filter=='BSIT') echo 'selected'; ?>>BSIT</option>
                        <option value="BSBA" <?php if($cat_filter=='BSBA') echo 'selected'; ?>>BSBA</option>
                        <option value="BSEd" <?php if($cat_filter=='BSEd') echo 'selected'; ?>>BSEd</option>
                        <option value="Lab Fees" <?php if($cat_filter=='Lab Fees') echo 'selected'; ?>>Laboratory Fees</option>
                    </select>
                </form>
                <button onclick="openModal()" class="btn-add">+ Add New Fee</button>
            </div>
        </div>
        
        <?php echo $message; ?>
        
        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Fee Name / Title</th>
                        <th>Amount (₱)</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) { 
                            $desc_display = !empty($row['description']) ? htmlspecialchars($row['description']) : '<span style="color:#9ca3af; font-style:italic;">No description</span>';
                            // Kapag walang category sa luma, default ay 'General'
                            $cat_display = !empty($row['category']) ? htmlspecialchars($row['category']) : 'General';

                            echo "<tr>
                                    <td data-label='Category'><span class='cat-badge'>" . $cat_display . "</span></td>
                                    <td data-label='Fee Name / Title'><strong>" . htmlspecialchars($row['fee_name']) . "</strong></td>
                                    <td data-label='Amount (₱)' class='amount'>₱ " . number_format($row['amount'], 2) . "</td>
                                    <td data-label='Description' style='max-width: 250px; font-size: 14px; color: var(--text-secondary);'>" . $desc_display . "</td>
                                    <td data-label='Actions'>
                                        <a href='edit_fee.php?id=" . $row['fee_id'] . "' class='btn-edit'>Edit</a>
                                        <a href='index.php?delete=" . $row['fee_id'] . "' class='btn-delete' onclick='return confirm(\"Are you sure you want to delete this fee?\");'>Delete</a>
                                    </td>
                                  </tr>"; 
                        }
                    } else { 
                        echo "<tr><td colspan='5' class='empty-state' style='color:var(--text-secondary);'>Walang naka-set na fees.</td></tr>"; 
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="addFeeModal" class="modal-overlay">
        <div class="modal-box">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2>Add New Fee</h2>
            <form method="POST" action="">
                
                <label for="category">Category / Department:</label>
                <select id="category" name="category" required>
                    <option value="General">General / Misc</option>
                    <option value="BSIT">BSIT</option>
                    <option value="BSBA">BSBA</option>
                    <option value="BSEd">BSEd</option>
                    <option value="Lab Fees">Laboratory Fees</option>
                </select>

                <label for="fee_name">Fee Name / Title:</label> 
                <input type="text" id="fee_name" name="fee_name" required placeholder="e.g. Tuition Fee, Networking Lab">
                
                <label for="amount">Amount (₱):</label> 
                <input type="number" id="amount" name="amount" step="0.01" required placeholder="0.00">
                
                <label for="description">Description (Optional):</label> 
                <textarea id="description" name="description" rows="3" placeholder="Brief details about this fee..."></textarea>
                
                <button type="submit" name="add_fee_btn" class="modal-submit">Save Fee</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const themeBtn = document.getElementById('theme-toggle'); if (localStorage.getItem('theme') === 'dark') { document.documentElement.setAttribute('data-theme', 'dark'); themeBtn.innerText = '☀️ Mode'; }
            themeBtn.addEventListener('click', () => { if (document.documentElement.getAttribute('data-theme') === 'dark') { document.documentElement.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); themeBtn.innerText = '🌙 Mode'; } else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); themeBtn.innerText = '☀️ Mode'; } });
            const mobileBtn = document.getElementById('mobile-menu-btn'); const navMenu = document.getElementById('nav-menu'); if(mobileBtn) { mobileBtn.addEventListener('click', () => { navMenu.classList.toggle('show-menu'); }); }
        });
        
        const modal = document.getElementById('addFeeModal'); 
        function openModal() { modal.style.display = 'flex'; } 
        function closeModal() { modal.style.display = 'none'; } 
        window.onclick = function(event) { if (event.target == modal) { closeModal(); } }
    </script>
</body>
</html>