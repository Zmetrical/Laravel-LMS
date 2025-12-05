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
        
        // Calculate component averages
        const wwAvg = grade.components.WW.average || 0;
        const ptAvg = grade.components.PT.average || 0;
        const qaAvg = grade.components.QA.average || 0;
        
        // Determine card class based on final grade
        let cardClass = 'card-outline card-primary';
        let gradeDisplay = '';
        
        if (hasFinal) {
            if (finalGrade >= 90) {
                cardClass = 'card-outline card-success';
            } else if (finalGrade >= 75) {
                cardClass = 'card-outline card-primary';
            } else {
                cardClass = 'card-outline card-danger';
            }
            
            const gradeColor = finalGrade >= 90 ? 'success' : (finalGrade >= 75 ? 'primary' : 'danger');
            const remarksColor = remarks === 'PASSED' ? 'success' : 'danger';
            
            gradeDisplay = `
                <div class="text-center mb-2">
                    <div class="score-display text-${gradeColor}">${finalGrade}</div>
                    <span class="badge badge-${remarksColor}">${remarks}</span>
                </div>
            `;
        } else {
            gradeDisplay = `
                <div class="text-center mb-2">
                    <div class="score-display text-muted">-</div>
                    <span class="badge badge-secondary">NO FINAL GRADE</span>
                </div>
            `;
        }

        const col = `
            <div class="col-lg-4 col-md-6 col-12 mb-3">
                <div class="card grade-card ${cardClass} h-100">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <strong>${escapeHtml(grade.class_name)}</strong>
                        </h5>
                    </div>
                    <div class="card-body">
                        ${gradeDisplay}
                        
                        <!-- Component Scores -->
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">
                                    <i class="fas fa-pencil-alt"></i> Written Works
                                    <span class="component-badge badge badge-light ml-1">${grade.ww_percentage}%</span>
                                </small>
                                <strong class="text-primary">${wwAvg.toFixed(2)}%</strong>
                            </div>
                            <div class="progress grade-progress">
                                <div class="progress-bar bg-primary" style="width: ${wwAvg}%"></div>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">
                                    <i class="fas fa-tasks"></i> Performance Tasks
                                    <span class="component-badge badge badge-light ml-1">${grade.pt_percentage}%</span>
                                </small>
                                <strong class="text-primary">${ptAvg.toFixed(2)}%</strong>
                            </div>
                            <div class="progress grade-progress">
                                <div class="progress-bar bg-primary" style="width: ${ptAvg}%"></div>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">
                                    <i class="fas fa-clipboard-check"></i> Quarterly Assessment
                                    <span class="component-badge badge badge-light ml-1">${grade.qa_percentage}%</span>
                                </small>
                                <strong class="text-primary">${qaAvg.toFixed(2)}%</strong>
                            </div>
                            <div class="progress grade-progress">
                                <div class="progress-bar bg-primary" style="width: ${qaAvg}%"></div>
                            </div>
                        </div>
                        
                        ${grade.teacher_name ? `
                            <div class="mt-3 pt-3 border-top">
                                <small class="text-muted">
                                    <i class="fas fa-chalkboard-teacher"></i> ${escapeHtml(grade.teacher_name)}
                                </small>
                            </div>
                        ` : ''}
                    </div>
                    <div class="card-footer">
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

        $('#totalClassesCount').text(totalClasses);
        $('#withFinalCount').text(withFinal);
        $('#pendingCount').text(pending);
        $('#overallAverage').text(overallAverage > 0 ? overallAverage : 'N/A');
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