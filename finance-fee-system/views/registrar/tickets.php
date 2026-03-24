<?php include '../layouts/header.php'; include '../layouts/sidebar.php'; ?>

<div class="dashboard-header">
    <h1>Student Tickets</h1>
    <p>Manage student concerns and feedback.</p>
</div>

<div class="card">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 2px solid var(--border-color);">
                <th style="padding: 1rem;">Date</th>
                <th style="padding: 1rem;">Student</th>
                <th style="padding: 1rem;">Subject</th>
                <th style="padding: 1rem;">Status</th>
                <th style="padding: 1rem;">Action</th>
            </tr>
        </thead>
        <tbody id="ticketList">
            </tbody>
    </table>
</div>
<?php include '../layouts/footer.php'; ?>