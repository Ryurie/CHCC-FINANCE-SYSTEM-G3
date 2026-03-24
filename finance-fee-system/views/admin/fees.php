<?php 
// views/admin/fees.php
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$fees = [];
try {
    // Kunin lahat ng existing fees sa database
    $stmt = $db->query("SELECT * FROM fees ORDER BY name ASC");
    $fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $error = "Failed to load fees. Make sure the 'fees' table exists.";
}
?>

<style>
    .card-animate {
        transition: transform 0.3s ease, box-shadow 0.3s ease !important;
    }
    .card-animate:hover {
        transform: translateY(-5px) !important;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }
    .btn-animate {
        transition: all 0.2s ease-in-out !important;
        display: inline-block;
    }
    .btn-animate:hover {
        transform: translateY(-2px) scale(1.03) !important;
        filter: brightness(1.1) !important;
    }
</style>

<div style="width: 100%; box-sizing: border-box; margin-top: 1rem;">

    <div class="dashboard-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="margin-bottom: 0.5rem; color: #1e293b;">Fee Management</h1>
            <p style="color: #64748b; margin-top: 0;">Create and manage school fee categories and amounts.</p>
        </div>
        
        <button onclick="openFeeModal()" class="btn btn-primary btn-animate" style="padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 1rem;">
            ➕ Add New Fee
        </button>
    </div>

    <div class="card card-animate" style="padding: 1.5rem; background: #fff; border-radius: 8px; box-shadow: var(--shadow); width: 100%; overflow-x: auto;">
        
        <div style="margin-bottom: 1.5rem;">
            <input type="text" id="feeSearch" placeholder="Search fee name..." 
                   style="width: 100%; max-width: 400px; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px;">
        </div>

        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; background-color: #f8fafc;">
                    <th style="padding: 1rem; color: #475569;">Fee Name</th>
                    <th style="padding: 1rem; color: #475569;">Description</th>
                    <th style="padding: 1rem; color: #475569;">Amount (₱)</th>
                    <th style="padding: 1rem; text-align: center; color: #475569;">Actions</th>
                </tr>
            </thead>
            <tbody id="feeTableBody">
                <?php if (empty($fees)): ?>
                    <tr><td colspan="4" style="text-align:center; padding: 3rem; color: #94a3b8;">No fee categories found. Click 'Add New Fee' to create one.</td></tr>
                <?php else: ?>
                    <?php foreach ($fees as $fee): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                            <td style="padding: 1rem; font-weight: bold; color: #1e293b;">
                                <?php echo htmlspecialchars($fee['name']); ?>
                            </td>
                            <td style="padding: 1rem; color: #64748b; font-size: 0.9rem;">
                                <?php echo htmlspecialchars($fee['description'] ?? 'No description'); ?>
                            </td>
                            <td style="padding: 1rem; font-weight: bold; color: #10b981;">
                                ₱<?php echo number_format($fee['amount'], 2); ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <button onclick="editFee(<?php echo $fee['id']; ?>, '<?php echo addslashes($fee['name']); ?>', '<?php echo addslashes($fee['description']); ?>', <?php echo $fee['amount']; ?>)" 
                                        class="btn-animate" style="background: #e0e7ff; color: #4f46e5; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; font-size: 0.8rem; margin-right: 0.5rem; cursor: pointer;">
                                    ✏️ Edit
                                </button>
                                <button onclick="deleteFee(<?php echo $fee['id']; ?>, '<?php echo addslashes($fee['name']); ?>')" 
                                        class="btn-animate" style="background: #fee2e2; color: #991b1b; border: none; padding: 0.4rem 0.8rem; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">
                                    🗑️ Delete
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<div id="feeModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center;">
    <div class="card card-animate" style="width: 100%; max-width: 450px; background: white; padding: 2.5rem; border-radius: 8px; position: relative;">
        <h2 id="modalTitle" style="margin-top: 0; margin-bottom: 0.5rem;">Add New Fee</h2>
        <p style="color: #64748b; margin-bottom: 1.5rem; font-size: 0.9rem;">Enter the details for the fee category.</p>
        
        <form id="feeForm">
            <input type="hidden" id="fee_id"> <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Fee Name</label>
                <input type="text" id="fee_name" required placeholder="e.g. Tuition Fee, Library Fee" 
                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Amount (₱)</label>
                <input type="number" id="fee_amount" required step="0.01" placeholder="0.00" 
                       style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Description (Optional)</label>
                <textarea id="fee_description" rows="3" placeholder="Brief description of this fee..." 
                          style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; box-sizing: border-box; resize: vertical;"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="button" class="btn btn-outline btn-animate" onclick="closeFeeModal()" style="flex: 1; padding: 0.75rem;">Cancel</button>
                <button type="submit" class="btn btn-primary btn-animate" style="flex: 1; padding: 0.75rem;">Save Fee</button>
            </div>
        </form>
    </div>
</div>

<script>
// Search Logic
document.getElementById('feeSearch').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    document.querySelectorAll('#feeTableBody tr').forEach(row => {
        if(row.cells.length > 1) {
            row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
        }
    });
});

// Modal Controls
function openFeeModal() { 
    document.getElementById('modalTitle').innerText = 'Add New Fee';
    document.getElementById('feeForm').reset();
    document.getElementById('fee_id').value = '';
    document.getElementById('feeModal').style.display = 'flex'; 
}

function closeFeeModal() { 
    document.getElementById('feeModal').style.display = 'none'; 
}

function editFee(id, name, description, amount) {
    document.getElementById('modalTitle').innerText = 'Edit Fee Category';
    document.getElementById('fee_id').value = id;
    document.getElementById('fee_name').value = name;
    document.getElementById('fee_amount').value = amount;
    document.getElementById('fee_description').value = description;
    document.getElementById('feeModal').style.display = 'flex';
}

// Save (Create/Update) Logic
document.getElementById('feeForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const id = document.getElementById('fee_id').value;
    const actionUrl = id ? '../../api/fees/update.php' : '../../api/fees/create.php';
    
    const data = {
        id: id,
        name: document.getElementById('fee_name').value,
        amount: document.getElementById('fee_amount').value,
        description: document.getElementById('fee_description').value
    };

    try {
        const response = await fetch(actionUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        
        if (result.success) {
            alert(result.message);
            location.reload();
        } else {
            alert("Error: " + result.message);
        }
    } catch (error) {
        alert("System error. Please check your API connection.");
    }
});

// Delete Logic
async function deleteFee(id, name) {
    if (!confirm(`Are you sure you want to delete the fee "${name}"? This action cannot be undone.`)) return;

    try {
        const response = await fetch('../../api/fees/delete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        const result = await response.json();
        
        if (result.success) {
            alert("Fee deleted successfully!");
            location.reload();
        } else {
            alert("Warning: Cannot delete this fee because it is already used in student invoices.");
        }
    } catch (error) {
        alert("System error. Please check your connection.");
    }
}
</script>

<?php include '../layouts/footer.php'; ?>