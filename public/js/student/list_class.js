$(document).ready(function() {
    // CSRF token setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Configure toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 3000
    };

    // Load classes on page load
    loadClasses();

    /**
     * Load student classes
     */
    function loadClasses() {
        $('#loadingState').show();
        $('#emptyState').hide();
        $('#classesGrid').hide();

        $.ajax({
            url: API_ROUTES.getClasses,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.data.length === 0) {
                        showEmptyState();
                    } else {
                        displayClasses(response.data);
                    }
                } else {
                    showError('Failed to load classes');
                }
            },
            error: function(xhr) {
                showEmptyState();
                let message = 'Failed to load classes';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                if (xhr.status === 401) {
                    toastr.error('Please log in again');
                    setTimeout(() => {
                        window.location.href = '/student/login';
                    }, 2000);
                } else {
                    toastr.error(message);
                }
            }
        });
    }

    /**
     * Display classes for student (card grid view with teacher info)
     */
function displayClasses(classes) {
    $('#loadingState').hide();
    $('#emptyState').hide();
    $('#classesGrid').show();

    let grid = $('#classesGrid');
    grid.empty();

    classes.forEach(function(classData) {
        // Teacher info
        let teacherName = 'No teacher assigned';
        if (classData.teacher_name && classData.teacher_name.trim() !== '') {
            teacherName = escapeHtml(classData.teacher_name);
        }
        
        // Progress calculation (lessons instead of lectures)
        const progress = classData.progress_percentage || 0;
        const completed = classData.completed_lectures || 0;
        const total = classData.total_lectures || 0;
        
        // Next lesson info
        let nextLessonBadge = '';
        let actionButton = '';
        
        if (classData.next_lecture) {
            // Has next incomplete lecture - show lesson name
            nextLessonBadge = `
                <span class="badge badge-primary">
                    <i class="fas fa-book-reader"></i> ${escapeHtml(classData.next_lecture.lesson_title)}
                </span>
            `;
            
            actionButton = `
                <button class="btn btn-primary btn-sm btn-block continue-lecture-btn" 
                        data-class-id="${classData.id}"
                        data-lesson-id="${classData.next_lecture.lesson_id}"
                        data-lecture-id="${classData.next_lecture.lecture_id}">
                    <i class="fas fa-play"></i> Continue
                </button>
            `;
        } else if (total > 0) {
            // All completed
            nextLessonBadge = `
                <span class="badge badge-success">
                    <i class="fas fa-check-circle"></i> All lessons completed
                </span>
            `;
            
            actionButton = `
                <button class="btn btn-success btn-sm btn-block view-class-btn" 
                        data-class-id="${classData.id}">
                    <i class="fas fa-redo"></i> Review
                </button>
            `;
        } else {
            // No lessons yet
            nextLessonBadge = `
                <span class="badge badge-secondary">
                    <i class="fas fa-info-circle"></i> No lessons available
                </span>
            `;
            
            actionButton = `
                <button class="btn btn-secondary btn-sm btn-block" disabled>
                    <i class="fas fa-book-open"></i> View Lessons
                </button>
            `;
        }
        
        let card = `
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card card-primary card-outline h-100 d-flex flex-column">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-book"></i> ${escapeHtml(classData.class_name)}
                        </h3>
                    </div>
                    <div class="card-body flex-grow-1">
                        <p class="mb-2">
                            <i class="fas fa-chalkboard-teacher"></i> ${teacherName}
                        </p>
                        
                        <div class="mb-3">
                            ${nextLessonBadge}
                        </div>
                        
                        <div class="mt-auto">
                            <small class="text-muted d-block mb-2">
                                Progress: ${completed}/${total} lessons
                            </small>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar ${progress === 100 ? 'bg-success' : 'bg-primary'}" 
                                     style="width: ${progress}%">
                                </div>
                            </div>
                            <small class="text-muted">${progress}%</small>
                        </div>
                    </div>
                    <div class="card-footer">
                        ${actionButton}
                    </div>
                </div>
            </div>
        `;
        grid.append(card);
    });
}

// Handler for continue button
$(document).on('click', '.continue-lecture-btn', function() {
    let classId = $(this).data('class-id');
    let lessonId = $(this).data('lesson-id');
    let lectureId = $(this).data('lecture-id');
    
    $(this).html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
    
    window.location.href = `/student/class/${classId}/lesson/${lessonId}/lecture/${lectureId}`;
});
    /**
     * Show empty state
     */
    function showEmptyState() {
        $('#loadingState').hide();
        $('#classesGrid').hide();
        $('#emptyState').show();
    }

    /**
     * Handle view class button click - Redirect to lessons page
     */
    $(document).on('click', '.view-class-btn', function() {
        let classId = $(this).data('class-id');
        let className = $(this).data('class-name');
        
        // Show loading feedback
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
        
        // Redirect to student lessons page
        window.location.href = `/student/class/${classId}/lessons`;
    });

    /**
     * Show error message
     */
    function showError(message) {
        $('#loadingState').hide();
        toastr.error(message, 'Error');
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        let map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});