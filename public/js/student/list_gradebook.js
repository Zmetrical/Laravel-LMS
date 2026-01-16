$(document).ready(function() {
    let currentFilter = 'q1';

    loadGrades(currentFilter);

    $(document).on('click', '.quarter-filter-btn', function() {
        $('.quarter-filter-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('active btn-primary');
        currentFilter = $(this).data('filter');
        loadGrades(currentFilter);
    });

    $(document).on('click', '.view-details-btn', function() {
        const classId = $(this).data('class-id');
        const quarterId = $(this).data('quarter-id');
        
        if (currentFilter === 'final') {
            toastr.info('Please select a specific quarter to view details');
            return;
        }
        
        const detailsUrl = API_ROUTES.gradeDetails
            .replace(':classId', classId)
            .replace(':quarterId', quarterId);
        window.location.href = detailsUrl;
    });

    function loadGrades(filter) {
        showLoading();

        $.ajax({
            url: API_ROUTES.getGrades,
            method: 'GET',
            data: { filter: filter },
            success: function(response) {
                if (response.success) {
                    displayGrades(response.data, filter);
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

    function displayGrades(grades, filter) {
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

        if (filter === 'final') {
            grades.forEach(function(grade) {
                const card = createFinalGradeCard(grade);
                row.append(card);
            });
        } else {
            grades.forEach(function(grade) {
                const card = createQuarterGradeCard(grade);
                row.append(card);
            });
        }

        container.append(row);
    }

    function createQuarterGradeCard(grade) {
        const hasGrade = grade.quarter_grade !== null && grade.quarter_grade !== undefined;
        const gradeDisplay = hasGrade ? parseFloat(grade.quarter_grade).toFixed(2) : '—';
        const gradeBoxClass = hasGrade ? 'has-grade' : 'no-grade';

        const col = `
            <div class="col-lg-4 col-md-6 col-12 mb-3">
                <div class="card card-outline card-primary h-100 grade-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">
                                ${escapeHtml(grade.class_name)}
                            </h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="grade-box ${gradeBoxClass} mb-3">
                            <div class="grade-label">Grade</div>
                            <div class="grade-value">${gradeDisplay}</div>
                        </div>

                        <div class="border-top pt-2 mt-2">
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
                                data-class-id="${grade.class_id}"
                                data-quarter-id="${grade.quarter_id}">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </div>
                </div>
            </div>
        `;

        return col;
    }

    function createFinalGradeCard(grade) {
        const hasFinal = grade.has_final;
        const finalGrade = hasFinal ? parseFloat(grade.final_grade).toFixed(2) : null;
        const remarks = hasFinal ? grade.remarks : null;
        const q1Grade = grade.q1_grade !== null ? parseFloat(grade.q1_grade).toFixed(2) : '—';
        const q2Grade = grade.q2_grade !== null ? parseFloat(grade.q2_grade).toFixed(2) : '—';
        
        let statusBadge = '';
        
        if (hasFinal && remarks === 'PASSED') {
            statusBadge = `<span class="badge badge-success">PASSED</span>`;
        }

        const col = `
            <div class="col-lg-4 col-md-6 col-12 mb-3">
                <div class="card card-outline card-primary h-100 grade-card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">
                                ${escapeHtml(grade.class_name)}
                            </h3>
                            ${statusBadge}
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="grade-box ${q1Grade !== '—' ? 'has-grade' : 'no-grade'}">
                                    <div class="grade-label">Q1</div>
                                    <div class="grade-value">${q1Grade}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="grade-box ${q2Grade !== '—' ? 'has-grade' : 'no-grade'}">
                                    <div class="grade-label">Q2</div>
                                    <div class="grade-value">${q2Grade}</div>
                                </div>
                            </div>
                        </div>

                        <div class="grade-box ${hasFinal ? 'has-grade' : 'no-grade'} mb-3">
                            <div class="grade-label">Final Grade</div>
                            <div class="grade-value">${hasFinal ? finalGrade : '—'}</div>
                        </div>

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
                </div>
            </div>
        `;

        return col;
    }

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