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
     * Load classes based on user type
     */
    function loadClasses() {
        $('#loadingState').show();
        $('#emptyState').hide();
        $('#classesGrid').hide();
        $('#classesList').hide();

        $.ajax({
            url: API_ROUTES.getClasses,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.data.length === 0) {
                        showEmptyState();
                    } else {
                        if (USER_TYPE === 'teacher') {
                            displayTeacherClasses(response.data);
                        } else {
                            displayStudentClasses(response.data);
                        }
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
                        window.location.href = USER_TYPE === 'teacher' ? '/teacher/login' : '/student/login';
                    }, 2000);
                } else {
                    toastr.error(message);
                }
            }
        });
    }

    /**
     * Display classes for teacher (card grid view)
     */
    function displayTeacherClasses(classes) {
        $('#loadingState').hide();
        $('#emptyState').hide();
        $('#classesGrid').show();

        let grid = $('#classesGrid');
        grid.empty();

        classes.forEach(function(classData) {
            let card = `
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="card card-primary card-outline h-100">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-book"></i> ${escapeHtml(classData.class_code)}
                            </h3>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-3">${escapeHtml(classData.class_name)}</h6>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary btn-sm btn-block view-class-btn" 
                                    data-class-id="${classData.id}"
                                    data-class-name="${escapeHtml(classData.class_name)}">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                </div>
            `;
            grid.append(card);
        });
    }

    /**
     * Display classes for student (card grid view with teacher info)
     */
    function displayStudentClasses(classes) {
        $('#loadingState').hide();
        $('#emptyState').hide();
        $('#classesGrid').show();

        let grid = $('#classesGrid');
        grid.empty();

        classes.forEach(function(classData) {
            let teacherInfo = '';
            if (classData.teacher_name && classData.teacher_name.trim() !== '') {
                teacherInfo = `
                    <div class="mb-3">
                        <p class="mb-1">
                            <i class="fas fa-chalkboard-teacher"></i> ${escapeHtml(classData.teacher_name)}
                        </p>
                    </div>
                `;
            } else {
                teacherInfo = `
                    <div class="mb-3">
                        <p class="mb-1 text-muted">
                            <i class="fas fa-user-slash"></i> No teacher assigned
                        </p>
                    </div>
                `;
            }
            
            let card = `
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="card card-primary card-outline h-100">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-book"></i> ${escapeHtml(classData.class_code)}
                            </h3>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-3">${escapeHtml(classData.class_name)}</h6>
                            ${teacherInfo}
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary btn-sm btn-block view-class-btn" 
                                    data-class-id="${classData.id}"
                                    data-class-name="${escapeHtml(classData.class_name)}">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                </div>
            `;
            grid.append(card);
        });
    }

    /**
     * Show empty state
     */
    function showEmptyState() {
        $('#loadingState').hide();
        $('#classesList').hide();
        $('#classesGrid').hide();
        $('#emptyState').show();
    }

    /**
     * Handle view class button click
     */
    $(document).on('click', '.view-class-btn', function() {
        let classId = $(this).data('class-id');
        let className = $(this).data('class-name');
        
        // Placeholder for future implementation
        toastr.info(`Opening class: ${className}`, 'Coming Soon');
        
        // Future routes will be:
        // Teacher: window.location.href = `/teacher/class/${classId}`;
        // Student: window.location.href = `/student/class/${classId}`;
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