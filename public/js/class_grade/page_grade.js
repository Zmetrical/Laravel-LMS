console.log("Enhanced Grade Page - Clean Design");

let gradesData = [];
let quartersData = [];

$(document).ready(function() {
    loadGrades();

    // Toggle quarter collapse
    $(document).on('click', '.quarter-header', function() {
        const icon = $(this).find('.toggle-icon');
        icon.toggleClass('fa-chevron-down fa-chevron-up');
    });

    // View quiz button click
    $(document).on('click', '.btn-view-quiz', function(e) {
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
    
    $.ajax({
        url: API_ROUTES.getGrades,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Grades response:', response);
            
            gradesData = response.grades || [];
            quartersData = response.quarters || [];
            
            if (gradesData.length > 0) {
                renderQuarters();
                showMainContent();
            } else {
                showEmptyState();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading grades:', error);
            showEmptyState();
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load grades. Please refresh the page.',
                confirmButtonColor: '#007bff'
            });
        }
    });
}

function renderQuarters() {
    const $container = $('#quartersContainer');
    $container.empty();
    
    // Group quizzes by quarter
    const quarterGroups = {};
    
    gradesData.forEach(function(lesson) {
        lesson.quizzes.forEach(function(quiz) {
            const quarterId = quiz.quarter_id || 'unassigned';
            const quarterName = quiz.quarter_name || 'Unassigned';
            
            if (!quarterGroups[quarterId]) {
                quarterGroups[quarterId] = {
                    id: quarterId,
                    name: quarterName,
                    quizzes: []
                };
            }
            
            quarterGroups[quarterId].quizzes.push({
                ...quiz,
                lesson_id: lesson.lesson_id,
                lesson_title: lesson.lesson_title
            });
        });
    });
    
    // Sort quarters
    const sortedQuarters = Object.values(quarterGroups).sort((a, b) => {
        if (a.id === 'unassigned') return 1;
        if (b.id === 'unassigned') return -1;
        return a.id - b.id;
    });
    
    // Render each quarter
    sortedQuarters.forEach(function(quarter) {
        $container.append(buildQuarterSection(quarter));
    });
}

function buildQuarterSection(quarter) {
    // Build quiz rows
    let quizRowsHtml = '';
    quarter.quizzes.forEach(function(quiz) {
        quizRowsHtml += buildQuizRow(quiz);
    });
    
    return `
        <div class="card card-primary mb-3">
            <div class="card-header quarter-header" data-toggle="collapse" data-target="#quarter-${quarter.id}" style="cursor: pointer;">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        ${escapeHtml(quarter.name)}
                    </h4>
                    <i class="fas fa-chevron-down toggle-icon"></i>
                </div>
            </div>
            <div id="quarter-${quarter.id}" class="collapse show">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="30%">Quiz</th>
                                    <th width="12%" class="text-center">Score</th>
                                    <th width="12%" class="text-center">Percentage</th>
                                    <th width="15%" class="text-center">Status</th>
                                    <th width="21%">Date</th>
                                    <th width="10%" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${quizRowsHtml}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function buildQuizRow(quiz) {
    const now = new Date();
    const availableFrom = quiz.available_from ? new Date(quiz.available_from) : null;
    const availableUntil = quiz.available_until ? new Date(quiz.available_until) : null;
    
    // Determine actual quiz state based on dates
    let quizState = 'unknown';
    
    if (quiz.submitted_at) {
        // Already submitted
        quizState = 'submitted';
    } else if (availableFrom && now < availableFrom) {
        // Not yet open (upcoming)
        quizState = 'upcoming';
    } else if (availableUntil && now > availableUntil) {
        // Already closed
        quizState = 'closed';
    } else if (quiz.is_available) {
        // Currently available
        quizState = 'available';
    } else {
        // Not available (no dates or other reason)
        quizState = 'unavailable';
    }
    
    // Status badge based on submission
    let statusBadge = '';
    if (quiz.status === 'passed') {
        statusBadge = '<span class="badge">Passed</span>';
    } else if (quiz.status === 'failed') {
        statusBadge = '<span class="badge badge-danger">Failed</span>';
    } else if (quizState === 'available') {
        statusBadge = '<span class="badge badge-primary">Available</span>';
    } else if (quizState === 'upcoming') {
        statusBadge = '<span class="badge badge-secondary">Upcoming</span>';
    } else if (quizState === 'closed') {
        statusBadge = '<span class="badge badge-secondary">Closed</span>';
    } else if (quizState === 'unavailable') {
        statusBadge = '<span class="badge badge-secondary">Unavailable</span>';
    }
    
    // Score display
    let scoreHtml = '<span class="text-muted">—</span>';
    let percentageHtml = '<span class="text-muted">—</span>';
    
    if (quiz.score !== null) {
        // Has a score - show it
        const scoreColor = quiz.status === 'failed' ? 'text-danger' : '';
        scoreHtml = `<strong class="${scoreColor}">${quiz.score}/${quiz.total_points}</strong>`;
        percentageHtml = `<span class="${scoreColor}">${quiz.percentage}%</span>`;
    } else if (quizState === 'closed') {
        // Closed and not taken = 0
        scoreHtml = `<strong class="text-danger">0/${quiz.total_points || 0}</strong>`;
        percentageHtml = `<span class="text-danger">0%</span>`;
    }
    
    // Date info with visual indicators
    let dateHtml = '';
    if (quizState === 'submitted') {
        dateHtml = `
            <div class="quiz-date">
                <i class="fas fa-check-circle mr-1"></i>
                <small class="text-muted">Submitted: ${formatDate(quiz.submitted_at)}</small>
            </div>
        `;
    } else if (quizState === 'upcoming') {
        dateHtml = `
            <div class="quiz-date">
                <i class="fas fa-clock mr-1"></i>
                <small class="text-muted">Opens: ${formatDate(availableFrom)}</small>
            </div>
        `;
    } else if (quizState === 'available') {
        dateHtml = `
            <div class="quiz-date">
                <i class="fas fa-circle text-primary mr-1" style="font-size: 8px;"></i>
                <small class="text-primary font-weight-bold">Available Now</small>
                ${availableUntil ? `<br><small class="text-muted ml-3">Closes: ${formatDate(availableUntil)}</small>` : ''}
            </div>
        `;
    } else if (quizState === 'closed') {
        dateHtml = `
            <div class="quiz-date">
                <i class="fas fa-lock mr-1"></i>
                <small class="text-muted">Closed: ${formatDate(availableUntil)}</small>
            </div>
        `;
    } else {
        dateHtml = `
            <div class="quiz-date">
                <i class="fas fa-ban mr-1"></i>
                <small class="text-muted">Unavailable</small>
            </div>
        `;
    }
    
    // Action button - only show for available quizzes
    let actionButton = '';
    if (quizState === 'available' || quizState === 'submitted') {
        actionButton = `
            <button class="btn btn-primary btn-sm btn-view-quiz" 
                    data-lesson-id="${quiz.lesson_id}"
                    data-quiz-id="${quiz.quiz_id}">
                <i class="fas fa-eye"></i> View
            </button>
        `;
    } else {
        actionButton = '<span class="text-muted">—</span>';
    }
    
    return `
        <tr>
            <td class="align-middle">
                <strong>${escapeHtml(quiz.quiz_title)}</strong>
                ${quiz.lesson_title ? `<br><small class="text-muted">${escapeHtml(quiz.lesson_title)}</small>` : ''}
            </td>
            <td class="align-middle text-center">
                ${scoreHtml}
            </td>
            <td class="align-middle text-center">
                ${percentageHtml}
            </td>
            <td class="align-middle text-center">
                ${statusBadge}
            </td>
            <td class="align-middle">
                ${dateHtml}
            </td>
            <td class="align-middle text-center">
                ${actionButton}
            </td>
        </tr>
    `;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
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
    return text.replace(/[&<>"']/g, m => map[m]);
}

function showLoadingState() {
    $('#loadingState').show();
    $('#emptyState').hide();
    $('#mainContent').hide();
}

function showEmptyState() {
    $('#loadingState').hide();
    $('#emptyState').show();
    $('#mainContent').hide();
}

function showMainContent() {
    $('#loadingState').hide();
    $('#emptyState').hide();
    $('#mainContent').show();
}