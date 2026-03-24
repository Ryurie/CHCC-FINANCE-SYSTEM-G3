document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('adminPaymentBody');

    async function loadPendingPayments() {
        try {
            // Kunin ang lahat ng payments (sa totoong app, lagyan ito ng filter na ?status=pending)
            const response = await fetch('../../api/payments/list.php'); 
            const result = await response.json();

            if (result.success) {
                tableBody.innerHTML = '';
                result.data.filter(p => p.status === 'pending').forEach(payment => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid var(--border-color)';
                    tr.innerHTML = `
                        <td style="padding: 0.75rem;">${payment.payment_date}</td>
                        <td style="padding: 0.75rem;">${payment.student_name || 'Student #'+payment.invoice_id}</td>
                        <td style="padding: 0.75rem;"><strong>$${payment.amount_paid}</strong></td>
                        <td style="padding: 0.75rem;">${payment.payment_method}</td>
                        <td style="padding: 0.75rem;"><code>${payment.reference_number}</code></td>
                        <td style="padding: 0.75rem;">
                            <button onclick="verifyPayment(${payment.id}, 'verified')" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; background-color: var(--success);">Approve</button>
                            <button onclick="verifyPayment(${payment.id}, 'rejected')" class="btn btn-outline" style="padding: 0.25rem 0.5rem; font-size: 0.8rem; color: var(--danger);">Reject</button>
                        </td>
                    `;
                    tableBody.appendChild(tr);
                });
            }
        } catch (error) {
            console.error("Error loading payments:", error);
        }
    }

    // Global function para matawag ng button
    window.verifyPayment = async (id, status) => {
        if (!confirm(`Are you sure you want to set this payment as ${status}?`)) return;

        const response = await fetch('../../api/payments/update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ payment_id: id, status: status })
        });

        const result = await response.json();
        if (result.success) {
            alert(result.message);
            loadPendingPayments(); // Reload table
        }
    };

    loadPendingPayments();
});