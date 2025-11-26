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
        
        // Calculate statistics
        let totalStudents = grades.length;
        let totalQuizzes = allQuizzes.length;
        let classTotal = 0;
        let classCount = 0;
        let passingCount = 0;
        
        // Add student rows
        grades.forEach((student, index) => {
            let row = `
                <tr>
                    <td style="position: sticky; left: 0; z-index: 5; background-color: white;" class="text-center">
                        <strong>${index + 1}</strong>
                    </td>
                    <td style="position: sticky; left: 50px; z-index: 5; background-color: white;">
                        <strong>${student.full_name}</strong>
                        <br>
                        <small class="text-muted">${student.student_number}</small>
                    </td>
            `;
            
            let studentTotal = 0;
            let studentMax = 0;
            let scoredQuizzes = 0;
            
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
                    scoredQuizzes++;
                    
                    classTotal += percentage;
                    classCount++;
                    
                    if (percentage >= 60) passingCount++;
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
            
            $tbody.append(row);
        });
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