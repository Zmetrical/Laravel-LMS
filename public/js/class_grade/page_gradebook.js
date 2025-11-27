console.log("page gradebook");

// Teacher Grades JS
$(document).ready(function() {
    const classId = API_ROUTES.classId;
    let allGrades = [];
    let allQuizzes = [];
    let classInfo = null;
    
    function loadGrades() {
        $('#loadingState').show();
        $('#gradeTableContainer').hide();
        $('#emptyState').hide();
        
        console.log('Loading grades for class:', classId);
        
        $.ajax({
            url: API_ROUTES.getGrades,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log('Full Response:', response);
                console.log('Grades:', response.grades);
                console.log('Grades Length:', response.grades.length);
                console.log('Quizzes:', response.quizzes);
                console.log('Quizzes Length:', response.quizzes.length);
                
                $('#loadingState').hide();
                
                if (!response.grades || response.grades.length === 0 || !response.quizzes || response.quizzes.length === 0) {
                    console.log('No data found - showing empty state');
                    $('#emptyState').show();
                    return;
                }
                
                // Store data globally
                allGrades = response.grades;
                allQuizzes = response.quizzes;
                classInfo = response.class;
                
                console.log('Rendering grade table...');
                renderGradeTable(allGrades);
                $('#gradeTableContainer').show();
            },
            error: function(xhr) {
                console.error('Error loading grades:', xhr);
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                
                $('#loadingState').hide();
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load grades'
                });
            }
        });
    }
    
    function renderGradeTable(grades) {
        const $thead = $('#gradeTable thead tr');
        const $tbody = $('#gradeTableBody');
        
        // Clear existing content (keep first two columns)
        $thead.find('th:not(:first):not(:nth-child(2))').remove();
        $tbody.empty();
        
        // Add quiz columns
        allQuizzes.forEach(quiz => {
            $thead.append(`
                <th class="text-center" title="${quiz.lesson_title}">
                    ${quiz.title}
                </th>
            `);
        });
        
        // Add summary columns
        $thead.append(`
            <th class="text-center bg-light">Total Score</th>
            <th class="text-center bg-light">Average %</th>
        `);
        
        // Get student details and merge with grades
        $.ajax({
            url: API_ROUTES.getStudents,
            method: 'GET',
            async: false,
            success: function(response) {
                // Create a map of student details
                const studentDetailsMap = {};
                response.students.forEach(student => {
                    studentDetailsMap[student.student_number] = student;
                });

                // Add gender info to grades
                grades.forEach(grade => {
                    const details = studentDetailsMap[grade.student_number];
                    grade.gender = details ? details.gender : 'unknown';
                });
            }
        });

        // Sort grades by gender (Male first, then Female)
        const sortedGrades = grades.sort((a, b) => {
            if (a.gender.toLowerCase() === b.gender.toLowerCase()) return 0;
            return a.gender.toLowerCase() === 'male' ? -1 : 1;
        });

        // Separate by gender
        const maleGrades = sortedGrades.filter(g => g.gender.toLowerCase() === 'male');
        const femaleGrades = sortedGrades.filter(g => g.gender.toLowerCase() === 'female');

        let rowCounter = 1;
        const quizCount = allQuizzes.length + 2; // +2 for summary columns

        // Render Male Section
        if (maleGrades.length > 0) {
            $tbody.append(`
                <tr class="bg-secondary">
                    <td colspan="${quizCount + 2}" class="font-weight-bold" style="position: sticky; left: 0; z-index: 10;">
                        <i class="fas fa-mars mr-2"></i>MALE (${maleGrades.length})
                    </td>
                </tr>
            `);

            maleGrades.forEach(student => {
                $tbody.append(renderStudentRow(student, rowCounter++));
            });
        }

        // Render Female Section
        if (femaleGrades.length > 0) {
            $tbody.append(`
                <tr class="bg-secondary">
                    <td colspan="${quizCount + 2}" class="font-weight-bold" style="position: sticky; left: 0; z-index: 10;">
                        <i class="fas fa-venus mr-2"></i>FEMALE (${femaleGrades.length})
                    </td>
                </tr>
            `);

            femaleGrades.forEach(student => {
                $tbody.append(renderStudentRow(student, rowCounter++));
            });
        }
    }

    function renderStudentRow(student, index) {
        let row = `
            <tr>
                <td >
                    <strong>${student.student_number}</strong>
                </td>
                <td>
                    <strong>${student.full_name}</strong>
                </td>
        `;
        
        let studentTotal = 0;
        let studentMax = 0;
        
        allQuizzes.forEach(quiz => {
            const quizGrade = student.quizzes[quiz.id];
            
            if (quizGrade.score !== null) {
                const percentage = quizGrade.percentage;
                
                row += `
                    <td class="text-center">
                        <span>${quizGrade.score} / ${quizGrade.total}</span>
                        <br>
                        <small class="text-muted">${percentage}%</small>
                    </td>
                `;
                
                studentTotal += parseFloat(quizGrade.score);
                studentMax += parseFloat(quizGrade.total);
            } else {
                row += `<td class="text-center text-muted">-</td>`;
            }
        });
        
        const studentAvg = studentMax > 0 ? ((studentTotal / studentMax) * 100).toFixed(2) : 0;

        row += `
                <td class="text-center bg-light">
                    <strong>${studentTotal.toFixed(2)} / ${studentMax.toFixed(2)}</strong>
                </td>
                <td class="text-center bg-light">
                    <span>${studentAvg}%</span>
                </td>
            </tr>
        `;
        
        return row;
    }
    
    // ========================================================================
    // GRADE FILTERS
    // ========================================================================
    function applyGradeFilters() {
        const searchTerm = $('#gradeSearchFilter').val().toLowerCase();

        const filtered = allGrades.filter(student => {
            const matchSearch = !searchTerm || 
                student.student_number.toLowerCase().includes(searchTerm) ||
                student.full_name.toLowerCase().includes(searchTerm);
            
            return matchSearch;
        });

        renderGradeTable(filtered);
    }

    $('#gradeSearchFilter').on('input change', function() {
        applyGradeFilters();
    });

    $('#resetGradeFiltersBtn').click(function() {
        $('#gradeSearchFilter').val('');
        renderGradeTable(allGrades);
    });
    
    $('#refreshGrades').click(function() {
        loadGrades();
    });
    
    // Initial load
    loadGrades();
});