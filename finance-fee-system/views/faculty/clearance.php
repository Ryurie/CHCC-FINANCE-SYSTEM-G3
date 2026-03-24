<?php 
// views/faculty/clearance.php
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$clearances = [];
try {
    // Kunin lahat ng clearance requests
    $query = "SELECT c.id, c.status, c.remarks, c.created_at, 
                     s.student_number, u.name as student_name 
              FROM clearances c 
              JOIN students s ON c.student_id = s.id 
              JOIN users u ON s.user_id = u.id 
              ORDER BY 
                CASE WHEN c.status = 'pending' THEN 1 ELSE 2 END, 
                c.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $clearances = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Database Error. Ensure clearances table exists.";
}
?>

<div style="width: 100%; box-sizing: border-box; margin-top: 1rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem; color: #1e293b;">Academic Clearances</h1>
        <p style="color: #64748b; margin-top: 0;">Review and update student clearance statuses for enrollment.</p>
    </div>

    <div class="card" style="padding: 1.5rem; background: #fff; border-radius: 8px; box-shadow: var(--shadow); width: 100%; overflow-x: auto;">
        
        <div style="margin-bottom: 1.5rem; display: flex; gap: 1rem;">
            <input type="text" id="searchInput" placeholder="Search student name or ID..." 
                   style="flex: 2; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 4px;">
            <select id="statusFilter" style="flex: 1; padding: 0.6rem; border: 1px solid var(--border-color); border-radius: 4px;">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>

        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; background-color: #f8fafc;">
                    <th style="padding: 0.75rem 1rem; color: #475569;">Date</th>
                    <th style="padding: 0.75rem 1rem; color: #475569;">Student</th>
                    <th style="padding: 0.75rem 1rem; color: #475569;">Status</th>
                    <th style="padding: 0.75rem 1rem; color: #475569;">Remarks</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; color: #475569;">Actions</th>
                </tr>
            </thead>
            <tbody id="clearanceTableBody">
                <?php if (empty($clearances)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 2rem;">No clearance requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($clearances as $row): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.75rem 1rem; color: #64748b; font-size: 0.9rem;">
                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <div style="font-weight: bold; color: #1e293b;"><?php echo htmlspecialchars($row['student_name']); ?></div>
                                <div style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($row['student_number']); ?></div>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <?php 
                                    $bg = '#fef3c7'; $color = '#92400e';
                                    if ($row['status'] === 'approved') { $bg = '#dcfce3'; $color = '#166534'; }
                                    if ($row['status'] === 'rejected') { $bg = '#fee2e2'; $color = '#991b1b'; }
                                ?>
                                <span class="status-badge" style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td style="padding: 0.75rem 1rem; color: #475569; font-size: 0.9rem;">
                                <?php echo htmlspecialchars($row['remarks'] ?? 'None'); ?>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                <?php if ($row['status'] === 'pending'): ?>
                                    <button onclick="updateClearance(<?php echo $row['id']; ?>, 'approved')" class="btn btn-primary" style="background: #10b981; border: none; padding: 0.3rem 0.6rem; font-size: 0.8rem; margin-right: 0.3rem;">Approve</button>
                                    <button onclick="promptReject(<?php echo $row['id']; ?>)" class="btn btn-outline" style="color: #ef4444; border-color: #ef4444; padding: 0.3rem 0.6rem; font-size: 0.8rem;">Reject</button>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: #94a3b8;">Resolved</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Search and Filter Logic
document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('statusFilter').addEventListener('change', applyFilters);

function applyFilters() {
    const term = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value.toLowerCase();
    const rows = document.querySelectorAll('#clearanceTableBody tr');

    rows.forEach(row => {
        if(row.cells.length < 5) return;
        const text = row.innerText.toLowerCase();
        const rowStatus = row.querySelector('.status-badge').innerText.toLowerCase();
        
        const matchesSearch = text.includes(term);
        const matchesStatus = status === "" || rowStatus === status;
        
        row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
    });
}

// Prompt for rejection reason
function promptReject(id) {
    const reason = prompt("Please enter the reason for rejection (e.g., Missing Library Book):");
    if (reason !== null) {
        updateClearance(id, 'rejected', reason);
    }
}

// AJAX Update API Call
async function updateClearance(id, newStatus, remarks = '') {
    if (newStatus === 'approved' && !confirm("Are you sure you want to approve this student's clearance?")) return;

    try {
        const response = await fetch('../../api/faculty/update_clearance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, status: newStatus, remarks: remarks })
        });
        
        const result = await response.json();
        if (result.success) {
            alert("Clearance successfully updated!");
            location.reload(); // Refresh para mag-update ang table at dashboard numbers
        } else {
            alert("Error: " + result.message);
        }
    } catch (error) {
        alert("System Error. Please check your connection.");
    }
}
</script>

<?php include '../layouts/footer.php'; ?>