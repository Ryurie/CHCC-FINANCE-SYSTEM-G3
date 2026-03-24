<?php
// views/layouts/sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['role'] ?? '';

// OVERRIDE: Kung faculty ang naka-login
$display_name = $_SESSION['name'] ?? 'User';
if ($role === 'faculty') {
    $display_name = 'CHCC Faculty';
}
?>

<div id="sidebarOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 40; backdrop-filter: blur(2px);"></div>

<div class="main-wrapper" style="display: flex; min-height: 100vh; overflow: hidden; position: relative;">

    <div class="sidebar" id="mainSidebar" style="width: 260px; display: flex; flex-direction: column; padding: 1.5rem; flex-shrink: 0; overflow-y: auto; z-index: 50;">
        
        <div class="sidebar-logo-container" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; padding-bottom: 1rem;">
            
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-radius: 10px; display: flex; justify-content: center; align-items: center; box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4); flex-shrink: 0; transform: rotate(-5deg);">
                    <span style="color: white; font-weight: 900; font-size: 1.1rem; font-family: 'Inter', sans-serif; transform: rotate(5deg);">CH</span>
                </div>
                <div>
                    <h2 class="sidebar-title" style="margin: 0; font-size: 1.15rem; line-height: 1.2; font-weight: 900; letter-spacing: 0.5px;">CHCC <span style="color: #3b82f6;">FINANCE</span></h2>
                    <span class="sidebar-subtitle" style="font-size: 0.65rem; display: block; margin-top: 0.1rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 600;">System Portal</span>
                </div>
            </div>
            
            <button id="themeToggle" style="background: transparent; color: #94a3b8; border: 1px solid #cbd5e1; padding: 0.4rem; border-radius: 6px; cursor: pointer; font-size: 1rem; display: flex; align-items: center; justify-content: center; transition: all 0.3s; margin-left: 5px;" title="Toggle Dark/Light Mode">
                🌙
            </button>
        </div>

        <ul class="sidebar-nav" style="list-style: none; padding: 0; margin: 0; flex: 1;">
            
            <?php if ($role === 'admin'): ?>
                <li class="sidebar-category" style="font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.5rem; margin-top: 0.5rem; font-weight: bold;">Administrator</li>
                <li style="margin-bottom: 0.5rem;"><a href="../admin/dashboard.php" class="nav-link">Dashboard</a></li>
                <li style="margin-bottom: 0.5rem;"><a href="../admin/fees.php" class="nav-link">Fee Management</a></li>
                <li style="margin-bottom: 0.5rem;"><a href="../admin/scholarships.php" class="nav-link">Scholarships</a></li>
                <li style="margin-bottom: 0.5rem;"><a href="../admin/payments.php" class="nav-link">Payment Verification</a></li>
                <li style="margin-bottom: 0.5rem;"><a href="../admin/reports.php" class="nav-link">Financial Reports</a></li>

            <?php elseif ($role === 'registrar'): ?>
                <li class="sidebar-category" style="font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.5rem; margin-top: 0.5rem; font-weight: bold;">Billing Department</li>
                <li style="margin-bottom: 0.5rem;"><a href="../registrar/dashboard.php" class="nav-link">Billing Overview</a></li>
                <li style="margin-bottom: 0.5rem;"><a href="../registrar/students.php" class="nav-link">Student Accounts</a></li>
                <li style="margin-bottom: 0.5rem;"><a href="../registrar/invoices.php" class="nav-link">Invoice Management</a></li>
                <li style="margin-bottom: 0.5rem;"><a href="../registrar/tickets.php" class="nav-link">Support Tickets</a></li>

            <?php elseif ($role === 'faculty'): ?>
                <li class="sidebar-category" style="font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.5rem; margin-top: 0.5rem; font-weight: bold;">Department Faculty</li>
                <li style="margin-bottom: 0.5rem;"><a href="../faculty/dashboard.php" class="nav-link">Dashboard</a></li>
                <li style="margin-bottom: 0.5rem;"><a href="../faculty/clearance.php" class="nav-link">Clearance Approval</a></li>

            <?php elseif ($role === 'student'): ?>
                <li class="sidebar-category" style="font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.5rem; margin-top: 0.5rem; font-weight: bold;">Student Portal</li>
                <li style="margin-bottom: 0.5rem;"><a href="../student/dashboard.php" class="nav-link">My Account</a></li>
                <li style="margin-bottom: 0.5rem;"><a href="../student/history.php" class="nav-link">Payment History</a></li>
                <li style="margin-bottom: 0.5rem;"><a href="../student/support.php" class="nav-link">Help & Support</a></li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-footer-container" style="margin-top: 2rem; padding-top: 1rem;">
            <div style="margin-bottom: 1rem; font-size: 0.85rem; line-height: 1.4;">
                <span class="sidebar-footer-text">Logged in as:</span><br>
                <strong class="sidebar-footer-name" style="font-size: 0.95rem;"><?php echo htmlspecialchars($display_name); ?></strong>
            </div>
            <a href="../../api/auth/logout.php" style="display: block; width: 100%; text-align: center; background-color: #ef4444; color: white; padding: 0.6rem; text-decoration: none; border-radius: 6px; font-weight: bold; transition: background 0.3s; box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2);">
                Logout
            </a>
        </div>

    </div>
    <div class="main-content-wrapper" style="flex: 1; padding: 1rem; overflow-y: auto; height: 100vh; box-sizing: border-box;">
        
        <div class="mobile-topbar" style="display: none; justify-content: space-between; align-items: center; padding: 1rem; background: #ffffff; border-bottom: 1px solid #e2e8f0; margin: -1rem -1rem 1.5rem -1rem; position: sticky; top: -1rem; z-index: 30;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 30px; height: 30px; background: linear-gradient(135deg, #3b82f6, #8b5cf6); border-radius: 8px; display: flex; justify-content: center; align-items: center; box-shadow: 0 2px 5px rgba(59, 130, 246, 0.3);">
                    <span style="color: white; font-weight: 900; font-size: 0.9rem; font-family: 'Inter', sans-serif;">CH</span>
                </div>
                <strong class="mobile-title" style="color: #1e293b; font-size: 1.1rem; font-weight: 800;">CHCC <span style="color: #3b82f6;">FINANCE</span></strong>
            </div>
            <button id="mobileToggleBtn" style="background: transparent; border: none; font-size: 1.5rem; cursor: pointer; color: #1e293b; padding: 0; display: flex; align-items: center; justify-content: center;">
                ☰
            </button>
        </div>