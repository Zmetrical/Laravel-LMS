$(document).ready(function() {
    let currentGrades = [];

    // Load grades on page load
    loadGrades();

    /**
     * Load student grades
     */
    function loadGrades() {
        showLoading();

        $.ajax({
            url: API_ROUTES.getGrades,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    currentGrades = response.data;
                    displayGrades(response.data);
                    updateSummary(response.data);
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

    /**
     * Display grades as cards
     */
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

    /**
     * Create individual grade card
     */
    function createGradeCard(grade) {
        const hasFinal = grade.has_final;
        const finalGrade = hasFinal ? grade.final_grade.final_grade : null;
        const remarks = hasFinal ? grade.final_grade.remarks : null;
        
        // RAW quarter grades - NO transmutation
        const q1Grade = grade.quarter_grades?.q1 ?? null;
        const q2Grade = grade.quarter_grades?.q2 ?? null;
        
        // Determine card class based on final grade or status
        let cardClass = 'card-outline card-primary';
        let statusBadge = '';
        
        if (hasFinal) {
            if (finalGrade >= 90) {
                cardClass = 'card-outline card-primary';
            } else if (finalGrade >= 75) {
                cardClass = 'card-outline card-primary';
            } else {
                cardClass = 'card-outline card-primary';
            }
            
            const remarksColor = remarks === 'PASSED' ? 'primary' : (remarks === 'FAILED' ? 'primary' : 'primary');
            statusBadge = `<span class="badge badge-${remarksColor}">${remarks}</span>`;
        } else {
            statusBadge = `<span class="badge badge-secondary">ONGOING</span>`;
        }

        const col = `
            <div class="col-lg-4 col-md-6 col-12 mb-3">
                <div class="card grade-card ${cardClass} h-100">
                    <div class="card-header py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1 pr-2">
                                <h6 class="card-title mb-0 font-weight-bold">
                                    ${escapeHtml(grade.class_name)}
                                </h6>
                            </div>
                            ${statusBadge}
                        </div>
                    </div>
                    <div class="card-body py-2">
                        <!-- Quarter Grades - Raw scores -->
                        <div class="row mb-2 g-2">
                            <div class="col-6">
                                <div class="grade-box ${q1Grade !== null ? 'has-grade' : 'no-grade'}">
                                    <div class="grade-label">
                                        <i class="fas fa-calendar-alt"></i> Q1
                                    </div>
                                    <div class="grade-value">
                                        ${q1Grade !== null ? q1Grade : '-'}
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="grade-box ${q2Grade !== null ? 'has-grade' : 'no-grade'}">
                                    <div class="grade-label">
                                        <i class="fas fa-calendar-alt"></i> Q2
                                    </div>
                                    <div class="grade-value">
                                        ${q2Grade !== null ? q2Grade : '-'}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Final Grade - Rounded average -->
                        <div class="mb-2">
                            <div class="grade-box final-box ${hasFinal ? 'has-grade' : 'no-grade'}">
                                <div class="grade-label">
                                    <i class="fas fa-trophy"></i> Final Grade
                                </div>
                                <div class="grade-value">
                                    ${hasFinal ? finalGrade : '-'}
                                </div>
                            </div>
                        </div>

                        <!-- Component Weights -->
                        <div class="mt-2 pt-2 border-top">
                            <div class="d-flex justify-content-between gap-3">
                                <span class="badge badge-light flex-fill text-center">
                                    <i class="fas fa-pencil-alt"></i> WW ${grade.ww_percentage}%
                                </span>
                                <span class="badge badge-light flex-fill text-center">
                                    <i class="fas fa-tasks"></i> PT ${grade.pt_percentage}%
                                </span>
                                <span class="badge badge-light flex-fill text-center">
                                    <i class="fas fa-clipboard-check"></i> QA ${grade.qa_percentage}%
                                </span>
                            </div>
                        </div>
                        
                        ${grade.teacher_name ? `
                            <div class="mt-2 pt-2 border-top">
                                <small class="text-muted">
                                    <i class="fas fa-chalkboard-teacher"></i> ${escapeHtml(grade.teacher_name)}
                                </small>
                            </div>
                        ` : ''}
                    </div>
                    <div class="card-footer py-2">
                        <button class="btn btn-primary btn-sm btn-block view-details-btn" 
                                data-class-id="${grade.class_id}">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </div>
                </div>
            </div>
        `;

        return col;
    }

    /**
     * Update summary cards
     */
    function updateSummary(grades) {
        const totalClasses = grades.length;
        const withFinal = grades.filter(g => g.has_final).length;
        const pending = totalClasses - withFinal;
        
        // Calculate overall average
        const finalized = grades.filter(g => g.has_final);
        let overallAverage = 0;
        
        if (finalized.length > 0) {
            const sum = finalized.reduce((acc, g) => acc + parseFloat(g.final_grade.final_grade), 0);
            overallAverage = (sum / finalized.length).toFixed(2);
        }

        // Count passed/failed
        const passed = finalized.filter(g => g.final_grade.remarks === 'PASSED').length;
        const failed = finalized.filter(g => g.final_grade.remarks === 'FAILED').length;

        $('#totalClassesCount').text(totalClasses);
        $('#withFinalCount').text(withFinal);
        $('#pendingCount').text(pending);
        $('#overallAverage').text(overallAverage > 0 ? overallAverage : 'N/A');
        $('#passedCount').text(passed);
        $('#failedCount').text(failed);
    }

    /**
     * View grade details - Navigate to details page
     */
    $(document).on('click', '.view-details-btn', function() {
        const classId = $(this).data('class-id');
        const detailsUrl = API_ROUTES.gradeDetails.replace(':classId', classId);
        window.location.href = detailsUrl;
    });

    /**
     * Helper functions
     */
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
                        <i class="fas fa-redo"></i> Retry
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