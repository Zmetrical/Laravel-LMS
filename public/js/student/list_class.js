$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 3000
    };

    loadClasses();

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
                        displayClasses(response.data, response.active_quarter);
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

    function displayClasses(classes, activeQuarter) {
        $('#loadingState').hide();
        $('#emptyState').hide();
        $('#classesGrid').show();

        let grid = $('#classesGrid');
        grid.empty();

        classes.forEach(function(classData) {
            let teacherName = 'No teacher assigned';
            if (classData.teacher_name && classData.teacher_name.trim() !== '') {
                teacherName = escapeHtml(classData.teacher_name);
            }
            
            const progress = classData.progress_percentage || 0;
            const completed = classData.completed_lectures || 0;
            const total = classData.total_lectures || 0;
            

            
            // Next lesson info with quarter - clickable card
            let nextLessonCard = '';
            
            if (classData.next_lecture) {
                let lessonQuarterInfo = '';
                if (classData.next_lecture.quarter_name) {
                    lessonQuarterInfo = `
                        <small class="text-muted">
                            <i class="fas fa-book-reader"></i> ${escapeHtml(classData.next_lecture.quarter_name)}
                        </small>
                    `;
                }
                
                nextLessonCard = `
                    <div class="callout callout-primary continue-lecture-btn" 
                         style="cursor: pointer; margin-bottom: 1rem;"
                         data-class-id="${classData.id}"
                         data-lesson-id="${classData.next_lecture.lesson_id}"
                         data-lecture-id="${classData.next_lecture.lecture_id}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Next Lesson:</strong><br>
                                ${escapeHtml(classData.next_lecture.lesson_title)}<br>
                                ${lessonQuarterInfo}
                            </div>
                            <div>
                                <i class="fas fa-play-circle fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                `;
            } else if (total > 0) {
                nextLessonCard = `
                    <div class="callout callout-success" style="margin-bottom: 1rem;">
                        <i class="fas fa-check-circle"></i> All lessons completed!
                    </div>
                `;
            } else {
                nextLessonCard = `
                    <div class="callout callout-secondary" style="margin-bottom: 1rem;">
                        <i class="fas fa-info-circle"></i> No lessons available yet
                    </div>
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
                                                        
                            ${nextLessonCard}
                            
                            <div class="mt-auto">
                                <small class="text-muted d-block mb-2">
                                    Overall Progress: ${completed}/${total} lessons
                                </small>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar ${progress === 100 ? 'bg-success' : 'bg-primary'}" 
                                         style="width: ${progress}%">
                                    </div>
                                </div>
                                <small class="text-muted">${progress}%</small>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary btn-sm btn-block view-class-btn" 
                                    data-class-id="${classData.id}">
                                <i class="fas fa-list"></i> View All Lessons
                            </button>
                        </div>
                    </div>
                </div>
            `;
            grid.append(card);
        });
    }

    $(document).on('click', '.continue-lecture-btn', function(e) {
        e.preventDefault();
        let classId = $(this).data('class-id');
        let lessonId = $(this).data('lesson-id');
        let lectureId = $(this).data('lecture-id');
        
        // Add loading state to the callout
        $(this).css('opacity', '0.6').css('pointer-events', 'none');
        $(this).find('.fa-play-circle').removeClass('fa-play-circle').addClass('fa-spinner fa-spin');
        
        window.location.href = `/student/class/${classId}/lesson/${lessonId}/lecture/${lectureId}`;
    });

    $(document).on('click', '.view-class-btn', function(e) {
        e.preventDefault();
        let classId = $(this).data('class-id');
        
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
        
        window.location.href = `/student/class/${classId}/lessons`;
    });

    function showEmptyState() {
        $('#loadingState').hide();
        $('#classesGrid').hide();
        $('#emptyState').show();
    }

    function showError(message) {
        $('#loadingState').hide();
        toastr.error(message, 'Error');
    }

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