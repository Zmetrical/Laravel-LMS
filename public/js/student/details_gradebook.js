$(document).ready(function() {
    let gradeData = null;
    let currentComponentFilter = 'all';
    let currentQuarterFilter = 'all';

    // Load grade details on page load
    loadGradeDetails();

    /**
     * Component filter buttons
     */
    $(document).on('click', '.component-filter-btn', function() {
        $('.component-filter-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('active btn-primary');
        
        currentComponentFilter = $(this).data('filter');
        applyFilters();
    });

    /**
     * Quarter filter buttons
     */
    $(document).on('click', '.quarter-filter-btn', function() {
        $('.quarter-filter-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('active btn-primary');
        
        currentQuarterFilter = $(this).data('filter');
        displayFilteredData();
    });

    /**
     * Load grade details
     */
    function loadGradeDetails() {
        showLoading();

        $.ajax({
            url: API_ROUTES.getDetails,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    gradeData = response.data;
                    displayFilteredData();
                } else {
                    showError(response.message || 'Failed to load grade details');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to load grade details';
                showError(message);
                toastr.error(message);
            }
        });
    }

    /**
     * Display filtered data based on quarter selection
     */
    function displayFilteredData() {
        if (!gradeData) return;

        const { quarters, final_grade } = gradeData;
        const container = $('#gradesContent');
        container.empty();

        if (quarters.length === 0) {
            container.html(`
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5>No Quarter Data</h5>
                        <p class="text-muted">No grade data available for this class yet.</p>
                    </div>
                </div>
            `);
            return;
        }

        if (currentQuarterFilter === 'all') {
            // Display all quarters
            quarters.forEach(function(quarterData, index) {
                const quarterCard = createQuarterCard(quarterData, index + 1);
                container.append(quarterCard);
            });
        } else if (currentQuarterFilter === 'final') {
            // Display final grade
            if (final_grade) {
                displayFinalGradeView(final_grade, quarters);
            } else {
                container.html(`
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <h5>Final Grade Not Available</h5>
                            <p class="text-muted">Final grade has not been computed yet.</p>
                        </div>
                    </div>
                `);
            }
        } else {
            // Display specific quarter
            const quarterIndex = parseInt(currentQuarterFilter) - 1;
            if (quarters[quarterIndex]) {
                const quarterCard = createQuarterCard(quarters[quarterIndex], quarterIndex + 1);
                container.append(quarterCard);
            }
        }

        applyFilters();
    }

    /**
     * Create quarter card with calculations
     */
    function createQuarterCard(quarterData, quarterNumber) {
        const { quarter, grades, scores } = quarterData;
        
        const isLocked = grades && grades.is_locked;
        const lockedClass = isLocked ? 'locked' : '';
        const lockIcon = isLocked ? '<i class="fas fa-lock text-warning ml-2"></i>' : '';

        // Group scores by component type
        const wwScores = scores.filter(s => s.component_type === 'WW');
        const ptScores = scores.filter(s => s.component_type === 'PT');
        const qaScores = scores.filter(s => s.component_type === 'QA');

        // Calculate totals and percentages
        const wwTotals = calculateComponentTotals(wwScores);
        const ptTotals = calculateComponentTotals(ptScores);
        const qaTotals = calculateComponentTotals(qaScores);

        const card = `
            <div class="card quarter-card ${lockedClass} mb-3">
                <div class="card-header bg-primary">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt"></i> 
                        ${escapeHtml(quarter.name)}
                        ${lockIcon}
                    </h5>
                </div>
                <div class="card-body">
                    ${grades ? `
                        <!-- Component Summary Cards -->
                        <div class="row mb-3">
                            <!-- WW Component -->
                            <div class="col-md-4 component-section" data-component="WW">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-3 text-center">
                                            <i class="fas fa-pencil-alt"></i> Written Works
                                        </h6>
                                        
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Total Score:</small>
                                                <strong>${wwTotals.earned} / ${wwTotals.max}</strong>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Percentage Score:</small>
                                                <span class="badge badge-secondary">${grades.ww_ps || '0.00'}%</span>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Weight (${CLASS_INFO.ww_perc}%):</small>
                                                <span class="badge badge-primary">${grades.ww_ws || '0.00'}</span>
                                            </div>
                                        </div>

                                        <hr class="my-2">
                                        
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">Contribution to Grade:</small>
                                            <strong class="text-primary">${grades.ww_ws || '0.00'} pts</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PT Component -->
                            <div class="col-md-4 component-section" data-component="PT">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-3 text-center">
                                            <i class="fas fa-tasks"></i> Performance Tasks
                                        </h6>
                                        
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Total Score:</small>
                                                <strong>${ptTotals.earned} / ${ptTotals.max}</strong>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Percentage Score:</small>
                                                <span class="badge badge-secondary">${grades.pt_ps || '0.00'}%</span>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Weight (${CLASS_INFO.pt_perc}%):</small>
                                                <span class="badge badge-primary">${grades.pt_ws || '0.00'}</span>
                                            </div>
                                        </div>

                                        <hr class="my-2">
                                        
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">Contribution to Grade:</small>
                                            <strong class="text-primary">${grades.pt_ws || '0.00'} pts</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- QA Component -->
                            <div class="col-md-4 component-section" data-component="QA">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="text-muted mb-3 text-center">
                                            <i class="fas fa-clipboard-check"></i> Quarterly Assessment
                                        </h6>
                                        
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Total Score:</small>
                                                <strong>${qaTotals.earned} / ${qaTotals.max}</strong>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Percentage Score:</small>
                                                <span class="badge badge-secondary">${grades.qa_ps || '0.00'}%</span>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Weight (${CLASS_INFO.qa_perc}%):</small>
                                                <span class="badge badge-primary">${grades.qa_ws || '0.00'}</span>
                                            </div>
                                        </div>

                                        <hr class="my-2">
                                        
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">Contribution to Grade:</small>
                                            <strong class="text-primary">${grades.qa_ws || '0.00'} pts</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quarter Summary -->
                        <div class="card card-outline card-primary">
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Initial Grade</small>
                                        <h3 class="text-secondary mb-0">${grades.initial_grade || '-'}</h3>
                                        <small class="text-muted">(Sum of Weighted Scores)</small>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Transmuted Grade</small>
                                        <h3 class="text-primary mb-0">${grades.transmuted_grade || '-'}</h3>
                                        <small class="text-muted">(Based on Grading Scale)</small>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted d-block">Status</small>
                                        <h5 class="mb-0">
                                            ${isLocked ? 
                                                '<span class="badge badge-warning"><i class="fas fa-lock"></i> Locked</span>' : 
                                                '<span class="badge badge-secondary"><i class="fas fa-unlock"></i> In Progress</span>'
                                            }
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    ` : `
                        <div class="alert alert-secondary text-center">
                            <i class="fas fa-info-circle"></i> No grades recorded for this quarter yet
                        </div>
                    `}

                    <!-- Detailed Scores Table -->
                    ${scores.length > 0 ? `
                        <div class="card card-outline card-secondary mt-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0">
                                    <i class="fas fa-list"></i> Individual Scores
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>Component</th>
                                                <th>Item</th>
                                                <th class="text-center">Score</th>
                                                <th class="text-center">Max</th>
                                                <th class="text-center">%</th>
                                                <th class="text-center">Source</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${createScoreRows(wwScores, 'WW')}
                                            ${createScoreRows(ptScores, 'PT')}
                                            ${createScoreRows(qaScores, 'QA')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;

        return card;
    }

    /**
     * Calculate component totals
     */
    function calculateComponentTotals(scores) {
        let earned = 0;
        let max = 0;

        scores.forEach(function(score) {
            if (score.score !== null) {
                earned += parseFloat(score.score);
            }
            max += parseFloat(score.max_points);
        });

        return {
            earned: earned.toFixed(2),
            max: max.toFixed(2),
            percentage: max > 0 ? ((earned / max) * 100).toFixed(2) : '0.00'
        };
    }

    /**
     * Create score table rows
     */
    function createScoreRows(scores, componentType) {
        if (scores.length === 0) return '';

        let rows = '';
        scores.forEach(function(score) {
            const componentColor = 'secondary';
            const sourceIcon = score.source_type === 'online' ? 
                '<i class="fas fa-laptop text-primary"></i>' : 
                '<i class="fas fa-pencil-alt text-secondary"></i>';
            
            const sourceText = score.source_type === 'online' ? 'Online' : 'Manual';
            const scoreDisplay = score.score !== null ? score.score : '-';
            const percentageDisplay = score.percentage > 0 ? score.percentage + '%' : '-';

            rows += `
                <tr class="score-row" data-component="${componentType}">
                    <td>
                        <span class="badge badge-${componentColor}">${componentType}</span>
                    </td>
                    <td>${escapeHtml(score.column_name)}</td>
                    <td class="text-center"><strong>${scoreDisplay}</strong></td>
                    <td class="text-center">${score.max_points}</td>
                    <td class="text-center">${percentageDisplay}</td>
                    <td class="text-center">
                        ${sourceIcon}
                        <small class="d-block text-muted">${sourceText}</small>
                    </td>
                </tr>
            `;
        });

        return rows;
    }

    /**
     * Display final grade view
     */
    function displayFinalGradeView(finalGrade, quarters) {
        const container = $('#gradesContent');
        
        const q1Data = quarters[0] || null;
        const q2Data = quarters[1] || null;

        const finalView = `
            <div class="card card-outline card-primary">
                <div class="card-header bg-primary">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-trophy"></i> Final Semester Grade
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Quarter Breakdown -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">1st Quarter</h6>
                                    <h2 class="text-primary mb-0">${finalGrade.q1_grade || '-'}</h2>
                                    ${q1Data && q1Data.grades ? `
                                        <small class="text-muted d-block mt-2">
                                            WW: ${q1Data.grades.ww_ws} | 
                                            PT: ${q1Data.grades.pt_ws} | 
                                            QA: ${q1Data.grades.qa_ws}
                                        </small>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-muted mb-2">2nd Quarter</h6>
                                    <h2 class="text-primary mb-0">${finalGrade.q2_grade || '-'}</h2>
                                    ${q2Data && q2Data.grades ? `
                                        <small class="text-muted d-block mt-2">
                                            WW: ${q2Data.grades.ww_ws} | 
                                            PT: ${q2Data.grades.pt_ws} | 
                                            QA: ${q2Data.grades.qa_ws}
                                        </small>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Final Grade Summary -->
                    <div class="card card-outline card-primary">
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-6">
                                    <small class="text-muted d-block">Final Grade</small>
                                    <h1 class="display-3 text-primary mb-0">${finalGrade.final_grade || '-'}</h1>
                                    <small class="text-muted">(Average of Q1 and Q2)</small>
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted d-block mb-3">Remarks</small>
                                    ${getRemarksDisplay(finalGrade.remarks)}
                                    ${finalGrade.is_locked ? 
                                        '<div class="mt-3"><span class="badge badge-warning"><i class="fas fa-lock"></i> Locked</span></div>' : 
                                        '<div class="mt-3"><span class="badge badge-secondary"><i class="fas fa-unlock"></i> In Progress</span></div>'
                                    }
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        container.html(finalView);
    }

    /**
     * Get remarks display HTML
     */
    function getRemarksDisplay(remarks) {
        let badgeClass = 'badge-secondary';
        let icon = 'fas fa-minus';
        
        if (remarks === 'PASSED') {
            badgeClass = 'badge-success';
            icon = 'fas fa-check-circle';
        } else if (remarks === 'FAILED') {
            badgeClass = 'badge-danger';
            icon = 'fas fa-times-circle';
        } else if (remarks === 'INC') {
            badgeClass = 'badge-warning';
            icon = 'fas fa-exclamation-circle';
        }
        
        return `<h2><span class="badge ${badgeClass}"><i class="${icon}"></i> ${remarks || '-'}</span></h2>`;
    }

    /**
     * Apply component filters
     */
    function applyFilters() {
        if (currentComponentFilter === 'all') {
            $('.component-section').show();
            $('.score-row').show();
        } else {
            $('.component-section').hide();
            $('.score-row').hide();
            
            $(`.component-section[data-component="${currentComponentFilter}"]`).show();
            $(`.score-row[data-component="${currentComponentFilter}"]`).show();
        }
    }

    /**
     * Helper functions
     */
    function showLoading() {
        $('#gradesContent').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading grade details...</p>
            </div>
        `);
    }

    function showError(message) {
        $('#gradesContent').html(`
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5>Error Loading Grade Details</h5>
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