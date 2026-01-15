$(document).ready(function() {
    let gradeData = null;
    let currentComponent = 'all';

    loadGradeDetails();

    $(document).on('click', '.component-filter-btn', function() {
        $('.component-filter-btn').removeClass('active btn-primary').addClass('btn-outline-primary');
        $(this).removeClass('btn-outline-primary').addClass('active btn-primary');
        currentComponent = $(this).data('component');
        displayFilteredData();
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

        const { quarter, grades, calculated_grade, scores } = gradeData;
        const container = $('#gradesContent');
        container.empty();

        if (scores.length === 0) {
            container.html(`
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5>No Data Available</h5>
                        <p class="text-muted">No scores recorded for this quarter yet.</p>
                    </div>
                </div>
            `);
            return;
        }

        const isLocked = grades && grades.is_locked;

        // Calculate component stats
        const wwScores = scores.filter(s => s.component_type === 'WW');
        const ptScores = scores.filter(s => s.component_type === 'PT');
        const qaScores = scores.filter(s => s.component_type === 'QA');

        const wwCalc = calculateComponentStats(wwScores, CLASS_INFO.ww_perc);
        const ptCalc = calculateComponentStats(ptScores, CLASS_INFO.pt_perc);
        const qaCalc = calculateComponentStats(qaScores, CLASS_INFO.qa_perc);

        // Determine display grade
        let displayGrade = '-';
        let transmutedGrade = null;
        
        if (isLocked && grades) {
            displayGrade = grades.initial_grade ? parseFloat(grades.initial_grade).toFixed(2) : '-';
            transmutedGrade = grades.transmuted_grade ? parseFloat(grades.transmuted_grade).toFixed(2) : null;
        } else if (calculated_grade) {
            displayGrade = calculated_grade.initial_grade ? parseFloat(calculated_grade.initial_grade).toFixed(2) : '-';
            transmutedGrade = calculated_grade.transmuted_grade ? parseFloat(calculated_grade.transmuted_grade).toFixed(2) : null;
        }

        // Create main card
        const card = `
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h4 class="mb-1">${escapeHtml(quarter.name)}</h4>
                            <small class="text-muted">
                                ${isLocked ? '<i class="fas fa-lock"></i> Grade Locked' : '<i class="fas fa-unlock"></i> In Progress'}
                            </small>
                        </div>
                        <div class="text-right">
                            <div class="display-4 font-weight-bold mb-0">${transmutedGrade || displayGrade}</div>
                            <small class="text-muted">${transmutedGrade ? 'Transmuted Grade' : 'Initial Grade'}</small>
                        </div>
                    </div>

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

                    <div id="componentContent">
                        ${createComponentContent(currentComponent, wwScores, ptScores, qaScores, wwCalc, ptCalc, qaCalc)}
                    </div>
                </div>
            </div>
        `;

        container.html(card);
    }

    function createComponentContent(component, wwScores, ptScores, qaScores, wwCalc, ptCalc, qaCalc) {
        if (component === 'all') {
            return `
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
            `;
        } else if (component === 'WW') {
            return createComponentDetail(wwScores, 'WW', 'Written Works', wwCalc);
        } else if (component === 'PT') {
            return createComponentDetail(ptScores, 'PT', 'Performance Tasks', ptCalc);
        } else if (component === 'QA') {
            return createComponentDetail(qaScores, 'QA', 'Quarterly Assessment', qaCalc);
        }
    }

    function createComponentDetail(scores, type, name, calc) {
        if (scores.length === 0) {
            return `<div class="alert alert-light text-center mb-0">No ${name} items yet</div>`;
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
        if (scores.length === 0) return '<tr><td colspan="4" class="text-center text-muted">No items</td></tr>';

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