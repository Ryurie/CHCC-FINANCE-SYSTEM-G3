<?php 
// views/registrar/students.php
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$students = [];
try {
    $query = "SELECT s.id, s.student_number, s.course, s.year_level, s.wallet_balance, 
                     u.name, u.email, u.created_at 
              FROM students s 
              JOIN users u ON s.user_id = u.id 
              ORDER BY u.created_at DESC";
    $stmt = $db->query($query);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Failed to load students.";
}
?>

<div style="width: 100%; box-sizing: border-box; margin-top: 1rem;">
    <div class="dashboard-header" style="margin-bottom: 2rem;">
        <h1 style="margin-bottom: 0.5rem; color: #1e293b;">Student Accounts</h1>
        <p style="color: #64748b; margin-top: 0;">Manage registered students, view their balances, or remove dummy records.</p>
    </div>

    <div class="card" style="padding: 1.5rem; background: #fff; border-radius: 8px; box-shadow: var(--shadow); width: 100%; overflow-x: auto;">
        
        <div style="margin-bottom: 1.5rem;">
            <input type="text" id="searchInput" placeholder="Search by Name or Student No..." 
                   style="width: 100%; max-width: 400px; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px;">
        </div>

        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; background-color: #f8fafc;">
                    <th style="padding: 0.75rem 1rem; color: #475569;">Student No.</th>
                    <th style="padding: 0.75rem 1rem; color: #475569;">Full Name</th>
                    <th style="padding: 0.75rem 1rem; color: #475569;">Course & Year</th>
                    <th style="padding: 0.75rem 1rem; color: #475569;">E-Wallet</th>
                    <th style="padding: 0.75rem 1rem; text-align: center; color: #475569;">Action</th>
                </tr>
            </thead>
            <tbody id="studentTableBody">
                <?php if (empty($students)): ?>
                    <tr><td colspan="5" style="text-align:center; padding: 2rem;">No registered students found.</td></tr>
                <?php else: ?>
                    <?php foreach ($students as $stu): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 0.75rem 1rem; font-weight: bold; color: #1e293b;">
                                <?php echo htmlspecialchars($stu['student_number']); ?>
                            </td>
                            <td style="padding: 0.75rem 1rem;">
                                <div style="font-weight: 500; color: #1e293b;"><?php echo htmlspecialchars($stu['name']); ?></div>
                                <div style="font-size: 0.8rem; color: #64748b;"><?php echo htmlspecialchars($stu['email']); ?></div>
                            </td>
                            <td style="padding: 0.75rem 1rem; color: #475569;">
                                <?php echo htmlspecialchars($stu['course'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($stu['year_level'] ?? 'N/A'); ?>
                            </td>
                            <td style="padding: 0.75rem 1rem; font-weight: bold; color: #0ea5e9;">
                                ₱<?php echo number_format($stu['wallet_balance'] ?? 0, 2); ?>
                            </td>
                            <td style="padding: 0.75rem 1rem; text-align: center;">
                                <button onclick="deleteStudent(<?php echo $stu['id']; ?>, '<?php echo addslashes($stu['name']); ?>')" 
                                        class="btn" style="background: #ef4444; color: white; border: none; padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                    🗑️ Remove
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Search Filter Logic
document.getElementById('searchInput').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    const rows = document.querySelectorAll('#studentTableBody tr');
    
    rows.forEach(row => {
        if(row.cells.length < 5) return;
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
    });
});

// Delete Student Logic
async function deleteStudent(id, name) {
    if (!confirm(`Are you sure you want to completely remove ${name}? This action cannot be undone.`)) {
        return;
    }

    try {
        const response = await fetch('../../api/students/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        if (result.success) {
            alert(result.message);
            location.reload(); // I-refresh ang page para mawala sa listahan
        } else {
            alert("Warning: " + result.message);
        }
    } catch (error) {
        alert("System Error. Check your connection.");
    }
}
</script>

<?php include '../layouts/footer.php'; ?>