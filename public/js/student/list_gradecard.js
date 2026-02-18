console.log("Student Grade Card List");

$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    loadSemesters();
    loadSummary();

    // Handle semester selection change
    $('#semester-selector').on('change', function() {
        const semesterId = $(this).val();
        
        if (!semesterId) {
            $('#report-card-wrapper').hide();
            return;
        }

        const selectedOption = $(this).find('option:selected');
        const schoolYear = selectedOption.data('school-year');
        const semesterName = selectedOption.data('semester-name');
        
        loadGradeCard(semesterId, schoolYear, semesterName);
    });

    // Handle tab switching
    $('#summary-tab').on('shown.bs.tab', function() {
        // Reload summary when tab is shown
        loadSummary();
    });

    function loadSemesters() {
        $.ajax({
            url: '/student/gradecard/semesters',
            method: 'GET',
            success: function(response) {
                if (response.semesters && response.semesters.length > 0) {
                    displaySemesters(response.semesters);
                } else {
                    $('#semester-selector').html('<option value="">No grade records available</option>');
                }
            },
            error: function(xhr) {
                console.error('Error loading semesters:', xhr);
                $('#semester-selector').html('<option value="">Error loading semesters</option>');
            }
        });
    }

    function displaySemesters(semesters) {
        let html = '<option value="">-- Select a Semester --</option>';
        
        semesters.forEach(function(semester) {
            const isActive = semester.status === 'active';
            const activeLabel = isActive ? ' (Current)' : '';
            const selected = isActive ? 'selected' : '';
            
            html += `
                <option value="${semester.id}" 
                        data-school-year="${semester.school_year_code}"
                        data-semester-name="${semester.display_name}"
                        ${selected}>
                    ${semester.display_name}${activeLabel}
                </option>
            `;
        });
        
        $('#semester-selector').html(html);

        // Auto-load active semester if exists
        const activeSemester = $('#semester-selector').find('option:selected');
        if (activeSemester.val()) {
            loadGradeCard(
                activeSemester.val(),
                activeSemester.data('school-year'),
                activeSemester.data('semester-name')
            );
        }
    }

    function loadGradeCard(semesterId, schoolYear, semesterName) {
        // Show loading
        $('#grades-tbody').html(`
            <tr>
                <td colspan="5" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin"></i> Loading grades...
                </td>
            </tr>
        `);
        $('#report-card-wrapper').show();
        
        // Update display
        $('#school-year-display').text(schoolYear);
        $('#semester-display').text(semesterName);
        
        // Scroll to report card
        $('html, body').animate({
            scrollTop: $('#report-card-wrapper').offset().top - 20
        }, 500);

        $.ajax({
            url: '/student/gradecard/data',
            method: 'GET',
            data: { semester_id: semesterId },
            success: function(response) {
                if (response.grades && response.grades.length > 0) {
                    displayGrades(response.grades);
                    
                    // Update student info
                    if (response.student_info) {
                        const info = response.student_info;
                        $('#level-section-display').text(
                            info.level_name + (info.section_name ? ' - ' + info.section_name : ' - IRREGULAR')
                        );
                        $('#strand-display').text(info.strand_code || '-');
                        $('#adviser-display').text(info.adviser_name || 'N/A');
                    }
                } else {
                    $('#grades-tbody').html(`
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No subjects enrolled for this semester.
                            </td>
                        </tr>
                    `);
                }
            },
            error: function(xhr) {
                console.error('Error loading grades:', xhr);
                $('#grades-tbody').html(`
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Error loading grades. Please try again.
                        </td>
                    </tr>
                `);
            }
        });
    }

    function displayGrades(grades) {
        let html = '';
        
        // Group by category
        let coreSubjects = grades.filter(g => g.class_category === 'CORE SUBJECT');
        let appliedSubjects = grades.filter(g => g.class_category === 'APPLIED SUBJECT');
        let specializedSubjects = grades.filter(g => g.class_category === 'SPECIALIZED SUBJECT');
        
        let totalFinal = 0;
        let countWithGrades = 0;
        
        // Core Subjects
        if (coreSubjects.length > 0) {
            html += '<tr><td colspan="5" class="category-header">CORE SUBJECT</td></tr>';
            coreSubjects.forEach(function(grade) {
                html += buildGradeRow(grade);
                if (grade.final_grade !== null && grade.final_grade !== undefined) {
                    totalFinal += parseFloat(grade.final_grade);
                    countWithGrades++;
                }
            });
        }
        
        // Applied Subjects
        if (appliedSubjects.length > 0) {
            html += '<tr><td colspan="5" class="category-header">APPLIED SUBJECT</td></tr>';
            appliedSubjects.forEach(function(grade) {
                html += buildGradeRow(grade);
                if (grade.final_grade !== null && grade.final_grade !== undefined) {
                    totalFinal += parseFloat(grade.final_grade);
                    countWithGrades++;
                }
            });
        }
        
        // Specialized Subjects
        if (specializedSubjects.length > 0) {
            html += '<tr><td colspan="5" class="category-header">SPECIALIZED SUBJECT</td></tr>';
            specializedSubjects.forEach(function(grade) {
                html += buildGradeRow(grade);
                if (grade.final_grade !== null && grade.final_grade !== undefined) {
                    totalFinal += parseFloat(grade.final_grade);
                    countWithGrades++;
                }
            });
        }
        
        // General Average
        if (countWithGrades > 0) {
            const generalAverage = (totalFinal / countWithGrades).toFixed(2);
            html += `
                <tr class="general-average-row">
                    <td colspan="3" style="text-align: right; padding-right: 20px;">GENERAL AVERAGE</td>
                    <td colspan="2" class="text-center">${generalAverage}</td>
                </tr>
            `;
        }
        
        $('#grades-tbody').html(html);
    }

    function buildGradeRow(grade) {
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

    // Summary/Evaluation Functions
    function loadSummary() {
        $('#evaluation-semesters-container').html(`
            <div class="text-center py-4">
                <i class="fas fa-spinner fa-spin"></i> Loading evaluation...
            </div>
        `);

        $.ajax({
            url: '/student/gradecard/summary',
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
                console.error('Error loading summary:', xhr);
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