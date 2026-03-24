document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.getElementById('studentTableBody');

    async function loadStudents() {
        try {
            const response = await fetch('../../api/students/list.php');
            const result = await response.json();

            if (result.success) {
                tableBody.innerHTML = '';
                result.data.forEach(student => {
                    const tr = document.createElement('tr');
                    tr.style.borderBottom = '1px solid var(--border-color)';
                    
                    let badgeClass = student.clearance_status === 'cleared' ? 'badge-student' : 'badge-faculty';
                    
                    tr.innerHTML = `
                        <td style="padding: 0.75rem;">${student.student_number}</td>
                        <td style="padding: 0.75rem;"><strong>${student.name}</strong><br><small>${student.email}</small></td>
                        <td style="padding: 0.75rem;">${student.course} - ${student.year_level}</td>
                        <td style="padding: 0.75rem;"><span class="badge ${badgeClass}">${student.clearance_status}</span></td>
                        <td style="padding: 0.75rem;">
                            <button class="btn btn-outline" style="font-size: 0.8rem;">View Profile</button>
                        </td>
                    `;
                    tableBody.appendChild(tr);
                });
            }
        } catch (error) {
            console.error("Error loading students:", error);
        }
    }

    loadStudents();
});