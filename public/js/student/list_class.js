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
        
        // Progress with actual data
        const progress = classData.progress_percentage || 0;
        const completed = classData.completed_lectures || 0;
        const total = classData.total_lectures || 0;
        
        let progressHtml = `
            <div class="mt-auto">
                <small class="text-muted d-block mb-2">
                    <i class="fas fa-chart-line"></i> Progress: ${completed}/${total} lectures
                </small>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar ${progress === 100 ? 'bg-success' : 'bg-primary'}" 
                         role="progressbar" 
                         style="width: ${progress}%" 
                         aria-valuenow="${progress}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
                <small class="text-muted">${progress}% complete</small>
            </div>
        `;
        
        let card = `
            <div class="col-md-4 col-sm-6 mb-4">
                <div class="card card-primary card-outline h-100">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-book"></i> ${escapeHtml(classData.class_name)}
                        </h3>
                    </div>
                    <div class="card-body">
                        ${teacherInfo}
                        ${progressHtml}
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-primary btn-sm btn-block view-class-btn" 
                                data-class-id="${classData.id}"
                                data-class-code="${escapeHtml(classData.class_code)}"
                                data-class-name="${escapeHtml(classData.class_name)}">
                            <i class="fas fa-book-open"></i> View Lessons
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