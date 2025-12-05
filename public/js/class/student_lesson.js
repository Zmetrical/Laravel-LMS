$(document).ready(function() {
    loadLessons();

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
                
                navHtml += `
                    <a href="#lesson-${lesson.id}" class="list-group-item list-group-item-action lesson-nav-link pl-4" data-lesson-id="${lesson.id}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small>${escapeHtml(lesson.title)}</small>
                            </div>
                            <i class="fas fa-chevron-right text-muted"></i>
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
            <div class="card card-primary mb-4">
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
                    <div class="card-body p-3">
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
        
        let lecturesHtml = '';
        if (lesson.lectures && lesson.lectures.length > 0) {
            lesson.lectures.forEach(lecture => {
                const iconClass = getContentTypeIcon(lecture.content_type);
                lecturesHtml += `
                    <div class="d-flex justify-content-between align-items-center py-3 px-4 border-bottom">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-${iconClass} text-primary mr-3" style="font-size: 1.1rem;"></i>
                            <span style="font-size: 0.95rem;">${escapeHtml(lecture.title)}</span>
                        </div>
                        <a href="#" class="btn btn-sm btn-outline-primary px-3" onclick="viewLecture(${lesson.id}, ${lecture.id}); return false;">
                            <i class="fas fa-eye mr-1"></i> View
                        </a>
                    </div>
                `;
            });
        } else {
            lecturesHtml = '<div class="text-center text-muted py-4"><i class="fas fa-info-circle mr-2"></i>No lectures available yet</div>';
        }
        
        html += `
            <div class="card mb-3 shadow-sm" id="lesson-${lesson.id}">
                <div class="card-header bg-white py-3">
                    <div class="d-flex align-items-center mb-2">
                        <h5 class="mb-0" style="font-weight: 600;">${escapeHtml(lesson.title)}</h5>
                    </div>
                    ${lesson.description ? `<p class="text-muted mb-0 mt-2 ml-5 pl-2">${escapeHtml(lesson.description)}</p>` : ''}
                </div>
                <div class="card-body p-0">
                    ${lecturesHtml}
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
        <div class="p-4 text-center text-muted">
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
    return text.replace(/[&<>"']/g, m => map[m]);
}

function viewLecture(lessonId, lectureId) {
    if (typeof API_ROUTES.viewLecture === 'undefined') {
        console.error('viewLecture route is not defined');
        toastr.error('Navigation route not configured');
        return;
    }
    
    window.location.href = API_ROUTES.viewLecture
        .replace(':lessonId', lessonId)
        .replace(':lectureId', lectureId);
}

function takeQuiz(lessonId, quizId) {
    if (typeof API_ROUTES.viewQuiz === 'undefined') {
        console.error('viewQuiz route is not defined');
        toastr.error('Navigation route not configured');
        return;
    }
    
    window.location.href = API_ROUTES.viewQuiz
        .replace(':lessonId', lessonId)
        .replace(':quizId', quizId);
}