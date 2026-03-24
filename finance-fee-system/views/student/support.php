<?php 
// views/student/support.php
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 
require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$user_id = $_SESSION['user_id'];
$student_id = 0;
$tickets = [];

try {
    // Kunin ang Student ID
    $stmt1 = $db->prepare("SELECT id FROM students WHERE user_id = :uid");
    $stmt1->execute([':uid' => $user_id]);
    $student = $stmt1->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        $student_id = $student['id'];

        // Kunin ang lahat ng tickets ng estudyanteng ito
        $stmt2 = $db->prepare("SELECT * FROM tickets WHERE student_id = :sid ORDER BY created_at DESC");
        $stmt2->execute([':sid' => $student_id]);
        $tickets = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    $error = "Failed to load support tickets.";
}
?>

<style>
    .card-animate { transition: transform 0.3s ease, box-shadow 0.3s ease !important; }
    .card-animate:hover { transform: translateY(-3px) !important; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08) !important; }
    .btn-animate { transition: all 0.2s ease-in-out !important; display: inline-block; }
    .btn-animate:hover { transform: translateY(-2px) scale(1.03) !important; filter: brightness(1.1) !important; }
</style>

<div style="width: 100%; box-sizing: border-box; margin-top: 1rem;">

    <div class="dashboard-header" style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1 style="margin-bottom: 0.5rem; color: #1e293b;">Help & Support</h1>
            <p style="color: #64748b; margin-top: 0;">Need help with your billing? Submit a support ticket to the Cashier/Registrar.</p>
        </div>
        
        <button onclick="openTicketModal()" class="btn btn-primary btn-animate" style="padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 1rem;">
            ✉️ Submit New Ticket
        </button>
    </div>

    <div class="card card-animate" style="padding: 1.5rem; background: #fff; border-radius: 8px; width: 100%; overflow-x: auto;">
        <h2 style="font-size: 1.2rem; margin-top: 0; margin-bottom: 1.5rem; color: #1e293b;">My Support Tickets</h2>

        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; background-color: #f8fafc;">
                    <th style="padding: 1rem; color: #475569;">Ticket ID</th>
                    <th style="padding: 1rem; color: #475569;">Subject & Message</th>
                    <th style="padding: 1rem; color: #475569;">Date Submitted</th>
                    <th style="padding: 1rem; color: #475569;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($tickets)): ?>
                    <tr><td colspan="4" style="text-align:center; padding: 3rem; color: #94a3b8;">You have no active support tickets.</td></tr>
                <?php else: ?>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;">
                            <td style="padding: 1rem; font-weight: bold; color: #64748b;">
                                #TCK-<?php echo str_pad($ticket['id'], 4, '0', STR_PAD_LEFT); ?>
                            </td>
                            <td style="padding: 1rem; max-width: 300px;">
                                <div style="font-weight: bold; color: #1e293b; margin-bottom: 0.2rem;"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                                <div style="font-size: 0.85rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo htmlspecialchars($ticket['message']); ?>
                                </div>
                            </td>
                            <td style="padding: 1rem; color: #64748b; font-size: 0.9rem;">
                                <?php echo date('M d, Y h:i A', strtotime($ticket['created_at'])); ?>
                            </td>
                            <td style="padding: 1rem;">
                                <?php 
                                    $bg = '#fef3c7'; $color = '#92400e'; // Open
                                    if ($ticket['status'] === 'answered') { $bg = '#e0e7ff'; $color = '#4f46e5'; }
                                    if ($ticket['status'] === 'closed') { $bg = '#dcfce3'; $color = '#166534'; }
                                ?>
                                <span style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase;">
                                    <?php echo $ticket['status']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<div id="ticketModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); justify-content: center; align-items: center;">
    <div class="card card-animate" style="width: 100%; max-width: 500px; background: white; padding: 2.5rem; border-radius: 8px; position: relative;">
        <h2 style="margin-top: 0; margin-bottom: 0.5rem; color: #1e293b;">Submit a Concern</h2>
        <p style="color: #64748b; margin-bottom: 1.5rem; font-size: 0.9rem;">Please provide details about your billing issue.</p>
        
        <form id="ticketForm">
            <input type="hidden" id="student_id" value="<?php echo $student_id; ?>">
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #1e293b;">Topic / Subject</label>
                <select id="ticket_subject" required style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; box-sizing: border-box;">
                    <option value="" disabled selected>Select a category...</option>
                    <option value="Missing Payment">My payment is not reflecting</option>
                    <option value="Incorrect Billing">My bill computation is wrong</option>
                    <option value="Scholarship Inquiry">Inquiry about scholarship discount</option>
                    <option value="Clearance Issue">Problem with Academic Clearance</option>
                    <option value="Other">Other Concerns</option>
                </select>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: bold; color: #1e293b;">Detailed Message</label>
                <textarea id="ticket_message" required rows="4" placeholder="Describe your problem here..." 
                          style="width: 100%; padding: 0.75rem; border: 1px solid var(--border-color); border-radius: 4px; box-sizing: border-box; resize: vertical;"></textarea>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                <button type="button" class="btn btn-outline btn-animate" onclick="closeTicketModal()" style="flex: 1; padding: 0.75rem;">Cancel</button>
                <button type="submit" class="btn btn-primary btn-animate" style="flex: 1; padding: 0.75rem;">Submit Ticket</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTicketModal() { 
    document.getElementById('ticketForm').reset();
    document.getElementById('ticketModal').style.display = 'flex'; 
}

function closeTicketModal() { 
    document.getElementById('ticketModal').style.display = 'none'; 
}

document.getElementById('ticketForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';

    const data = {
        student_id: document.getElementById('student_id').value,
        subject: document.getElementById('ticket_subject').value,
        message: document.getElementById('ticket_message').value
    };

    try {
        const response = await fetch('../../api/tickets/create.php', {
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
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Ticket';
        }
    } catch (error) {
        alert("System error. Please check your connection.");
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Ticket';
    }
});
</script>

<?php include '../layouts/footer.php'; ?>