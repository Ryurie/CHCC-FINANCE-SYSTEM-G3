<?php 
// views/admin/students.php
session_start();
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$students = [];
try {
    // Kunin ang lahat ng estudyante at i-join sa users table para sa pangalan at email
    $query = "SELECT s.id, s.student_number, s.course, s.year_level, u.name, u.email 
              FROM students s 
              JOIN users u ON s.user_id = u.id 
              ORDER BY u.name ASC";
    $stmt = $db->query($query);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = $e->getMessage();
}
?>

<style>
    .modern-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 1.5rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .search-input {
        padding: 0.75rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        width: 100%;
        max-width: 300px;
        outline: none;
        transition: all 0.2s;
    }
    .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    
    .student-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .student-table th { padding: 1rem; background: #f8fafc; color: #64748b; font-size: 0.8rem; text-transform: uppercase; font-weight: 700; border-bottom: 2px solid #e2e8f0; }
    .student-table td { padding: 1rem; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
    
    .avatar-circle {
        width: 35px; height: 35px; background: #e0e7ff; color: #4338ca;
        border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-weight: bold; font-size: 0.85rem;
    }

    body.dark-mode .modern-card { background: #1e293b; border-color: #334155; }
    body.dark-mode .student-table th { background: #0f172a; color: #94a3b8; border-color: #334155; }
    body.dark-mode .student-table td { color: #e2e8f0; border-color: #334155; }
    body.dark-mode .search-input { background: #0f172a; border-color: #334155; color: white; }
</style>

<div style="width: 100%; margin-top: 1rem;">
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="margin: 0; font-weight: 900; font-size: 2rem; letter-spacing: -0.5px;">Student Directory</h1>
            <p style="color: #64748b; margin: 0.2rem 0 0 0;">Manage and view all registered students in the system.</p>
        </div>
        <input type="text" id="studentSearch" class="search-input" placeholder="🔍 Search name or ID...">
    </div>

    <div class="modern-card" style="padding: 0; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table class="student-table" id="studentsTable">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Student ID</th>
                        <th>Course & Year</th>
                        <th>Email Address</th>
                        <th style="text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 3rem; color: #94a3b8;">No students found in the record.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div class="avatar-circle"><?php echo strtoupper(substr($s['name'], 0, 1)); ?></div>
                                        <div style="font-weight: 700;"><?php echo htmlspecialchars($s['name']); ?></div>
                                    </div>
                                </td>
                                <td style="font-family: monospace; font-weight: 600; color: #3b82f6;">
                                    <?php echo htmlspecialchars($s['student_number']); ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600;"><?php echo htmlspecialchars($s['course']); ?></div>
                                    <div style="font-size: 0.75rem; color: #64748b;">Year Level: <?php echo $s['year_level']; ?></div>
                                </td>
                                <td style="color: #64748b; font-size: 0.9rem;">
                                    <?php echo htmlspecialchars($s['email']); ?>
                                </td>
                                <td style="text-align: center;">
                                    <span style="background: #dcfce3; color: #166534; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 800;">ACTIVE</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Live Search Logic
    document.getElementById('studentSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#studentsTable tbody tr');
        
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include '../layouts/footer.php'; ?>