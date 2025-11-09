$(document).ready(function() {
    loadLessons();
    
    if (IS_TEACHER) {
        initTeacherFunctions();
    }
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
                        ${IS_TEACHER ? `
                        <a href="#" class="btn btn-sm btn-outline-primary" onclick="editLecture(${lesson.id}, ${lecture.id}); return false;">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        ` : `
                        <a href="#" class="btn btn-sm btn-outline-primary" onclick="viewLecture(${lesson.id}, ${lecture.id}); return false;">
                            <i class="fas fa-eye"></i> View
                        </a>
                        `}
                    </div>
                `;
            });
        } else {
            lecturesHtml = '<p class="text-muted mb-0"><i class="fas fa-info-circle"></i> No lectures added yet</p>';
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
                        ${IS_TEACHER ? `
                        <a href="#" class="btn btn-sm btn-outline-info" onclick="editQuiz(${lesson.id}, ${quiz.id}); return false;">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        ` : `
                        <a href="#" class="btn btn-sm btn-outline-info" onclick="takeQuiz(${lesson.id}, ${quiz.id}); return false;">
                            <i class="fas fa-play"></i> Take Quiz
                        </a>
                        `}
                    </div>
                `;
            });
        } else {
            quizzesHtml = '<p class="text-muted mb-0"><i class="fas fa-info-circle"></i> No quizzes added yet</p>';
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
                            <span class="badge badge-primary mr-1">
                                <i class="fas fa-chalkboard-teacher"></i> ${lectureCount} Lectures
                            </span>
                            <span class="badge badge-info mr-2">
                                <i class="fas fa-clipboard-check"></i> ${quizCount} Quizzes
                            </span>
                            ${IS_TEACHER ? `
                            <button class="btn btn-sm btn-primary" onclick="editLesson(${lesson.id})">
                                <i class="fas fa-cog"></i> Settings
                            </button>
                            ` : ''}
                        </div>
                    </div>
                    ${lesson.description ? `<p class="text-muted mb-0 mt-2"><small>${escapeHtml(lesson.description)}</small></p>` : ''}
                </div>
                <div class="card-body">
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
                    ${IS_TEACHER ? `
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <button class="btn btn-outline-primary btn-block" onclick="addLecture(${lesson.id})">
                                <i class="fas fa-plus-circle"></i> Add Lecture
                            </button>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-outline-info btn-block" onclick="addQuiz(${lesson.id})">
                                <i class="fas fa-plus-circle"></i> Add Quiz
                            </button>
                        </div>
                    </div>
                    ` : ''}
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
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Teacher-only functions
function initTeacherFunctions() {
    // Save lesson
    $('#saveLessonBtn').on('click', function() {
        const title = $('#lessonTitle').val().trim();
        const description = $('#lessonDescription').val().trim();
        
        if (!title) {
            toastr.warning('Please enter a lesson title');
            return;
        }
        
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
                    toastr.success('Lesson created successfully');
                    $('#addLessonModal').modal('hide');
                    $('#lessonForm')[0].reset();
                    loadLessons();
                } else {
                    toastr.error(response.message || 'Failed to create lesson');
                }
            },
            error: function(xhr) {
                console.error('Error creating lesson:', xhr);
                toastr.error('Failed to create lesson');
            }
        });
    });
    
    // Update lesson
    $('#updateLessonBtn').on('click', function() {
        const id = $('#editLessonId').val();
        const title = $('#editLessonTitle').val().trim();
        const description = $('#editLessonDescription').val().trim();
        
        if (!title) {
            toastr.warning('Please enter a lesson title');
            return;
        }
        
        const url = API_ROUTES.updateLesson.replace(':lessonId', id);
        
        $.ajax({
            url: url,
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            data: {
                title: title,
                description: description
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Lesson updated successfully');
                    $('#editLessonModal').modal('hide');
                    loadLessons();
                } else {
                    toastr.error(response.message || 'Failed to update lesson');
                }
            },
            error: function(xhr) {
                console.error('Error updating lesson:', xhr);
                toastr.error('Failed to update lesson');
            }
        });
    });
    
    // Delete lesson
    $('#deleteLessonBtn').on('click', function() {
        const id = $('#editLessonId').val();
        const title = $('#editLessonTitle').val();
        
        if (!confirm(`Are you sure you want to delete "${title}"?\n\nThis will permanently remove all lectures and quizzes in this lesson.\n\nThis action cannot be undone.`)) {
            return;
        }
        
        const url = API_ROUTES.deleteLesson.replace(':lessonId', id);
        
        $.ajax({
            url: url,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Lesson deleted successfully');
                    $('#editLessonModal').modal('hide');
                    loadLessons();
                } else {
                    toastr.error(response.message || 'Failed to delete lesson');
                }
            },
            error: function(xhr) {
                console.error('Error deleting lesson:', xhr);
                toastr.error('Failed to delete lesson');
            }
        });
    });
}

function editLesson(lessonId) {
    $.ajax({
        url: API_ROUTES.getLessons,
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        success: function(response) {
            if (response.success && response.data) {
                const lesson = response.data.find(l => l.id === lessonId);
                if (lesson) {
                    $('#editLessonId').val(lesson.id);
                    $('#editLessonTitle').val(lesson.title);
                    $('#editLessonDescription').val(lesson.description || '');
                    $('#editLessonModal').modal('show');
                }
            }
        }
    });
}

// Replace the addLecture function
function addLecture(lessonId) {
    window.location.href = API_ROUTES.createLecture.replace(':lessonId', lessonId);
}

// Replace the editLecture function
function editLecture(lessonId, lectureId) {
    window.location.href = API_ROUTES.editLecture
        .replace(':lessonId', lessonId)
        .replace(':lectureId', lectureId);
}


function viewLecture(lessonId, lectureId) {
    toastr.info('View Lecture functionality - To be implemented');
}

function addQuiz(lessonId) {
    toastr.info('Add Quiz functionality - To be implemented');
}

function editQuiz(lessonId, quizId) {
    toastr.info('Edit Quiz functionality - To be implemented');
}

function takeQuiz(lessonId, quizId) {
    toastr.info('Take Quiz functionality - To be implemented');
}