<?php 
// views/admin/scholarships.php
session_start();
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$message = '';
$msg_color = '';

// =======================================================
// ✨ DIRECT PHP LOGIC (TAGA-SAVE, EDIT, AT DELETE) ✨
// Dito na mismo natin ipapasok sa database para iwas-error!
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. KUNG NAG-ADD NG BAGONG SCHOLARSHIP
    if ($action === 'add') {
        $name = trim($_POST['name']);
        $discount = floatval($_POST['discount']);
        $desc = trim($_POST['description']);

        if (!empty($name) && $discount > 0) {
            try {
                $stmt = $db->prepare("INSERT INTO scholarships (name, description, discount_percentage) VALUES (?, ?, ?)");
                $stmt->execute([$name, $desc, $discount]);
                $message = "Success: '$name' has been added to the database!";
                $msg_color = "#10b981"; // Green
            } catch(PDOException $e) {
                $message = "Error saving data: " . $e->getMessage();
                $msg_color = "#ef4444"; // Red
            }
        }
    } 
    // 2. KUNG NAG-EDIT NG SCHOLARSHIP
    elseif ($action === 'edit') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $discount = floatval($_POST['discount']);
        $desc = trim($_POST['description']);

        if ($id > 0 && !empty($name)) {
            try {
                $stmt = $db->prepare("UPDATE scholarships SET name=?, description=?, discount_percentage=? WHERE id=?");
                $stmt->execute([$name, $desc, $discount, $id]);
                $message = "Success: Scholarship updated!";
                $msg_color = "#10b981";
            } catch(PDOException $e) {
                $message = "Error updating data: " . $e->getMessage();
                $msg_color = "#ef4444";
            }
        }
    }
    // 3. KUNG NAG-DELETE NG SCHOLARSHIP
    elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        if ($id > 0) {
            try {
                $stmt = $db->prepare("DELETE FROM scholarships WHERE id=?");
                $stmt->execute([$id]);
                $message = "Success: Scholarship deleted!";
                $msg_color = "#10b981";
            } catch(PDOException $e) {
                $message = "Error: Cannot delete this grant. It might be in use.";
                $msg_color = "#ef4444";
            }
        }
    }
}

// =======================================================
// KUNIN ANG MGA SCHOLARSHIPS PARA IPAKITA SA TABLE
// =======================================================
$scholarships = [];
try {
    $stmt = $db->query("SELECT * FROM scholarships ORDER BY created_at DESC");
    $scholarships = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $message = "Database Error: " . $e->getMessage();
    $msg_color = "#ef4444";
}
?>

<style>
    .card-animate { transition: transform 0.3s ease, box-shadow 0.3s ease !important; }
    .card-animate:hover { transform: translateY(-3px) !important; box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important; }
    .btn-animate { transition: all 0.2s ease-in-out !important; display: inline-block; cursor: pointer; }
    .btn-animate:hover { transform: translateY(-2px) scale(1.03) !important; filter: brightness(1.1) !important; }
    
    /* MODAL STYLES */
    .modal-overlay { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); justify-content: center; align-items: center; }
    .modal-content { background: #fff; padding: 2rem; border-radius: 12px; width: 100%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); }
</style>

<div style="width: 100%; box-sizing: border-box; margin-top: 1rem;">

    <div class="dashboard-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="margin-bottom: 0.5rem; color: #1e293b; font-weight: 800;">Scholarships & Grants</h1>
            <p style="color: #64748b; margin-top: 0;">Manage discount percentages applied to student fees.</p>
        </div>
        <button onclick="openModal('add')" class="btn btn-primary btn-animate" style="padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: bold; background-color: #8b5cf6; border: none; color: white;">
            ➕ Add New Grant
        </button>
    </div>

    <?php if(!empty($message)): ?>
        <div style="background: <?php echo $msg_color; ?>20; border-left: 5px solid <?php echo $msg_color; ?>; color: <?php echo $msg_color; ?>; padding: 1rem; margin-bottom: 1.5rem; border-radius: 6px; font-weight: bold;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="card card-animate" style="padding: 1.5rem; background: #fff; border-radius: 8px; width: 100%; overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; background-color: #f8fafc;">
                    <th style="padding: 1rem; color: #475569;">Grant Name</th>
                    <th style="padding: 1rem; color: #475569;">Description / Requirements</th>
                    <th style="padding: 1rem; color: #475569;">Discount Value</th>
                    <th style="padding: 1rem; color: #475569; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($scholarships)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 3rem; color: #94a3b8;">
                            No scholarships added yet. Click "Add New Grant" to start.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($scholarships as $row): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 1rem; font-weight: bold; color: #1e293b;">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </td>
                            <td style="padding: 1rem; color: #64748b;">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </td>
                            <td style="padding: 1rem; font-weight: 900; color: #10b981; font-size: 1.1rem;">
                                <?php echo number_format($row['discount_percentage'], 0); ?>%
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <button onclick="openModal('edit', <?php echo $row['id']; ?>, '<?php echo addslashes($row['name']); ?>', '<?php echo addslashes($row['description']); ?>', <?php echo $row['discount_percentage']; ?>)" class="btn-animate" style="background: #e0e7ff; color: #4f46e5; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; font-size: 0.85rem; font-weight: bold; margin-right: 5px;">
                                    Edit
                                </button>
                                
                                <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this grant?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn-animate" style="background: #fee2e2; color: #ef4444; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; font-size: 0.85rem; font-weight: bold;">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<div id="scholModal" class="modal-overlay">
    <div class="modal-content card">
        <h2 id="modalTitle" style="margin-top: 0; color: #1e293b; margin-bottom: 1.5rem;">Add New Grant</h2>
        
        <form method="POST" action="">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="schol_id">
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #475569;">Grant / Scholarship Name</label>
                <input type="text" name="name" id="schol_name" required placeholder="e.g. TES" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #475569;">Discount Value (%)</label>
                <input type="number" name="discount" id="schol_discount" required min="1" max="100" placeholder="e.g. 70" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #475569;">Description / Requirements</label>
                <textarea name="description" id="schol_desc" rows="3" placeholder="e.g. Required GWA: 1.50" style="width: 100%; padding: 0.8rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; resize: vertical;"></textarea>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" onclick="closeModal()" class="btn-animate" style="padding: 0.75rem 1.5rem; border: 1px solid #cbd5e1; background: transparent; color: #475569; border-radius: 6px; font-weight: bold;">Cancel</button>
                <button type="submit" class="btn-animate" style="padding: 0.75rem 1.5rem; border: none; background: #8b5cf6; color: white; border-radius: 6px; font-weight: bold;">Save Grant</button>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('scholModal');
const title = document.getElementById('modalTitle');
const actionInput = document.getElementById('formAction');

function openModal(mode, id = '', name = '', desc = '', discount = '') {
    if (mode === 'edit') {
        title.innerText = 'Edit Scholarship';
        actionInput.value = 'edit';
        document.getElementById('schol_id').value = id;
        document.getElementById('schol_name').value = name;
        document.getElementById('schol_desc').value = desc;
        document.getElementById('schol_discount').value = discount;
    } else {
        title.innerText = 'Add New Grant';
        actionInput.value = 'add';
        document.getElementById('schol_id').value = '';
        document.getElementById('schol_name').value = '';
        document.getElementById('schol_desc').value = '';
        document.getElementById('schol_discount').value = '';
    }
    modal.style.display = 'flex';
}

function closeModal() {
    modal.style.display = 'none';
}
</script>

<?php include '../layouts/footer.php'; ?>