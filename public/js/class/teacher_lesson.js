$(document).ready(function() {
    loadLessons();

    // Create Lesson
    $('#saveLessonBtn').on('click', function() {
        createLesson();
    });

    // Update Lesson
    $('#updateLessonBtn').on('click', function() {
        updateLesson();
    });

    // Delete Lesson
    $('#deleteLessonBtn').on('click', function() {
        deleteLesson();
    });

    // Edit lesson modal trigger
    $(document).on('click', '.edit-lesson-btn', function() {
        const lessonId = $(this).data('id');
        const title = $(this).data('title');
        const description = $(this).data('description');

        $('#editLessonId').val(lessonId);
        $('#editLessonTitle').val(title);
        $('#editLessonDescription').val(description);
        $('#editLessonModal').modal('show');
    });

    // Create Lecture button
    $(document).on('click', '.create-lecture-btn', function() {
        const lessonId = $(this).data('lesson-id');
        const url = API_ROUTES.createLecture.replace(':lessonId', lessonId);
        window.location.href = url;
    });

    // Create Quiz button
    $(document).on('click', '.create-quiz-btn', function() {
        const lessonId = $(this).data('lesson-id');
        const url = API_ROUTES.createQuiz.replace(':lessonId', lessonId);
        window.location.href = url;
    });

    // Edit Lecture button
    $(document).on('click', '.edit-lecture-btn', function() {
        const lessonId = $(this).data('lesson-id');
        const lectureId = $(this).data('lecture-id');
        const url = API_ROUTES.editLecture
            .replace(':lessonId', lessonId)
            .replace(':lectureId', lectureId);
        window.location.href = url;
    });

    // Edit Quiz button
    $(document).on('click', '.edit-quiz-btn', function() {
        const lessonId = $(this).data('lesson-id');
        const quizId = $(this).data('quiz-id');
        const url = API_ROUTES.editQuiz
            .replace(':lessonId', lessonId)
            .replace(':quizId', quizId);
        window.location.href = url;
    });
});

function loadLessons() {
    $('#loadingState').show();
    $('#emptyState').hide();
    $('#lessonsContainer').empty();

    $.ajax({
        url: API_ROUTES.getLessons,
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        success: function(response) {
            $('#loadingState').hide();
            
            if (response.success && response.data.length > 0) {
                renderLessons(response.data);
                buildNavigation(response.data);
            } else {
                $('#emptyState').show();
            }
        },
        error: function(xhr) {
            $('#loadingState').hide();
            console.error('Error loading lessons:', xhr);
            toastr.error('Failed to load lessons');
        }
    });
}

function renderLessons(lessons) {
    const container = $('#lessonsContainer');
    container.empty();

    lessons.forEach((lesson, index) => {
        const lessonHtml = `
            <div class="card card-outline card-primary" id="lesson-${lesson.id}">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-book"></i> 
                        <strong>Lesson ${index + 1}:</strong> ${escapeHtml(lesson.title)}
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-primary edit-lesson-btn" 
                                data-id="${lesson.id}" 
                                data-title="${escapeHtml(lesson.title)}" 
                                data-description="${escapeHtml(lesson.description || '')}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    ${lesson.description ? `<p class="text-muted">${escapeHtml(lesson.description)}</p>` : ''}
                    
                    <!-- Action Buttons -->
                    <div class="mb-3">
                        <button class="btn btn-info btn-sm create-lecture-btn" data-lesson-id="${lesson.id}">
                            <i class="fas fa-plus"></i> Add Lecture
                        </button>
                        <button class="btn btn-success btn-sm create-quiz-btn" data-lesson-id="${lesson.id}">
                            <i class="fas fa-plus"></i> Add Quiz
                        </button>
                    </div>

                    <div class="row">
                        <!-- Lectures Section -->
                        <div class="col-md-6">
                            <h5 class="text-info">
                                <i class="fas fa-video"></i> Lectures 
                                <span class="badge badge-info">${lesson.lectures.length}</span>
                            </h5>
                            ${renderLectures(lesson.lectures, lesson.id)}
                        </div>

                        <!-- Quizzes Section -->
                        <div class="col-md-6">
                            <h5 class="text-success">
                                <i class="fas fa-clipboard-list"></i> Quizzes 
                                <span class="badge badge-success">${lesson.quizzes.length}</span>
                            </h5>
                            ${renderQuizzes(lesson.quizzes, lesson.id)}
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.append(lessonHtml);
    });
}

function renderLectures(lectures, lessonId) {
    if (!lectures || lectures.length === 0) {
        return '<p class="text-muted"><small>No lectures yet</small></p>';
    }

    let html = '<ul class="list-group list-group-flush">';
    lectures.forEach((lecture, index) => {
        const icon = getContentTypeIcon(lecture.content_type);
        html += `
            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <div>
                    <i class="fas fa-${icon} text-primary mr-2"></i>
                    ${escapeHtml(lecture.title)}
                </div>
                <button class="btn btn-xs btn-outline-primary edit-lecture-btn" 
                        data-lesson-id="${lessonId}" 
                        data-lecture-id="${lecture.id}">
                    <i class="fas fa-edit"></i>
                </button>
            </li>
        `;
    });
    html += '</ul>';
    return html;
}

function renderQuizzes(quizzes, lessonId) {
    if (!quizzes || quizzes.length === 0) {
        return '<p class="text-muted"><small>No quizzes yet</small></p>';
    }

    let html = '<ul class="list-group list-group-flush">';
    quizzes.forEach((quiz, index) => {
        html += `
            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                <div>
                    <i class="fas fa-clipboard-list text-success mr-2"></i>
                    ${escapeHtml(quiz.title)}
                    <br>
                    <small class="text-muted">
                        ${quiz.time_limit ? quiz.time_limit + ' min' : 'No time limit'} | 
                        Pass: ${quiz.passing_score}%
                    </small>
                </div>
                <button class="btn btn-xs btn-outline-success edit-quiz-btn" 
                        data-lesson-id="${lessonId}" 
                        data-quiz-id="${quiz.id}">
                    <i class="fas fa-edit"></i>
                </button>
            </li>
        `;
    });
    html += '</ul>';
    return html;
}

function buildNavigation(lessons) {
    const nav = $('#lessonNavigation');
    
    if (!lessons || lessons.length === 0) {
        nav.html('<div class="p-3 text-center text-muted"><small>No lessons</small></div>');
        return;
    }

    let html = '<div class="list-group list-group-flush">';
    
    lessons.forEach((lesson, index) => {
        html += `
            <a href="#lesson-${lesson.id}" class="list-group-item list-group-item-action lesson-nav-link">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge badge-primary mr-2">${index + 1}</span>
                        <small>${escapeHtml(lesson.title)}</small>
                    </div>
                    <div>
                        <span class="badge badge-info" title="Lectures">${lesson.lectures.length}</span>
                        <span class="badge badge-success" title="Quizzes">${lesson.quizzes.length}</span>
                    </div>
                </div>
            </a>
        `;
    });
    
    html += '</div>';
    nav.html(html);
}

function createLesson() {
    const title = $('#lessonTitle').val().trim();
    const description = $('#lessonDescription').val().trim();

    if (!title) {
        toastr.warning('Please enter a lesson title');
        return;
    }

    const btn = $('#saveLessonBtn');
    const originalHtml = btn.html();
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');

    $.ajax({
        url: API_ROUTES.createLesson,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        data: {
            title: title,
            description: description
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Lesson created successfully');
                $('#addLessonModal').modal('hide');
                $('#lessonForm')[0].reset();
                loadLessons();
            } else {
                toastr.error(response.message || 'Failed to create lesson');
            }
            btn.prop('disabled', false).html(originalHtml);
        },
        error: function(xhr) {
            console.error('Error creating lesson:', xhr);
            let errorMsg = 'Failed to create lesson';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            toastr.error(errorMsg);
            btn.prop('disabled', false).html(originalHtml);
        }
    });
}

function updateLesson() {
    const lessonId = $('#editLessonId').val();
    const title = $('#editLessonTitle').val().trim();
    const description = $('#editLessonDescription').val().trim();

    if (!title) {
        toastr.warning('Please enter a lesson title');
        return;
    }

    const btn = $('#updateLessonBtn');
    const originalHtml = btn.html();
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

    $.ajax({
        url: API_ROUTES.updateLesson.replace(':lessonId', lessonId),
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Content-Type': 'application/json'
        },
        data: JSON.stringify({
            title: title,
            description: description
        }),
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Lesson updated successfully');
                $('#editLessonModal').modal('hide');
                loadLessons();
            } else {
                toastr.error(response.message || 'Failed to update lesson');
            }
            btn.prop('disabled', false).html(originalHtml);
        },
        error: function(xhr) {
            console.error('Error updating lesson:', xhr);
            toastr.error('Failed to update lesson');
            btn.prop('disabled', false).html(originalHtml);
        }
    });
}

function deleteLesson() {
    const lessonId = $('#editLessonId').val();
    const title = $('#editLessonTitle').val();

    if (!confirm(`Are you sure you want to delete "${title}"?\n\nThis will hide the lesson and all its content from students.\n\nContinue?`)) {
        return;
    }

    const btn = $('#deleteLessonBtn');
    const originalHtml = btn.html();
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');

    $.ajax({
        url: API_ROUTES.deleteLesson.replace(':lessonId', lessonId),
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message || 'Lesson deleted successfully');
                $('#editLessonModal').modal('hide');
                loadLessons();
            } else {
                toastr.error(response.message || 'Failed to delete lesson');
            }
            btn.prop('disabled', false).html(originalHtml);
        },
        error: function(xhr) {
            console.error('Error deleting lesson:', xhr);
            toastr.error('Failed to delete lesson');
            btn.prop('disabled', false).html(originalHtml);
        }
    });
}

function getContentTypeIcon(type) {
    const icons = {
        'video': 'video',
        'pdf': 'file-pdf',
        'file': 'file',
        'text': 'file-alt'
    };
    return icons[type] || 'file-alt';
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