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
     * Load teacher classes
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
                        window.location.href = '/teacher/login';
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
    function displayClasses(classes) {
        $('#loadingState').hide();
        $('#emptyState').hide();
        $('#classesGrid').show();

        let grid = $('#classesGrid');
        grid.empty();

        classes.forEach(function(classData) {
            // Calculate statistics (placeholder - can be populated from backend)
            let studentCount = classData.student_count || 0;
            let lessonCount = classData.lesson_count || 0;
            
            let statsHtml = `
                <div class="row text-center mt-3">
                    <div class="col-6">
                        <div class="border-right">
                            <h5 class="mb-0 text-primary">${studentCount}</h5>
                            <small class="text-muted">Students</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <h5 class="mb-0 text-info">${lessonCount}</h5>
                        <small class="text-muted">Lessons</small>
                    </div>
                </div>
            `;
            
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
                            ${statsHtml}
                        </div>
                        <div class="card-footer">
                            <div class="btn-group btn-block" role="group">
                                <button class="btn btn-primary btn-sm view-class-btn" 
                                        data-class-id="${classData.id}"
                                        data-class-code="${escapeHtml(classData.class_code)}"
                                        data-class-name="${escapeHtml(classData.class_name)}"
                                        style="flex: 1;">
                                    <i class="fas fa-book-open"></i> View Lessons
                                </button>
                                <button class="btn btn-secondary btn-sm view-gradebook-btn" 
                                        data-class-id="${classData.id}"
                                        data-class-code="${escapeHtml(classData.class_code)}"
                                        data-class-name="${escapeHtml(classData.class_name)}"
                                        style="flex: 1;">
                                    <i class="fas fa-table"></i> Gradebook
                                </button>
                            </div>
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
        
        // Redirect to teacher lessons page
        window.location.href = `/teacher/class/${classId}/lessons`;
    });

    /**
     * Handle view gradebook button click - Redirect to gradebook page
     */
    $(document).on('click', '.view-gradebook-btn', function() {
        let classId = $(this).data('class-id');
        let className = $(this).data('class-name');
        
        // Show loading feedback
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Loading...').prop('disabled', true);
        
        // Redirect to gradebook page using API_ROUTES
        if (API_ROUTES.gradebook) {
            window.location.href = API_ROUTES.gradebook.replace(':classId', classId);
        } else {
            toastr.error('Gradebook access not available');
            $(this).html('<i class="fas fa-table"></i> Gradebook').prop('disabled', false);
        }
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