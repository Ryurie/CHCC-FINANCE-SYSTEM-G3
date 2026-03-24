// public/js/invoices.js

document.addEventListener('DOMContentLoaded', () => {
    // Hanapin ang table body kung saan natin ilalagay ang data
    const invoiceTableBody = document.querySelector('#invoice-list tbody');

    // Kung walang invoice table sa page na ito, wag na ituloy ang script
    if (!invoiceTableBody) return;

    // Function para kunin ang data sa API
    async function fetchInvoices() {
        try {
            // Ipakita ang loading state
            invoiceTableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 1rem;">Loading invoices...</td></tr>';

            const response = await fetch('../../api/invoices/list.php');
            const result = await response.json();

            if (response.ok && result.success) {
                renderInvoices(result.data);
            } else {
                invoiceTableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--danger); padding: 1rem;">${result.message || 'Failed to load invoices.'}</td></tr>`;
            }
        } catch (error) {
            console.error('Error fetching invoices:', error);
            invoiceTableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--danger); padding: 1rem;">An error occurred while fetching data.</td></tr>';
        }
    }

    // Function para gumawa ng HTML rows base sa data
    function renderInvoices(invoices) {
        if (invoices.length === 0) {
            invoiceTableBody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 1rem;">No invoices found.</td></tr>';
            return;
        }

        invoiceTableBody.innerHTML = ''; // I-clear ang loading text

        invoices.forEach(invoice => {
            // I-format ang pera (e.g. 15000.00 -> $15,000.00)
            const formatter = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD' // Pwede mo itong palitan ng 'PHP' kung peso
            });
            const formattedAmount = formatter.format(invoice.amount_due);

            // I-setup ang badge colors depende sa status
            let badgeStyle = '';
            let actionButton = '';

            if (invoice.status === 'pending') {
                badgeStyle = 'background-color: #fee2e2; color: #991b1b;';
                actionButton = `<a href="payments.php?invoice_id=${invoice.id}" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Pay Now</a>`;
            } else if (invoice.status === 'partial') {
                badgeStyle = 'background-color: #fef3c7; color: #92400e;';
                actionButton = `<a href="payments.php?invoice_id=${invoice.id}" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Pay Balance</a>`;
            } else if (invoice.status === 'paid') {
                badgeStyle = 'background-color: #dcfce3; color: #166534;';
                actionButton = `<span style="color: var(--text-light); font-size: 0.8rem;">Fully Paid</span>`;
            }

            // Gawin ang table row
            const tr = document.createElement('tr');
            tr.style.borderBottom = '1px solid var(--border-color)';
            tr.innerHTML = `
                <td style="padding: 0.75rem;">#INV-${invoice.id.toString().padStart(3, '0')}</td>
                <td style="padding: 0.75rem;">${invoice.fee_name} (${invoice.academic_year})</td>
                <td style="padding: 0.75rem;">${formattedAmount}</td>
                <td style="padding: 0.75rem;">${invoice.due_date}</td>
                <td style="padding: 0.75rem;">
                    <span class="badge" style="${badgeStyle}">${invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1)}</span>
                </td>
                <td style="padding: 0.75rem;">${actionButton}</td>
            `;

            invoiceTableBody.appendChild(tr);
        });
    }

    // Tawagin ang function pagka-load ng page
    fetchInvoices();
});