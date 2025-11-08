$(document).ready(function() {
    let allClasses = [];

    // Configure toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 3000
    };

    // Load classes on page load
    loadClasses();

    // ========================================================================
    // LOAD CLASSES
    // ========================================================================
    function loadClasses() {
        $('#loadingState').show();
        $('#emptyState').hide();
        $('#classesGrid').hide();

        $.ajax({
            url: API_ROUTES.getClasses,
            method: 'GET',
            success: function(response) {
                if (response.success && response.data && response.data.length > 0) {
                    allClasses = response.data;
                    renderClasses(allClasses);
                } else {
                    showEmptyState();
                }
            },
            error: function(xhr) {
                showEmptyState();
                if (xhr.status === 401) {
                    toastr.error('Please log in again');
                    setTimeout(() => {
                        window.location.href = '/student/login';
                    }, 2000);
                } else {
                    toastr.error('Failed to load classes. Please refresh the page.');
                }
            }
        });
    }

    // ========================================================================
    // RENDER CLASSES
    // ========================================================================
    function renderClasses(classes) {
        $('#loadingState').hide();
        $('#emptyState').hide();
        
        const grid = $('#classesGrid');
        grid.empty().show();

        classes.forEach(cls => {
            const teacherInfo = cls.teacher_name && cls.teacher_name.trim() !== ''
                ? `<p class="mb-1"><i class="fas fa-chalkboard-teacher"></i> ${cls.teacher_name}</p>`
                : `<p class="mb-1 text-muted"><i class="fas fa-user-slash"></i> No teacher assigned</p>`;

            const card = `
                <div class="col-md-4 col-sm-6 mb-4">
                    <div class="card card-primary card-outline h-100">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-book"></i> ${cls.class_code}
                            </h3>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-3">${cls.class_name}</h6>
                            
                            <div class="mb-3">
                                ${teacherInfo}
                            </div>
                            
                            <div class="mt-auto">
                                <small class="text-muted d-block mb-2">
                                    <i class="fas fa-chart-line"></i> Progress placeholder
                                </small>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: 0%" 
                                         aria-valuenow="0" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-primary btn-sm btn-block view-details-btn" 
                                    data-class-id="${cls.id}"
                                    data-class-name="${cls.class_name}">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            grid.append(card);
        });
    }

    // ========================================================================
    // SHOW EMPTY STATE
    // ========================================================================
    function showEmptyState() {
        $('#loadingState').hide();
        $('#classesGrid').hide();
        $('#emptyState').show();
    }

});