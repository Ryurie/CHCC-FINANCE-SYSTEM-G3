<?php 
// views/admin/reports.php
session_start();

// Security Check (Optional, kung wala pa sa header mo)
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'finance')) {
    header("Location: ../auth/login.php");
    exit;
}

include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$total_collected = 0;
try {
    // Kinukuha ang total collection mula sa database
    $total_collected = $db->query("SELECT SUM(amount) FROM payments WHERE status = 'verified'")->fetchColumn() ?: 0;
} catch(PDOException $e) { 
    // Error handling kung sakaling may issue sa DB
}
?>

<style>
    /* =========================================
       BASE STYLES & PRINT LAYOUT
       ========================================= */
    .report-card { 
        background: #ffffff; 
        border-radius: 16px; 
        padding: 2.5rem 2rem; 
        border: 1px solid #e2e8f0; 
        text-align: center; 
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    
    .print-btn { 
        background: #1e293b; 
        color: white; 
        padding: 0.8rem 1.5rem; 
        border-radius: 10px; 
        border: none; 
        font-weight: 700; 
        cursor: pointer; 
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s;
    }

    .print-btn:hover { background: #0f172a; }

    /* =========================================
       🌙 DARK MODE FIXES
       ========================================= */
    body.dark-mode .report-card {
        background: #1e293b !important;
        border-color: #334155 !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
    }

    body.dark-mode .report-card h3 { color: #94a3b8 !important; }
    body.dark-mode .report-card .status-title { color: #f8fafc !important; }
    body.dark-mode .report-card .date-text { color: #cbd5e1 !important; }

    /* Print settings para malinis sa papel */
    @media print {
        .sidebar, .print-btn, #themeToggle, header { display: none !important; }
        .main-content-wrapper { padding: 0 !important; margin: 0 !important; }
        .report-card { border: none !important; box-shadow: none !important; padding: 1rem !important; }
        body { background: white !important; color: black !important; }
    }
</style>

<div style="width: 100%; margin-top: 1rem; padding: 0 1rem;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem;">
        <div>
            <h1 style="margin: 0; font-weight: 900; font-size: 2.2rem; letter-spacing: -0.5px;">Financial Reports</h1>
            <p style="margin: 5px 0 0; color: #64748b;">Overview of system collections and summaries.</p>
        </div>
        <button class="print-btn" onclick="window.print()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Print Report
        </button>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
        
        <div class="report-card">
            <h3 style="color: #64748b; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1.5px; margin-top: 0;">Total Collection (Verified)</h3>
            <p style="font-size: 3.5rem; font-weight: 900; color: #10b981; margin: 1rem 0; letter-spacing: -1px;">
                ₱<?php echo number_format($total_collected, 2); ?>
            </p>
            <div style="height: 4px; background: rgba(16, 185, 129, 0.2); border-radius: 2px; width: 40%; margin: 0 auto;"></div>
        </div>

        <div class="report-card" style="display: flex; flex-direction: column; justify-content: center;">
            <h3 style="color: #64748b; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1.5px; margin-top: 0;">Collection Status</h3>
            <p class="status-title" style="color: #1e293b; font-size: 1.2rem; font-weight: 800; margin-top: 1rem;">Official Summary of Fees and Grants</p>
            <p class="date-text" style="font-size: 0.9rem; color: #64748b; margin-top: 0.5rem;">
                Generated on: <strong style="color: #3b82f6;"><?php echo date('M d, Y h:i A'); ?></strong>
            </p>
        </div>
        
    </div>
</div>

<?php include '../layouts/footer.php'; ?>