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
        const quarterId = $(this).data('quarter-id');

        $('#editLessonId').val(lessonId);
        $('#editLessonTitle').val(title);
        $('#editLessonDescription').val(description);
        $('#editLessonQuarter').val(quarterId);
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

    // Toggle quarter collapse
    $(document).on('click', '.quarter-header', function() {
        const icon = $(this).find('.toggle-icon');
        icon.toggleClass('fa-chevron-down fa-chevron-up');
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

function renderLessons(quarters) {
    $('#loadingState').hide();
    
    if (!quarters || quarters.length === 0) {
        showEmptyState();
        return;
    }
    
    const hasLessons = quarters.some(q => q.lessons && q.lessons.length > 0);
    
    if (!hasLessons) {
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
    quarters.forEach(quarter => {
        if (quarter.lessons && quarter.lessons.length > 0) {
            navHtml += `
                <div class="list-group-item bg-light">
                    <small class="font-weight-bold text-secondary">${escapeHtml(quarter.name)}</small>
                </div>
            `;
            
            quarter.lessons.forEach((lesson, index) => {
                const lessonNumber = index + 1;
                const lectureCount = lesson.lectures ? lesson.lectures.length : 0;
                const quizCount = lesson.quizzes ? lesson.quizzes.length : 0;
                
                navHtml += `
                    <a href="#lesson-${lesson.id}" class="list-group-item list-group-item-action lesson-nav-link pl-4" data-lesson-id="${lesson.id}">
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
        }
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
    
    // Render quarter sections
    quarters.forEach(quarter => {
        if (!quarter.lessons || quarter.lessons.length === 0) return;
        
        const quarterCard = `
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
                    <div class="card-body p-2">
                        ${renderQuarterLessons(quarter)}
                    </div>
                </div>
            </div>
        `;
        
        container.append(quarterCard);
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

function renderQuarterLessons(quarter) {
    let html = '';
    
    quarter.lessons.forEach((lesson, index) => {
        const lessonNumber = index + 1;
        
        let lecturesHtml = '';
        if (lesson.lectures && lesson.lectures.length > 0) {
            lesson.lectures.forEach(lecture => {
                const iconClass = getContentTypeIcon(lecture.content_type);
                lecturesHtml += `
                    <div class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                        <div>
                            <i class="fas fa-${iconClass} text-primary mr-2"></i>
                            <span>${escapeHtml(lecture.title)}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-primary edit-lecture-btn" 
                                data-lesson-id="${lesson.id}" 
                                data-lecture-id="${lecture.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                `;
            });
        } else {
            lecturesHtml = '<p class="text-muted mb-0 px-3 py-2"><small><i class="fas fa-info-circle"></i> No lectures yet</small></p>';
        }
        
        let quizzesHtml = '';
        if (lesson.quizzes && lesson.quizzes.length > 0) {
            lesson.quizzes.forEach(quiz => {
                quizzesHtml += `
                    <div class="d-flex justify-content-between align-items-center py-2 px-3 border-bottom">
                        <div>
                            <i class="fas fa-clipboard-check text-info mr-2"></i>
                            <span>${escapeHtml(quiz.title)}</span>
                        </div>
                        <button class="btn btn-sm btn-outline-info edit-quiz-btn" 
                                data-lesson-id="${lesson.id}" 
                                data-quiz-id="${quiz.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                `;
            });
        } else {
            quizzesHtml = '<p class="text-muted mb-0 px-3 py-2"><small><i class="fas fa-info-circle"></i> No quizzes yet</small></p>';
        }
        
        html += `
            <div class="card mb-2" id="lesson-${lesson.id}">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge badge-primary mr-2">${lessonNumber}</span>
                            <strong>${escapeHtml(lesson.title)}</strong>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-primary edit-lesson-btn" 
                                    data-id="${lesson.id}" 
                                    data-title="${escapeHtml(lesson.title)}" 
                                    data-description="${escapeHtml(lesson.description || '')}"
                                    data-quarter-id="${quarter.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </div>
                    ${lesson.description ? `<p class="text-muted mb-0 mt-2"><small>${escapeHtml(lesson.description)}</small></p>` : ''}
                </div>
                <div class="card-body p-0">
                    <div class="p-2">
                        <button class="btn btn-primary btn-sm create-lecture-btn mr-1" data-lesson-id="${lesson.id}">
                            <i class="fas fa-plus"></i> Add Lecture
                        </button>
                        <button class="btn btn-info btn-sm create-quiz-btn" data-lesson-id="${lesson.id}">
                            <i class="fas fa-plus"></i> Add Quiz
                        </button>
                    </div>
                    
                    <div class="row no-gutters">
                        <div class="col-md-6 border-right">
                            <div class="p-2 bg-light border-bottom">
                                <small class="font-weight-bold">
                                    <i class="fas fa-chalkboard-teacher text-primary"></i> Lectures
                                </small>
                            </div>
                            ${lecturesHtml}
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 bg-light border-bottom">
                                <small class="font-weight-bold">
                                    <i class="fas fa-clipboard-list text-info"></i> Quizzes
                                </small>
                            </div>
                            ${quizzesHtml}
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    return html;
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

function updateCounts(quarters) {
    let totalLessons = 0;
    let totalQuizzes = 0;
    
    if (quarters) {
        quarters.forEach(quarter => {
            if (quarter.lessons) {
                totalLessons += quarter.lessons.length;
                quarter.lessons.forEach(lesson => {
                    if (lesson.quizzes) {
                        totalQuizzes += lesson.quizzes.length;
                    }
                });
            }
        });
    }
    
    $('#lessonCount').text(totalLessons);
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
    const quarterId = $('#lessonQuarter').val();

    if (!title) {
        toastr.warning('Please enter a lesson title');
        return;
    }

    if (!quarterId) {
        toastr.warning('Please select a quarter');
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
            description: description,
            quarter_id: quarterId
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
    const quarterId = $('#editLessonQuarter').val();

    if (!title) {
        toastr.warning('Please enter a lesson title');
        return;
    }

    if (!quarterId) {
        toastr.warning('Please select a quarter');
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
            description: description,
            quarter_id: quarterId
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