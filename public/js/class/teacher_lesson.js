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
    $.ajax({
        url: API_ROUTES.getLessons,
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        success: function(response) {
            if (response.success) {
                renderLessons(response.data);
                updateCounts(response.data);
            } else {
                toastr.error(response.message || 'Failed to load lessons');
                showEmptyState();
            }
        },
        error: function(xhr) {
            console.error('Error loading lessons:', xhr);
            toastr.error('Failed to load lessons');
            showEmptyState();
        }
    });
}

function renderLessons(lessons) {
    $('#loadingState').hide();
    
    if (!lessons || lessons.length === 0) {
        showEmptyState();
        return;
    }
    
    $('#emptyState').hide();
    const container = $('#lessonsContainer');
    const navigation = $('#lessonNavigation');
    container.empty();
    navigation.empty();
    
    // Build navigation
    let navHtml = '<div class="list-group list-group-flush">';
    lessons.forEach((lesson, index) => {
        const lessonNumber = index + 1;
        const lectureCount = lesson.lectures ? lesson.lectures.length : 0;
        const quizCount = lesson.quizzes ? lesson.quizzes.length : 0;
        
        navHtml += `
            <a href="#lesson-${lesson.id}" class="list-group-item list-group-item-action lesson-nav-link" data-lesson-id="${lesson.id}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge badge-primary badge-sm mr-2">${lessonNumber}</span>
                        <small>${escapeHtml(lesson.title)}</small>
                    </div>
                    <i class="fas fa-chevron-right text-muted"></i>
                </div>
                <div class="mt-1">
                    <span class="badge badge-light badge-sm mr-1">
                        <i class="fas fa-chalkboard-teacher"></i> ${lectureCount}
                    </span>
                    <span class="badge badge-light badge-sm">
                        <i class="fas fa-clipboard-check"></i> ${quizCount}
                    </span>
                </div>
            </a>
        `;
    });
    navHtml += '</div>';
    navigation.html(navHtml);
    
    // Add smooth scroll behavior
    $('.lesson-nav-link').on('click', function(e) {
        e.preventDefault();
        const targetId = $(this).attr('href');
        $('.lesson-nav-link').removeClass('active');
        $(this).addClass('active');
        $('html, body').animate({
            scrollTop: $(targetId).offset().top - 100
        }, 500);
    });
    
    // Render lesson cards
    lessons.forEach((lesson, index) => {
        const lessonNumber = index + 1;
        const lectureCount = lesson.lectures ? lesson.lectures.length : 0;
        const quizCount = lesson.quizzes ? lesson.quizzes.length : 0;
        
        let lecturesHtml = '';
        if (lesson.lectures && lesson.lectures.length > 0) {
            lesson.lectures.forEach(lecture => {
                const iconClass = getContentTypeIcon(lecture.content_type);
                lecturesHtml += `
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <i class="fas fa-${iconClass} text-primary mr-2"></i>
                            <span>${escapeHtml(lecture.title)}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-primary edit-lecture-btn" 
                                data-lesson-id="${lesson.id}" 
                                data-lecture-id="${lecture.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                `;
            });
        } else {
            lecturesHtml = '<p class="text-muted mb-0"><i class="fas fa-info-circle"></i> No lectures available yet</p>';
        }
        
        let quizzesHtml = '';
        if (lesson.quizzes && lesson.quizzes.length > 0) {
            lesson.quizzes.forEach(quiz => {
                quizzesHtml += `
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <i class="fas fa-clipboard-check text-info mr-2"></i>
                            <span>${escapeHtml(quiz.title)}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-info edit-quiz-btn" 
                                data-lesson-id="${lesson.id}" 
                                data-quiz-id="${quiz.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                `;
            });
        } else {
            quizzesHtml = '<p class="text-muted mb-0"><i class="fas fa-info-circle"></i> No quizzes available yet</p>';
        }
        
        const lessonCard = `
            <div class="card card-outline card-primary mb-3" id="lesson-${lesson.id}">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title">
                            <span class="badge badge-primary mr-2">${lessonNumber}</span>
                            <strong>${escapeHtml(lesson.title)}</strong>
                        </h3>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary edit-lesson-btn mr-1" 
                                    data-id="${lesson.id}" 
                                    data-title="${escapeHtml(lesson.title)}" 
                                    data-description="${escapeHtml(lesson.description || '')}">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </div>
                    </div>
                    ${lesson.description ? `<p class="text-muted mb-0 mt-2"><small>${escapeHtml(lesson.description)}</small></p>` : ''}
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <button class="btn btn-primary btn-sm create-lecture-btn mr-1" data-lesson-id="${lesson.id}">
                            <i class="fas fa-plus"></i> Add Lecture
                        </button>
                        <button class="btn btn-info btn-sm create-quiz-btn" data-lesson-id="${lesson.id}">
                            <i class="fas fa-plus"></i> Add Quiz
                        </button>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0"><i class="fas fa-chalkboard-teacher text-primary"></i> Lectures</h5>
                            </div>
                            <div class="mb-3">
                                ${lecturesHtml}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="mb-0"><i class="fas fa-clipboard-list text-info"></i> Quizzes</h5>
                            </div>
                            <div class="mb-3">
                                ${quizzesHtml}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.append(lessonCard);
    });
    
    // Highlight active nav item on scroll
    $(window).on('scroll', function() {
        let scrollPos = $(window).scrollTop() + 150;
        $('.lesson-nav-link').each(function() {
            const target = $(this).attr('href');
            const section = $(target);
            if (section.length) {
                const sectionTop = section.offset().top;
                const sectionBottom = sectionTop + section.outerHeight();
                if (scrollPos >= sectionTop && scrollPos < sectionBottom) {
                    $('.lesson-nav-link').removeClass('active');
                    $(this).addClass('active');
                }
            }
        });
    });
}

function showEmptyState() {
    $('#loadingState').hide();
    $('#emptyState').show();
    $('#lessonNavigation').html(`
        <div class="p-3 text-center text-muted">
            <small>No lessons to display</small>
        </div>
    `);
}

function updateCounts(lessons) {
    const lessonCount = lessons ? lessons.length : 0;
    let totalQuizzes = 0;
    
    if (lessons) {
        lessons.forEach(lesson => {
            if (lesson.quizzes) {
                totalQuizzes += lesson.quizzes.length;
            }
        });
    }
    
    $('#lessonCount').text(lessonCount);
    $('#totalQuizCount').text(totalQuizzes);
}

function getContentTypeIcon(type) {
    switch(type) {
        case 'video': return 'video';
        case 'pdf': return 'file-pdf';
        case 'file': return 'file';
        default: return 'file-alt';
    }
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