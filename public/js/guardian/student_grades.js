console.log("Guardian Student Grades");

$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Get student number from the page
    const studentNumber = $('#semester-selector').data('student-number');

    // Auto-load active semester on page load
    const activeSemester = $('#semester-selector').find('option:selected').val();
    if (activeSemester) {
        loadGrades(activeSemester);
    }

    $('#semester-selector').change(function() {
        const semesterId = $(this).val();
        
        if (!semesterId) {
            $('#report-card-wrapper').hide();
            return;
        }

        loadGrades(semesterId);
    });

    function loadGrades(semesterId) {
        // Show loading
        $('#grades-tbody').html('<tr><td colspan="5" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading grades...</td></tr>');
        $('#report-card-wrapper').show();
        
        // Get selected semester text
        const selectedText = $('#semester-selector option:selected').text();
        $('#semester-display').text(selectedText);
        
        // Get school year from the selected option
        const selectedOption = $('#semester-selector option:selected');
        const schoolYear = selectedOption.data('school-year');
        $('#school-year-display').text(schoolYear || '');
        
        // Fetch grades
        $.ajax({
            url: window.location.pathname + '/data',
            method: 'GET',
            data: { semester_id: semesterId },
            success: function(response) {
                if (response.grades && response.grades.length > 0) {
                    displayGrades(response.grades);
                    
                    // Update adviser name if available
                    if (response.adviser_name) {
                        $('#adviser-display').text(response.adviser_name);
                    }
                } else {
                    $('#grades-tbody').html('<tr><td colspan="5" class="text-center text-muted py-4">No subjects enrolled for this semester yet.</td></tr>');
                }
            },
            error: function(xhr) {
                console.error('Error loading grades:', xhr);
                $('#grades-tbody').html('<tr><td colspan="5" class="text-center text-danger py-4">Error loading grades. Please try again.</td></tr>');
            }
        });
    }

    function displayGrades(grades) {
        let html = '';
        
        // Group subjects by category from database
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
            html += '<tr class="general-average-row">';
            html += '<td colspan="3" style="text-align: right; padding-right: 20px;">GENERAL AVERAGE</td>';
            html += '<td colspan="2" class="text-center">' + generalAverage + '</td>';
            html += '</tr>';
        }
        
        $('#grades-tbody').html(html);
    }

    function buildGradeRow(grade) {
        let html = '<tr>';
        html += '<td>' + grade.class_name + '</td>';
        
        // Q1 Grade
        html += '<td class="text-center">';
        if (grade.q1_grade !== null && grade.q1_grade !== undefined) {
            html += parseFloat(grade.q1_grade).toFixed(2);
        } else {
            html += '';
        }
        html += '</td>';
        
        // Q2 Grade
        html += '<td class="text-center">';
        if (grade.q2_grade !== null && grade.q2_grade !== undefined) {
            html += parseFloat(grade.q2_grade).toFixed(2);
        } else {
            html += '';
        }
        html += '</td>';
        
        // Final Grade (Semester Average)
        html += '<td class="text-center">';
        if (grade.final_grade !== null && grade.final_grade !== undefined) {
            html += parseFloat(grade.final_grade).toFixed(2);
        } else {
            html += '';
        }
        html += '</td>';
        
        // Remarks
        html += '<td class="text-center">' + (grade.remarks || '') + '</td>';
        html += '</tr>';
        
        return html;
    }
});