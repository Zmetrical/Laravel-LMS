$(document).ready(function() {
    let gradeBreakdownChart = null;
    let breakdownData = null;
    let selectedQuarterIndex = 0;

    // Load all data
    loadQuarterlyGrades();
    loadSemesterSummary();
    loadGradeBreakdown();

    // Refresh buttons
    $('#refreshSummary').on('click', function() {
        $(this).find('i').addClass('fa-spin');
        loadSemesterSummary();
    });

    $('#refreshBreakdown').on('click', function() {
        $(this).find('i').addClass('fa-spin');
        loadGradeBreakdown();
    });

    $('#refreshGrades').on('click', function() {
        $(this).find('i').addClass('fa-spin');
        loadQuarterlyGrades();
    });

    /**
     * Load semester summary
     */
    function loadSemesterSummary() {
        $.ajax({
            url: API_ROUTES.getSemesterSummary,
            method: 'GET',
            success: function(response) {
                $('#refreshSummary i').removeClass('fa-spin');
                
                if (response.success) {
                    displaySemesterSummary(response.data);
                } else {
                    showSummaryError();
                }
            },
            error: function(xhr) {
                $('#refreshSummary i').removeClass('fa-spin');
                showSummaryError();
                toastr.error('Failed to load semester summary');
            }
        });
    }

    /**
     * Display semester summary as table
     */
    function displaySemesterSummary(data) {
        const container = $('#semesterSummary');
        
        const q1AvgHtml = data.q1_average !== null 
            ? `<span class="${getGradeClass(data.q1_average)}">${data.q1_average}</span>`
            : '<span class="grade-pending">N/A</span>';
        
        const q2AvgHtml = data.q2_average !== null 
            ? `<span class="${getGradeClass(data.q2_average)}">${data.q2_average}</span>`
            : '<span class="grade-pending">N/A</span>';
        
        const semAvgHtml = data.semester_average !== null 
            ? `<span class="${getGradeClass(data.semester_average)}">${data.semester_average}</span>`
            : '<span class="grade-pending">N/A</span>';
        
        const html = `
            <table class="table table-hover table-grades">
                <thead>
                    <tr>
                        <th class="text-center">1st Quarter Average</th>
                        <th class="text-center">2nd Quarter Average</th>
                        <th class="text-center">Semester Average</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center" style="font-size: 1.5em;">${q1AvgHtml}</td>
                        <td class="text-center" style="font-size: 1.5em;">${q2AvgHtml}</td>
                        <td class="text-center final-grade-col" style="font-size: 1.5em;">${semAvgHtml}</td>
                    </tr>
                </tbody>
            </table>
        `;
        
        container.html(html);
    }

    /**
     * Show summary error
     */
    function showSummaryError() {
        $('#semesterSummary').html(`
            <div class="text-center py-4">
                <i class="fas fa-exclamation-circle fa-2x text-danger mb-2"></i>
                <p class="text-muted">Failed to load semester summary</p>
            </div>
        `);
    }

    /**
     * Load grade breakdown
     */
    function loadGradeBreakdown() {
        $.ajax({
            url: API_ROUTES.getGradeBreakdown,
            method: 'GET',
            success: function(response) {
                $('#refreshBreakdown i').removeClass('fa-spin');
                
                if (response.success) {
                    breakdownData = response.data;
                    renderQuarterButtons();
                    displayGradeBreakdown();
                } else {
                    showBreakdownError();
                }
            },
            error: function(xhr) {
                $('#refreshBreakdown i').removeClass('fa-spin');
                showBreakdownError();
                toastr.error('Failed to load grade breakdown');
            }
        });
    }

    /**
     * Render quarter toggle buttons
     */
    function renderQuarterButtons() {
        const container = $('#quarterToggle');
        
        if (!breakdownData || breakdownData.length === 0) {
            container.html('');
            return;
        }
        
        let buttonsHtml = '';
        breakdownData.forEach((quarter, index) => {
            const activeClass = index === selectedQuarterIndex ? 'btn-primary' : 'btn-secondary';
            buttonsHtml += `
                <button type="button" class="btn ${activeClass} quarter-btn" data-index="${index}">
                    ${escapeHtml(quarter.quarter_name)}
                </button>
            `;
        });
        
        container.html(buttonsHtml);
        
        // Bind click events
        $('.quarter-btn').on('click', function() {
            selectedQuarterIndex = parseInt($(this).data('index'));
            $('.quarter-btn').removeClass('btn-primary').addClass('btn-secondary');
            $(this).removeClass('btn-secondary').addClass('btn-primary');
            displayGradeBreakdown();
        });
    }

    /**
     * Display grade breakdown chart
     */
    function displayGradeBreakdown() {
        const container = $('#breakdownChartContainer');
        const ctx = document.getElementById('gradeBreakdownChart');
        
        if (!breakdownData || breakdownData.length === 0) {
            container.html(`
                <div class="text-center py-4">
                    <i class="fas fa-chart-bar fa-3x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No grade data available yet</p>
                </div>
            `);
            return;
        }
        
        const quarterData = breakdownData[selectedQuarterIndex];
        
        if (!quarterData || quarterData.classes.length === 0) {
            container.html(`
                <div class="text-center py-4">
                    <i class="fas fa-chart-bar fa-3x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No classes for this quarter</p>
                </div>
            `);
            return;
        }
        
        // Prepare data for stacked bar chart with multi-line labels
        const labels = quarterData.classes.map(c => {
            const transmuted = c.transmuted_grade || 'N/A';
            return [
                c.class_name,
                `Grade: ${transmuted}`
            ];
        });
        
        const wwData = quarterData.classes.map(c => c.ww_ws || 0);
        const ptData = quarterData.classes.map(c => c.pt_ws || 0);
        const qaData = quarterData.classes.map(c => c.qa_ws || 0);
        
        if (gradeBreakdownChart) {
            gradeBreakdownChart.destroy();
        }
        
        gradeBreakdownChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Written Work (WW)',
                        data: wwData,
                        backgroundColor: 'rgba(0, 123, 255, 0.8)',
                        borderColor: 'rgb(0, 123, 255)',
                        borderWidth: 1
                    },
                    {
                        label: 'Performance Task (PT)',
                        data: ptData,
                        backgroundColor: 'rgba(108, 117, 125, 0.8)',
                        borderColor: 'rgb(108, 117, 125)',
                        borderWidth: 1
                    },
                    {
                        label: 'Quarterly Assessment (QA)',
                        data: qaData,
                        backgroundColor: 'rgba(40, 167, 69, 0.8)',
                        borderColor: 'rgb(40, 167, 69)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    x: {
                        stacked: true,
                        ticks: {
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0
                        }
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value;
                            }
                        },
                        title: {
                            display: true,
                            text: 'Weighted Score'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                const index = context[0].dataIndex;
                                const classData = quarterData.classes[index];
                                return classData.class_name;
                            },
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.parsed.y || 0;
                                return label + ': ' + value.toFixed(2);
                            },
                            footer: function(context) {
                                const index = context[0].dataIndex;
                                const classData = quarterData.classes[index];
                                return [
                                    '---',
                                    'Weight Distribution:',
                                    'WW: ' + classData.ww_perc + '%',
                                    'PT: ' + classData.pt_perc + '%',
                                    'QA: ' + classData.qa_perc + '%'
                                ];
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Show breakdown error
     */
    function showBreakdownError() {
        $('#breakdownChartContainer').html(`
            <div class="text-center py-4">
                <i class="fas fa-exclamation-circle fa-2x text-danger mb-2"></i>
                <p class="text-muted">Failed to load grade breakdown</p>
            </div>
        `);
    }

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
                toastr.error('Failed to load grades');
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
                        <td class="text-center ${getGradeClass(grade)}">
                            ${grade}
                        </td>
                    `;
                } else {
                    tableHtml += `<td class="text-center grade-pending">N/A</td>`;
                }
            });
            
            // Add semester final grade
            if (classData.semester_final && classData.semester_final.final_grade !== null) {
                const finalGrade = classData.semester_final.final_grade;
                
                tableHtml += `
                    <td class="text-center final-grade-col ${getGradeClass(finalGrade)}">
                        ${finalGrade}
                    </td>
                `;
                
                // Add remarks
                const remarks = classData.semester_final.remarks;
                let remarksClass = '';
                if (remarks === 'PASSED') remarksClass = 'text-success';
                else if (remarks === 'FAILED') remarksClass = 'text-danger';
                else remarksClass = 'text-warning';
                
                tableHtml += `
                    <td class="text-center ${remarksClass}">
                        <strong>${remarks}</strong>
                    </td>
                `;
            } else {
                tableHtml += `
                    <td class="text-center final-grade-col grade-pending">N/A</td>
                    <td class="text-center grade-pending">-</td>
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
     * Get grade CSS class based on value
     */
    function getGradeClass(grade) {
        if (grade === null) return 'grade-pending';
        if (grade >= 90) return 'grade-excellent';
        if (grade >= 85) return 'grade-very-good';
        if (grade >= 80) return 'grade-good';
        if (grade >= 75) return 'grade-fair';
        return 'grade-poor';
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