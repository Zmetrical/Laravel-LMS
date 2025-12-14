$(document).ready(function() {
    let gradeData = null;
    let currentQuarterFilter = '1';

    loadGradeDetails();

    $(document).on('click', '.quarter-filter-btn', function() {
        $('.quarter-filter-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('active btn-primary');
        currentQuarterFilter = $(this).data('filter');
        displayFilteredData();
    });

    $(document).on('click', '.component-tab', function() {
        const quarter = $(this).data('quarter');
        const component = $(this).data('component');
        
        $(`.component-tab[data-quarter="${quarter}"]`).removeClass('active');
        $(this).addClass('active');
        
        $(`.component-content[data-quarter="${quarter}"]`).hide();
        $(`#${quarter}-${component}`).show();
    });

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

        if (currentQuarterFilter === 'final') {
            displayFinalGrade(quarters, final_grade);
        } else {
            const quarterIndex = parseInt(currentQuarterFilter) - 1;
            if (quarters[quarterIndex]) {
                const quarterCard = createQuarterCard(quarters[quarterIndex], quarterIndex + 1);
                container.append(quarterCard);
            } else {
                container.html(`
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <h5>No Data Available</h5>
                            <p class="text-muted">This quarter has no data yet.</p>
                        </div>
                    </div>
                `);
            }
        }
    }

    function createQuarterCard(quarterData, quarterNumber) {
        const { quarter, grades, calculated_grade, scores } = quarterData;
        const isLocked = grades && grades.is_locked;

        const wwScores = scores.filter(s => s.component_type === 'WW');
        const ptScores = scores.filter(s => s.component_type === 'PT');
        const qaScores = scores.filter(s => s.component_type === 'QA');

        const wwCalc = calculateComponentStats(wwScores, CLASS_INFO.ww_perc);
        const ptCalc = calculateComponentStats(ptScores, CLASS_INFO.pt_perc);
        const qaCalc = calculateComponentStats(qaScores, CLASS_INFO.qa_perc);

        // Use locked grade if available, otherwise use calculated grade
        let displayGrade = '-';
        let transmutedGrade = null;
        
        if (isLocked && grades) {
            displayGrade = grades.initial_grade ? parseFloat(grades.initial_grade).toFixed(2) : '-';
            transmutedGrade = grades.transmuted_grade ? parseFloat(grades.transmuted_grade).toFixed(2) : null;
        } else if (calculated_grade) {
            displayGrade = calculated_grade.initial_grade ? parseFloat(calculated_grade.initial_grade).toFixed(2) : '-';
            transmutedGrade = calculated_grade.transmuted_grade ? parseFloat(calculated_grade.transmuted_grade).toFixed(2) : null;
        }

        const quarterId = `q${quarterNumber}`;

        const card = `
            <div class="card mb-4 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h4 class="mb-1">${escapeHtml(quarter.name)}</h4>
                            <small class="text-muted">
                                ${isLocked ? '<i class="fas fa-lock"></i> Locked' : '<i class="fas fa-unlock"></i> In Progress'}
                            </small>
                        </div>
                        <div class="text-right">
                            <div class="display-4 font-weight-bold mb-0">${transmutedGrade || displayGrade}</div>
                            <small class="text-muted">${transmutedGrade ? 'Transmuted Grade' : 'Initial Grade'}</small>
                        </div>
                    </div>

                    ${scores.length > 0 ? `
                        <div class="row mb-4">
                            <div class="col-4 text-center">
                                <div class="mb-1">
                                    <span class="text-muted small">Written Works</span>
                                    <span class="badge badge-primary ml-1">${CLASS_INFO.ww_perc}%</span>
                                </div>
                                <div class="h5 mb-0">${wwCalc.weightedScore}</div>
                                <small class="text-muted">${wwCalc.totalEarned}/${wwCalc.totalMax}</small>
                            </div>
                            <div class="col-4 text-center border-left border-right">
                                <div class="mb-1">
                                    <span class="text-muted small">Performance Tasks</span>
                                    <span class="badge badge-primary ml-1">${CLASS_INFO.pt_perc}%</span>
                                </div>
                                <div class="h5 mb-0">${ptCalc.weightedScore}</div>
                                <small class="text-muted">${ptCalc.totalEarned}/${ptCalc.totalMax}</small>
                            </div>
                            <div class="col-4 text-center">
                                <div class="mb-1">
                                    <span class="text-muted small">Assessment</span>
                                    <span class="badge badge-primary ml-1">${CLASS_INFO.qa_perc}%</span>
                                </div>
                                <div class="h5 mb-0">${qaCalc.weightedScore}</div>
                                <small class="text-muted">${qaCalc.totalEarned}/${qaCalc.totalMax}</small>
                            </div>
                        </div>

                        <ul class="nav nav-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link component-tab active" data-quarter="${quarterId}" data-component="all" href="#">All Items</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link component-tab" data-quarter="${quarterId}" data-component="ww" href="#">WW</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link component-tab" data-quarter="${quarterId}" data-component="pt" href="#">PT</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link component-tab" data-quarter="${quarterId}" data-component="qa" href="#">QA</a>
                            </li>
                        </ul>

                        <div class="tab-content border-left border-right border-bottom p-3">
                            <div class="component-content" data-quarter="${quarterId}" id="${quarterId}-all">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Type</th>
                                                <th>Item</th>
                                                <th class="text-center">Score</th>
                                                <th class="text-center">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${createSimpleScoreRows(wwScores, 'WW')}
                                            ${createSimpleScoreRows(ptScores, 'PT')}
                                            ${createSimpleScoreRows(qaScores, 'QA')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="component-content" data-quarter="${quarterId}" id="${quarterId}-ww" style="display: none;">
                                ${createComponentDetail(wwScores, 'WW', 'Written Works', wwCalc)}
                            </div>
                            <div class="component-content" data-quarter="${quarterId}" id="${quarterId}-pt" style="display: none;">
                                ${createComponentDetail(ptScores, 'PT', 'Performance Tasks', ptCalc)}
                            </div>
                            <div class="component-content" data-quarter="${quarterId}" id="${quarterId}-qa" style="display: none;">
                                ${createComponentDetail(qaScores, 'QA', 'Quarterly Assessment', qaCalc)}
                            </div>
                        </div>
                    ` : `
                        <div class="alert alert-light text-center mb-0">
                            <i class="fas fa-info-circle"></i> No scores recorded yet
                        </div>
                    `}
                </div>
            </div>
        `;

        return card;
    }

    function createComponentDetail(scores, type, name, calc) {
        if (scores.length === 0) {
            return `<div class="text-center py-4 text-muted">No ${name} items yet</div>`;
        }

        return `
            <div class="mb-3 p-3 bg-light">
                <div class="row">
                    <div class="col-6">
                        <small class="text-muted">Total Score</small>
                        <div class="h5 mb-0">${calc.totalEarned} / ${calc.totalMax}</div>
                    </div>
                    <div class="col-6 text-right">
                        <small class="text-muted">Weighted Score</small>
                        <div class="h5 mb-0">${calc.weightedScore}</div>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-center">Score</th>
                            <th class="text-center">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${createDetailedScoreRows(scores)}
                    </tbody>
                </table>
            </div>
        `;
    }

    function createSimpleScoreRows(scores, componentType) {
        if (scores.length === 0) return '';

        let rows = '';
        scores.forEach(function(score) {
            const scoreDisplay = score.score !== null ? `${score.score}/${score.max_points}` : '-';
            const percentageDisplay = score.percentage > 0 ? score.percentage + '%' : '-';

            rows += `
                <tr>
                    <td><span class="badge badge-light">${componentType}</span></td>
                    <td>${escapeHtml(score.column_name)}</td>
                    <td class="text-center">${scoreDisplay}</td>
                    <td class="text-center">${percentageDisplay}</td>
                </tr>
            `;
        });

        return rows;
    }

    function createDetailedScoreRows(scores) {
        let rows = '';
        scores.forEach(function(score) {
            const scoreDisplay = score.score !== null ? `${score.score}/${score.max_points}` : '-';
            const percentageDisplay = score.percentage > 0 ? score.percentage + '%' : '-';

            rows += `
                <tr>
                    <td>${escapeHtml(score.column_name)}</td>
                    <td class="text-center">${scoreDisplay}</td>
                    <td class="text-center">${percentageDisplay}</td>
                </tr>
            `;
        });

        return rows;
    }

    function displayFinalGrade(quarters, storedFinalGrade) {
        const container = $('#gradesContent');
        
        // ONLY show final grade if it exists in grades_final table
        if (!storedFinalGrade) {
            container.html(`
                <div class="card shadow-sm">
                    <div class="card-body p-5 text-center">
                        <i class="fas fa-hourglass-half fa-4x text-muted mb-4"></i>
                        <h3 class="mb-3">Final Grade Not Yet Available</h3>
                        <p class="text-muted">Please check back later or contact your teacher.</p>
                    </div>
                </div>
            `);
            return;
        }

        // Display stored final grade from database
        const finalGrade = parseFloat(storedFinalGrade.final_grade).toFixed(2);
        const q1Grade = storedFinalGrade.q1_grade ? parseFloat(storedFinalGrade.q1_grade).toFixed(2) : null;
        const q2Grade = storedFinalGrade.q2_grade ? parseFloat(storedFinalGrade.q2_grade).toFixed(2) : null;
        const remarks = storedFinalGrade.remarks;
        
        let remarksClass = 'text-muted';
        let remarksIcon = 'fa-minus-circle';
        
        if (remarks === 'PASSED') {
            remarksClass = 'text-success';
            remarksIcon = 'fa-check-circle';
        } else if (remarks === 'FAILED') {
            remarksClass = 'text-danger';
            remarksIcon = 'fa-times-circle';
        } else if (remarks === 'INC') {
            remarksClass = 'text-warning';
            remarksIcon = 'fa-exclamation-circle';
        } else if (remarks === 'DRP' || remarks === 'W') {
            remarksClass = 'text-secondary';
            remarksIcon = 'fa-ban';
        }

        const finalView = `
            <div class="card shadow-sm">
                <div class="card-body p-5 text-center">
                    <h3 class="mb-4 text-muted">Final Grade</h3>
                    
                    <div class="display-1 font-weight-bold mb-3" style="font-size: 5rem;">${finalGrade}</div>
                    <h4 class="mb-4 ${remarksClass}">
                        <i class="fas ${remarksIcon}"></i> ${remarks}
                    </h4>
                    
                    <div class="row justify-content-center mt-5 mb-4">
                        <div class="col-md-3 col-6">
                            <div class="p-3">
                                <small class="text-muted d-block mb-2">1st Quarter</small>
                                <div class="h3 mb-0">${q1Grade || '-'}</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="p-3">
                                <small class="text-muted d-block mb-2">2nd Quarter</small>
                                <div class="h3 mb-0">${q2Grade || '-'}</div>
                            </div>
                        </div>
                    </div>

                    <small class="text-muted">
                        <i class="fas fa-lock"></i> Final grade is locked
                    </small>
                </div>
            </div>
        `;

        container.html(finalView);
    }

    function calculateComponentStats(scores, weight) {
        let totalEarned = 0;
        let totalMax = 0;
        let count = 0;

        scores.forEach(function(score) {
            if (score.score !== null && score.score !== undefined) {
                totalEarned += parseFloat(score.score) || 0;
                count++;
            }
            totalMax += parseFloat(score.max_points) || 0;
        });

        const percentage = totalMax > 0 ? (totalEarned / totalMax) * 100 : 0;
        const weightedScore = (percentage / 100) * parseFloat(weight);

        return {
            totalEarned: totalEarned.toFixed(2),
            totalMax: totalMax.toFixed(2),
            percentage: percentage.toFixed(2),
            weightedScore: weightedScore.toFixed(2),
            count: count
        };
    }

    function showLoading() {
        $('#gradesContent').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-3 text-muted">Loading grades...</p>
            </div>
        `);
    }

    function showError(message) {
        $('#gradesContent').html(`
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