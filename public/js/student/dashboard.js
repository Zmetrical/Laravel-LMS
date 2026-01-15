$(document).ready(function() {
    // Load grades on page load
    loadQuarterlyGrades();

    // Refresh button
    $('#refreshGrades').on('click', function() {
        $(this).find('i').addClass('fa-spin');
        loadQuarterlyGrades();
    });

    /**
     * Load quarterly grades
     */
    function loadQuarterlyGrades() {
        $.ajax({
            url: API_ROUTES.getQuarterlyGrades,
            method: 'GET',
            success: function(response) {
                $('#refreshGrades i').removeClass('fa-spin');
                
                if (response.success) {
                    displayQuarterlyGrades(response.data, response.quarters);
                } else {
                    showGradesError();
                }
            },
            error: function(xhr) {
                $('#refreshGrades i').removeClass('fa-spin');
                showGradesError();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load grades',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
            }
        });
    }

    /**
     * Display quarterly grades table
     */
    function displayQuarterlyGrades(data, quarters) {
        const container = $('#gradesTableContainer');
        
        if (data.length === 0) {
            container.html(`
                <div class="text-center py-4">
                    <i class="fas fa-table fa-3x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No grades available</p>
                </div>
            `);
            return;
        }

        let tableHtml = `
            <table class="table table-hover table-grades">
                <thead>
                    <tr>
                        <th>Subject</th>
        `;
        
        // Add quarter headers
        quarters.forEach(q => {
            tableHtml += `<th class="text-center">${escapeHtml(q.name)}</th>`;
        });
        
        tableHtml += `
                        <th class="text-center">Semester Final</th>
                        <th class="text-center">Remarks</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        // Add grade rows
        data.forEach(classData => {
            tableHtml += `<tr>`;
            tableHtml += `<td><strong>${escapeHtml(classData.class_name)}</strong></td>`;
            
            // Add quarter grades
            classData.quarters.forEach(quarter => {
                const grade = quarter.transmuted_grade;
                if (grade !== null) {
                    tableHtml += `
                        <td class="text-center">
                            ${grade}
                        </td>
                    `;
                } else {
                    tableHtml += `<td class="text-center text-muted"><i>N/A</i></td>`;
                }
            });
            
            // Add semester final grade
            if (classData.semester_final && classData.semester_final.final_grade !== null) {
                const finalGrade = classData.semester_final.final_grade;
                
                tableHtml += `
                    <td class="text-center final-grade-col">
                        ${finalGrade}
                    </td>
                `;
                
                // Add remarks
                const remarks = classData.semester_final.remarks;
                
                tableHtml += `
                    <td class="text-center">
                        <strong>${remarks}</strong>
                    </td>
                `;
            } else {
                tableHtml += `
                    <td class="text-center final-grade-col text-muted"><i>N/A</i></td>
                    <td class="text-center text-muted">-</td>
                `;
            }
            
            tableHtml += `</tr>`;
        });
        
        tableHtml += `
                </tbody>
            </table>
        `;
        
        container.html(tableHtml);
    }

    /**
     * Show grades error
     */
    function showGradesError() {
        $('#gradesTableContainer').html(`
            <div class="text-center py-4">
                <i class="fas fa-exclamation-circle fa-2x text-danger mb-2"></i>
                <p class="text-muted">Failed to load grades</p>
            </div>
        `);
    }

    /**
     * Helper function to escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
});