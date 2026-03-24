<?php 
session_start();
require_once '../../config/database.php';

$id = $_GET['id'] ?? 0;
$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT i.*, u.name as student_name, u.email, s.student_number, s.course, f.name as fee_name, f.description as fee_desc
              FROM invoices i 
              JOIN students s ON i.student_id = s.id 
              JOIN users u ON s.user_id = u.id 
              JOIN fees f ON i.fee_id = f.id 
              WHERE i.id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $inv = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inv) die("Invoice not found.");
} catch(PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?php echo str_pad($inv['id'], 5, '0', STR_PAD_LEFT); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; color: #1e293b; margin: 0; padding: 0; background: #f1f5f9; }
        .invoice-box { 
            max-width: 800px; margin: 2rem auto; background: #fff; padding: 3rem; 
            border-radius: 20px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #f1f5f9; padding-bottom: 2rem; margin-bottom: 2rem; }
        .logo-section h1 { margin: 0; color: #3b82f6; font-weight: 800; }
        .invoice-details { text-align: right; }
        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem; }
        .bill-to h4 { margin: 0 0 0.5rem 0; color: #64748b; text-transform: uppercase; font-size: 0.75rem; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 2rem; }
        th { text-align: left; padding: 1rem; background: #f8fafc; color: #64748b; font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 1.5rem 1rem; border-bottom: 1px solid #f1f5f9; }
        
        .total-section { text-align: right; margin-top: 2rem; }
        .total-amount { font-size: 2rem; font-weight: 800; color: #1e293b; }
        
        .status-badge { 
            display: inline-block; padding: 0.5rem 1rem; border-radius: 9999px; 
            font-size: 0.8rem; font-weight: 800; text-transform: uppercase;
        }
        .paid { background: #dcfce3; color: #166534; }
        .pending { background: #fef3c7; color: #92400e; }

        .no-print { text-align: center; margin-bottom: 2rem; }
        .btn-print { 
            background: #3b82f6; color: white; padding: 0.8rem 2rem; border-radius: 10px; 
            border: none; font-weight: 700; cursor: pointer; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        @media print {
            body { background: white; }
            .invoice-box { margin: 0; padding: 0; box-shadow: none; border: none; width: 100%; max-width: none; }
            .no-print { display: none; }
            .invoice-box { border-radius: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-top: 2rem;">
        <button class="btn-print" onclick="window.print()">🖨️ Download / Print PDF</button>
        <p style="font-size: 0.8rem; color: #64748b; margin-top: 10px;">Tip: Select "Save as PDF" in the printer destination.</p>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div class="logo-section">
                <h1>CHCC <span style="color: #1e293b;">FINANCE</span></h1>
                <p style="color: #64748b; font-size: 0.9rem;">Concepcion Holy Cross College<br>Financial Management Office</p>
            </div>
            <div class="invoice-details">
                <h2 style="margin: 0;">INVOICE</h2>
                <p style="color: #64748b; margin: 5px 0;">#INV-<?php echo str_pad($inv['id'], 5, '0', STR_PAD_LEFT); ?></p>
                <div class="status-badge <?php echo $inv['status']; ?>"><?php echo $inv['status']; ?></div>
            </div>
        </div>

        <div class="details-grid">
            <div class="bill-to">
                <h4>Bill To:</h4>
                <p style="margin: 0; font-weight: 800; font-size: 1.1rem;"><?php echo htmlspecialchars($inv['student_name']); ?></p>
                <p style="margin: 5px 0; color: #64748b;"><?php echo htmlspecialchars($inv['student_number']); ?></p>
                <p style="margin: 0; color: #64748b;"><?php echo htmlspecialchars($inv['course']); ?></p>
            </div>
            <div class="bill-to" style="text-align: right;">
                <h4>Date Issued:</h4>
                <p style="margin: 0; font-weight: 700;"><?php echo date('F j, Y', strtotime($inv['created_at'])); ?></p>
                <h4 style="margin-top: 1.5rem;">Due Date:</h4>
                <p style="margin: 0; font-weight: 700; color: #ef4444;"><?php echo date('F j, Y', strtotime($inv['due_date'])); ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div style="font-weight: 800;"><?php echo htmlspecialchars($inv['fee_name']); ?></div>
                        <div style="font-size: 0.85rem; color: #64748b; margin-top: 5px;"><?php echo htmlspecialchars($inv['fee_desc']); ?></div>
                    </td>
                    <td style="text-align: right; font-weight: 800; font-size: 1.1rem;">₱<?php echo number_format($inv['amount_due'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <h4 style="color: #64748b; margin-bottom: 0.5rem;">TOTAL AMOUNT DUE:</h4>
            <div class="total-amount">₱<?php echo number_format($inv['amount_due'], 2); ?></div>
            <p style="margin-top: 3rem; font-size: 0.8rem; color: #94a3b8; font-style: italic;">
                This is a system-generated document for CHCC Finance System.<br>
                Payment must be settled on or before the due date.
            </p>
        </div>
    </div>

</body>
</html>