console.log("page gradebook");

// Teacher Grades JS
$(document).ready(function() {
    const classId = API_ROUTES.classId;
    let allGrades = [];
    let allQuizzes = [];
    let classInfo = null;
    let uniqueSections = new Set();
    
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
                
                // Populate section filter
                populateSectionFilter();
                
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

    function populateSectionFilter() {
        uniqueSections.clear();
        allGrades.forEach(function(student) {
            if (student.section_name && student.section_name !== 'No Section') {
                uniqueSections.add(student.section_name);
            }
        });

        const $sectionFilter = $('#sectionFilter');
        $sectionFilter.find('option:not(:first)').remove();
        
        Array.from(uniqueSections).sort().forEach(function(section) {
            $sectionFilter.append(`<option value="${section}">${section}</option>`);
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

        // Separate by gender (already sorted by backend)
        const maleGrades = grades.filter(g => g.gender === 'male');
        const femaleGrades = grades.filter(g => g.gender === 'female');
        const unknownGrades = grades.filter(g => g.gender === 'unknown');

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

        // Render Unknown Gender Section (if any)
        if (unknownGrades.length > 0) {
            $tbody.append(`
                <tr class="bg-secondary">
                    <td colspan="${quizCount + 2}" class="font-weight-bold" style="position: sticky; left: 0; z-index: 10;">
                        <i class="fas fa-question mr-2"></i>OTHER (${unknownGrades.length})
                    </td>
                </tr>
            `);

            unknownGrades.forEach(student => {
                $tbody.append(renderStudentRow(student, rowCounter++));
            });
        }

    }

    function renderStudentRow(student, index) {
        let row = `
            <tr>
                <td>
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
        const sectionFilter = $('#sectionFilter').val();
        const genderFilter = $('#genderFilter').val();
        const typeFilter = $('#typeFilter').val();

        const filtered = allGrades.filter(student => {
            const matchSearch = !searchTerm || 
                student.student_number.toLowerCase().includes(searchTerm) ||
                student.full_name.toLowerCase().includes(searchTerm);

            const matchSection = !sectionFilter || 
                student.section_name === sectionFilter;

            const matchGender = !genderFilter || 
                student.gender === genderFilter.toLowerCase();

            const matchType = !typeFilter || 
                student.student_type === typeFilter.toLowerCase();
            
            return matchSearch && matchSection && matchGender && matchType;
        });

        renderGradeTable(filtered);
    }

    $('#gradeSearchFilter, #sectionFilter, #genderFilter, #typeFilter').on('input change', function() {
        applyGradeFilters();
    });

    $('#resetGradeFiltersBtn').click(function() {
        $('#gradeSearchFilter').val('');
        $('#sectionFilter').val('');
        $('#genderFilter').val('');
        $('#typeFilter').val('');
        renderGradeTable(allGrades);
    });
    
    $('#refreshGrades').click(function() {
        loadGrades();
    });
    
    // Initial load
    loadGrades();
});