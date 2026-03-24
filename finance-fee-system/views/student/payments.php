<?php 
// views/student/payments.php
include '../layouts/header.php'; 
include '../layouts/sidebar.php'; 

// Kunin ang Invoice ID mula sa URL kung meron (halimbawa: payments.php?invoice_id=1)
$invoice_id = $_GET['invoice_id'] ?? '';
?>

<div class="dashboard-header" style="margin-bottom: 2rem;">
    <h1>Make a Payment</h1>
    <p>Please fill out the form below to record your transaction.</p>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div id="paymentMessage" class="alert" style="display: none;"></div>

    <form id="paymentForm">
        <div class="form-group">
            <label for="invoice_id">Invoice Reference</label>
            <input type="text" id="invoice_id" name="invoice_id" 
                   value="<?php echo htmlspecialchars($invoice_id); ?>" 
                   placeholder="e.g. 1" required <?php echo $invoice_id ? 'readonly' : ''; ?>>
            <small style="color: var(--text-light);">This is the system reference number for your bill.</small>
        </div>

        <div class="form-group">
            <label for="amount_paid">Amount to Pay ($)</label>
            <input type="number" id="amount_paid" name="amount_paid" step="0.01" min="1" required placeholder="0.00">
        </div>

        <div class="form-group">
            <label for="payment_method">Payment Method</label>
            <select id="payment_method" name="payment_method" class="form-control" 
                    style="width: 100%; padding: 0.6rem; border-radius: var(--radius); border: 1px solid var(--border-color);">
                <option value="bank_transfer">Bank Transfer</option>
                <option value="online">Online Payment (G-Cash/Maya)</option>
                <option value="cash">Cash (Over the counter)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="reference_number">Transaction Reference Number</label>
            <input type="text" id="reference_number" name="reference_number" required placeholder="Enter Ref # from your receipt">
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-primary btn-block" id="submitPaymentBtn">Submit Payment</button>
            <a href="dashboard.php" class="btn btn-outline btn-block" style="margin-top: 0.5rem; text-align: center; text-decoration: none;">Cancel</a>
        </div>
    </form>
</div>

<script>
document.getElementById('paymentForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const msgDiv = document.getElementById('paymentMessage');
    const btn = document.getElementById('submitPaymentBtn');
    
    // Disable button to prevent double clicks
    btn.disabled = true;
    btn.textContent = 'Processing...';

    const formData = {
        invoice_id: document.getElementById('invoice_id').value,
        amount_paid: document.getElementById('amount_paid').value,
        payment_method: document.getElementById('payment_method').value,
        reference_number: document.getElementById('reference_number').value
    };

    try {
        const response = await fetch('../../api/payments/create.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (response.ok && result.success) {
            msgDiv.className = 'alert alert-success'; // Gamitin ang success class (dagdagan mo sa style.css kung wala pa)
            msgDiv.style.backgroundColor = '#dcfce3';
            msgDiv.style.color = '#166534';
            msgDiv.textContent = result.message;
            msgDiv.style.display = 'block';
            
            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = 'dashboard.php';
            }, 2000);
        } else {
            throw new Error(result.message || 'Payment failed');
        }
    } catch (error) {
        msgDiv.className = 'alert alert-danger';
        msgDiv.style.display = 'block';
        msgDiv.textContent = error.message;
        btn.disabled = false;
        btn.textContent = 'Submit Payment';
    }
});
</script>

<?php include '../layouts/footer.php'; ?>