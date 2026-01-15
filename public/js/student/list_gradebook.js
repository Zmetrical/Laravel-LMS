// public/js/student/list_gradebook.js
$(document).ready(function() {
    let currentGrades = [];

    loadGrades();

    function loadGrades() {
        showLoading();

        $.ajax({
            url: API_ROUTES.getGrades,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    currentGrades = response.data;
                    displayGrades(response.data);
                } else {
                    showError(response.message || 'Failed to load grades');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to load grades';
                showError(message);
                toastr.error(message);
            }
        });
    }

    function displayGrades(grades) {
        const container = $('#gradesContainer');
        container.empty();

        if (grades.length === 0) {
            container.html(`
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5>No Classes Enrolled</h5>
                        <p class="text-muted">You are not currently enrolled in any classes for this semester.</p>
                    </div>
                </div>
            `);
            return;
        }

        const row = $('<div class="row"></div>');

        grades.forEach(function(grade) {
            const card = createGradeCard(grade);
            row.append(card);
        });

        container.append(row);
    }

    function createGradeCard(grade) {
        const hasFinal = grade.has_final;
        const finalGrade = hasFinal ? grade.final_grade.final_grade : null;
        const remarks = hasFinal ? grade.final_grade.remarks : null;
        
        const q1Grade = grade.quarter_grades?.q1 ?? null;
        const q2Grade = grade.quarter_grades?.q2 ?? null;
        
        let statusBadge = '';
        
        if (hasFinal) {
            const remarksColor = remarks === 'PASSED' ? 'success' : (remarks === 'FAILED' ? 'danger' : 'secondary');
            statusBadge = `<span class="badge badge-${remarksColor}">${remarks}</span>`;
        } else {
            statusBadge = `<span class="badge badge-secondary">ONGOING</span>`;
        }

        const col = `
            <div class="col-lg-4 col-md-6 col-12 mb-3">
                <div class="card card-outline card-primary h-100">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">
                                ${escapeHtml(grade.class_name)}
                            </h3>
                            ${statusBadge}
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-3">
                            <tbody>
                                <tr>
                                    <td class="font-weight-bold" width="50%">1st Quarter</td>
                                    <td class="text-right">${q1Grade !== null ? q1Grade : '—'}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">2nd Quarter</td>
                                    <td class="text-right">${q2Grade !== null ? q2Grade : '—'}</td>
                                </tr>
                                <tr class="border-top">
                                    <td class="font-weight-bold">Final Grade</td>
                                    <td class="text-right font-weight-bold">${hasFinal ? finalGrade : '—'}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="border-top pt-2">
                            <small class="text-muted d-block mb-2">Grade Components:</small>
                            <div class="d-flex flex-column" style="gap: 0.25rem;">
                                <span class="badge badge-light text-left">Written Work ${grade.ww_percentage}%</span>
                                <span class="badge badge-light text-left">Performance Task ${grade.pt_percentage}%</span>
                                <span class="badge badge-light text-left">Quarterly Assessment ${grade.qa_percentage}%</span>
                            </div>
                        </div>
                        
                        ${grade.teacher_name ? `
                            <div class="mt-2 pt-2 border-top">
                                <small class="text-muted">
                                    <strong>Teacher:</strong> ${escapeHtml(grade.teacher_name)}
                                </small>
                            </div>
                        ` : ''}
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary btn-sm btn-block view-details-btn" 
                                data-class-id="${grade.class_id}">
                            View Details
                        </button>
                    </div>
                </div>
            </div>
        `;

        return col;
    }

    $(document).on('click', '.view-details-btn', function() {
        const classId = $(this).data('class-id');
        const detailsUrl = API_ROUTES.gradeDetails.replace(':classId', classId);
        window.location.href = detailsUrl;
    });

    function showLoading() {
        $('#gradesContainer').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading your grades...</p>
            </div>
        `);
    }

    function showError(message) {
        $('#gradesContainer').html(`
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5>Error Loading Grades</h5>
                    <p class="text-muted">${escapeHtml(message)}</p>
                    <button class="btn btn-primary" onclick="location.reload()">
                        Retry
                    </button>
                </div>
            </div>
        `);
    }

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