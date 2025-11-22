console.log("page grade");

$(document).ready(function() {
    loadGrades();



    // Quiz title click handler - redirect to quiz
    $(document).on('click', '.quiz-title-link', function(e) {
        e.preventDefault();
        const lessonId = $(this).data('lesson-id');
        const quizId = $(this).data('quiz-id');
        
        if (lessonId && quizId) {
            const url = API_ROUTES.viewQuiz
                .replace(':lessonId', lessonId)
                .replace(':quizId', quizId);
            window.location.href = url;
        }
    });


});

function loadGrades() {
    showLoadingState();
    
    return $.ajax({
        url: API_ROUTES.getGrades,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Grades response:', response);
            
            if (response.grades && response.grades.length > 0) {
                populateGrades(response.grades);
                showTableState();
            } else {
                showEmptyState();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading grades:', {
                status: xhr.status,
                error: error,
                response: xhr.responseText
            });
            
            showEmptyState();
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load grades. Please try again.',
                confirmButtonColor: '#007bff'
            });
        }
    });
}

function populateGrades(grades) {
    populateMobileView(grades);
    populateDesktopView(grades);
}

function populateMobileView(grades) {
    const $container = $('#mobileGradesContainer');
    $container.empty();

    grades.forEach(function(grade) {
        const percentage = grade.percentage !== null ? grade.percentage.toFixed(2) : '-';
        const score = grade.score !== null ? grade.score.toFixed(2) : '-';
        const total = grade.total_points !== null ? grade.total_points.toFixed(2) : '-';
        
        // Format date - shorter for mobile
        const submittedDate = grade.submitted_at 
            ? new Date(grade.submitted_at).toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            })
            : 'Not submitted';

        // Determine pass/fail status
        const passed = grade.percentage >= grade.passing_score;
        const statusBadge = grade.percentage !== null 
            ? (passed 
                ? '<span class="badge badge-success">Passed</span>' 
                : '<span class="badge badge-danger">Failed</span>')
            : '<span class="badge badge-secondary">Pending</span>';

        const card = `
            <div class="card mb-3">
                <div class="card-body p-3">
                    <!-- Quiz Title -->
                    <div class="mb-3">
                        <a href="#" class="quiz-title-link text-dark font-weight-bold d-block" 
                           data-lesson-id="${grade.lesson_id}" 
                           data-quiz-id="${grade.quiz_id}"
                           style="text-decoration: none; font-size: 1.1rem; line-height: 1.4;">
                            ${grade.quiz_title || '-'}
                            <i class="fas fa-chevron-right text-primary ml-1" style="font-size: 0.8rem;"></i>
                        </a>
                        <small class="text-muted">${submittedDate}</small>
                    </div>
                    
                    <!-- Score Display -->
                    <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                        <div>
                            <div class="text-muted" style="font-size: 0.85rem;">Your Score</div>
                            <div class="font-weight-bold" style="font-size: 1.3rem;">${score} <span class="text-muted">/ ${total}</span></div>
                        </div>
                        <div class="text-right">
                            <div class="text-primary font-weight-bold" style="font-size: 1.5rem;">${percentage}%</div>
                        </div>
                    </div>
                    
                    <!-- Status and Passing Info -->
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Passing: ${grade.passing_score}%</small>
                        </div>
                        <div>
                            ${statusBadge}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $container.append(card);
    });
}
function populateDesktopView(grades) {
    const $tbody = $('#gradeTableBody');
    $tbody.empty();

    grades.forEach(function(grade) {
        const percentage = grade.percentage !== null ? grade.percentage.toFixed(2) : '-';
        const score = grade.score !== null ? grade.score.toFixed(2) : '-';
        const total = grade.total_points !== null ? grade.total_points.toFixed(2) : '-';
        
        // Format date
        const submittedDate = grade.submitted_at 
            ? new Date(grade.submitted_at).toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
            : 'Not submitted';

        // Quiz title as clickable link
        const quizTitleHtml = `
            <a href="#" class="quiz-title-link text-primary font-weight-bold" 
               data-lesson-id="${grade.lesson_id}" 
               data-quiz-id="${grade.quiz_id}"
               style="text-decoration: none;">
                ${grade.quiz_title || '-'}
                <i class="fas fa-external-link-alt ml-1" style="font-size: 0.8em;"></i>
            </a>
        `;

        const row = `
            <tr>
                <td>${quizTitleHtml}</td>
                <td class="text-center">
                    <strong>${score} / ${total}</strong>
                </td>
                <td class="text-center">
                    <strong class="text-primary">${percentage}%</strong>
                </td>
                <td class="text-center">
                    ${grade.passing_score}%
                </td>
                <td class="text-center">
                    ${submittedDate}
                </td>
            </tr>
        `;
        
        $tbody.append(row);
    });
}


function showLoadingState() {
    $('#loadingState').show();
    $('#emptyState').hide();
    $('#gradeTableContainer').hide();
}

function showEmptyState() {
    $('#loadingState').hide();
    $('#emptyState').show();
    $('#gradeTableContainer').hide();
}

function showTableState() {
    $('#loadingState').hide();
    $('#emptyState').hide();
    $('#gradeTableContainer').show();
}