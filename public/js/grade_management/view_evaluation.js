console.log("Admin - Student Evaluation Summary");

$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    loadEvaluation();

    function loadEvaluation() {
        $('#evaluation-semesters-container').html(`
            <div class="text-center py-4">
                <i class="fas fa-spinner fa-spin"></i> Loading evaluation...
            </div>
        `);

        $.ajax({
            url: API_ROUTES.evaluationData,
            method: 'GET',
            success: function(response) {
                if (response.summary && response.summary.length > 0) {
                    displayEvaluationSummary(response.summary);
                } else {
                    $('#evaluation-semesters-container').html(`
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-info-circle"></i> No grade records available yet.
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                console.error('Error loading evaluation:', xhr);
                $('#evaluation-semesters-container').html(`
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-exclamation-triangle"></i> Error loading evaluation. Please try again.
                    </div>
                `);
            }
        });
    }

    function displayEvaluationSummary(data) {
        // Group by semester
        const bySemester = {};
        data.forEach(item => {
            const semKey = item.semester_id + '|' + item.full_semester;
            if (!bySemester[semKey]) {
                bySemester[semKey] = {
                    semester_name: item.semester_name,
                    full_semester: item.full_semester,
                    school_year: item.school_year_code,
                    grades: []
                };
            }
            bySemester[semKey].grades.push(item);
        });

        let html = '';
        let totalPassed = 0;
        let totalFailed = 0;
        let totalSubjects = 0;

        // Render each semester
        Object.values(bySemester).forEach(semester => {
            html += '<div class="semester-section">';
            html += `<div class="semester-header">${semester.full_semester}</div>`;

            // Group by category within semester
            const coreSubjects = semester.grades.filter(g => g.class_category === 'CORE SUBJECT');
            const appliedSubjects = semester.grades.filter(g => g.class_category === 'APPLIED SUBJECT');
            const specializedSubjects = semester.grades.filter(g => g.class_category === 'SPECIALIZED SUBJECT');

            html += '<table class="grades-table">';
            html += `
                <thead>
                    <tr>
                        <th style="width: 50%;">SUBJECTS</th>
                        <th style="width: 12.5%;">FIRST<br>QUARTER</th>
                        <th style="width: 12.5%;">SECOND<br>QUARTER</th>
                        <th style="width: 12.5%;">SEMESTER<br>AVERAGE</th>
                        <th style="width: 12.5%;">REMARKS</th>
                    </tr>
                </thead>
                <tbody>
            `;

            // Core Subjects
            if (coreSubjects.length > 0) {
                html += '<tr><td colspan="5" class="category-header">CORE SUBJECT</td></tr>';
                coreSubjects.forEach(grade => {
                    html += buildEvaluationRow(grade);
                    totalSubjects++;
                    if (grade.remarks === 'PASSED') totalPassed++;
                    if (grade.remarks === 'FAILED') totalFailed++;
                });
            }

            // Applied Subjects
            if (appliedSubjects.length > 0) {
                html += '<tr><td colspan="5" class="category-header">APPLIED SUBJECT</td></tr>';
                appliedSubjects.forEach(grade => {
                    html += buildEvaluationRow(grade);
                    totalSubjects++;
                    if (grade.remarks === 'PASSED') totalPassed++;
                    if (grade.remarks === 'FAILED') totalFailed++;
                });
            }

            // Specialized Subjects
            if (specializedSubjects.length > 0) {
                html += '<tr><td colspan="5" class="category-header">SPECIALIZED SUBJECT</td></tr>';
                specializedSubjects.forEach(grade => {
                    html += buildEvaluationRow(grade);
                    totalSubjects++;
                    if (grade.remarks === 'PASSED') totalPassed++;
                    if (grade.remarks === 'FAILED') totalFailed++;
                });
            }

            html += '</tbody></table>';

            html += '</div>'; // Close semester-section
        });

        $('#evaluation-semesters-container').html(html);
    }

    function buildEvaluationRow(grade) {
        let html = '<tr>';
        html += '<td>' + grade.class_name + '</td>';
        
        // Q1 Grade
        html += '<td class="text-center">';
        html += grade.q1_grade !== null ? parseFloat(grade.q1_grade).toFixed(2) : '';
        html += '</td>';
        
        // Q2 Grade
        html += '<td class="text-center">';
        html += grade.q2_grade !== null ? parseFloat(grade.q2_grade).toFixed(2) : '';
        html += '</td>';
        
        // Final Grade
        html += '<td class="text-center">';
        html += grade.final_grade !== null ? parseFloat(grade.final_grade).toFixed(2) : '';
        html += '</td>';
        
        // Remarks
        html += '<td class="text-center">' + (grade.remarks || '') + '</td>';
        html += '</tr>';
        
        return html;
    }
});