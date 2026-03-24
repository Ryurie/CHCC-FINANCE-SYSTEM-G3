<?php
// views/student/print_invoice.php
session_start();
require_once '../../config/database.php';

// Siguraduhing may naka-login na student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    die("Unauthorized access.");
}

if (!isset($_GET['id'])) {
    die("Invoice ID is missing.");
}

$invoice_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$database = new Database();
$db = $database->getConnection();

try {
    // Kunin ang kumpletong detalye ng resibo, estudyante, at bayad
    $query = "SELECT i.id as inv_id, i.amount_due, i.due_date, i.status, 
                     f.name as fee_name, f.description as fee_desc,
                     s.student_number, u.name as student_name,
                     p.amount as amount_paid, p.created_at as date_paid, p.id as or_number
              FROM invoices i
              JOIN fees f ON i.fee_id = f.id
              JOIN students s ON i.student_id = s.id
              JOIN users u ON s.user_id = u.id
              LEFT JOIN payments p ON p.invoice_id = i.id AND p.status = 'verified'
              WHERE i.id = :inv_id AND s.user_id = :uid";
              
    $stmt = $db->prepare($query);
    $stmt->execute([':inv_id' => $invoice_id, ':uid' => $user_id]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receipt) {
        die("Receipt not found or you don't have permission to view this.");
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Receipt - INV-<?php echo str_pad($receipt['inv_id'], 4, '0', STR_PAD_LEFT); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            background-color: #f1f5f9;
            margin: 0;
            padding: 2rem;
            display: flex;
            justify-content: center;
        }
        .receipt-container {
            background: white;
            width: 100%;
            max-width: 800px;
            padding: 3rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        .header h1 {
            margin: 0;
            color: #3b82f6;
            font-size: 1.8rem;
        }
        .header p {
            margin: 0.2rem 0 0;
            color: #64748b;
            font-size: 0.9rem;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .info-box {
            width: 48%;
        }
        .info-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #94a3b8;
            font-weight: bold;
            margin-bottom: 0.2rem;
        }
        .info-value {
            font-size: 1rem;
            font-weight: 600;
        }
        .table-container {
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background-color: #f8fafc;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid #cbd5e1;
            color: #475569;
        }
        td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .total-row {
            font-size: 1.2rem;
            font-weight: bold;
            color: #10b981;
        }
        .footer {
            margin-top: 3rem;
            text-align: center;
            color: #64748b;
            font-size: 0.85rem;
            border-top: 1px dashed #cbd5e1;
            padding-top: 1.5rem;
        }
        .no-print {
            text-align: center;
            margin-bottom: 2rem;
        }
        .btn-print {
            background-color: #3b82f6;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        /* CSS para maitago ang Print Button kapag nasa papel na */
        @media print {
            body { background-color: white; padding: 0; }
            .receipt-container { box-shadow: none; padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div style="width: 100%; max-width: 800px;">
        <div class="no-print">
            <button class="btn-print" onclick="window.print()">🖨️ Print / Save as PDF</button>
            <button onclick="window.close()" style="background: transparent; border: 1px solid #94a3b8; padding: 0.8rem 1.5rem; border-radius: 4px; cursor: pointer; margin-left: 1rem;">Close</button>
        </div>

        <div class="receipt-container">
            <div class="header">
                <h1>FinanceSys University</h1>
                <p>123 University Avenue, IT Department Building</p>
                <h2 style="margin-top: 1rem; color: #1e293b;">OFFICIAL RECEIPT</h2>
            </div>

            <div class="row">
                <div class="info-box">
                    <div class="info-title">Received From (Student)</div>
                    <div class="info-value"><?php echo htmlspecialchars($receipt['student_name']); ?></div>
                    <div style="color: #64748b; font-size: 0.9rem;"><?php echo htmlspecialchars($receipt['student_number']); ?></div>
                </div>
                <div class="info-box" style="text-align: right;">
                    <div class="info-title">Receipt / O.R. Number</div>
                    <div class="info-value" style="color: #ef4444;">
                        #OR-<?php echo str_pad($receipt['or_number'] ?? rand(1000,9999), 6, '0', STR_PAD_LEFT); ?>
                    </div>
                    <div class="info-title" style="margin-top: 0.5rem;">Date Paid</div>
                    <div class="info-value">
                        <?php echo $receipt['date_paid'] ? date('F d, Y h:i A', strtotime($receipt['date_paid'])) : 'N/A'; ?>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Description / Particulars</th>
                            <th style="text-align: right;">Amount Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($receipt['fee_name']); ?></strong><br>
                                <span style="font-size: 0.85rem; color: #64748b;">Invoice Ref: #INV-<?php echo str_pad($receipt['inv_id'], 4, '0', STR_PAD_LEFT); ?></span>
                            </td>
                            <td style="text-align: right; vertical-align: top;">
                                ₱<?php echo number_format($receipt['amount_due'], 2); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="text-align: right; padding-top: 2rem;"><strong>Total Amount Paid:</strong></td>
                            <td class="total-row" style="text-align: right; padding-top: 2rem;">
                                ₱<?php echo number_format($receipt['amount_paid'] ?? $receipt['amount_due'], 2); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="footer">
                <p>This is a system-generated official receipt. No physical signature is required.</p>
                <p style="font-weight: bold; margin-top: 0.5rem;">Status: <span style="color: #10b981;">VERIFIED & CLEARED</span></p>
            </div>
        </div>
    </div>

    <script>
        // Awtomatikong magbubukas ang Print Dialog pag-load ng page!
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>