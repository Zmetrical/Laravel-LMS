$(document).ready(function() {
    let availableClasses = [];
    let enrolledClasses = [];
    let selectedClasses = []; // Track selected classes for batch enrollment

    // Load initial data
    loadStudentInfo();
    loadStudentClasses();

    // Load student information
    function loadStudentInfo() {
        $.ajax({
            url: API_ROUTES.getStudentInfo,
            method: 'GET',
            success: function(response) {
                renderStudentInfo(response.data);
            },
            error: function() {
                $('#studentInfoContainer').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Failed to load student information
                    </div>
                `);
            }
        });
    }

    // Render student information
    function renderStudentInfo(student) {
        const html = `
            <dl class="row mb-0">
                <dt class="col-sm-5">Student Number:</dt>
                <dd class="col-sm-7"><strong>${student.student_number}</strong></dd>
                
                <dt class="col-sm-5">Full Name:</dt>
                <dd class="col-sm-7">${student.last_name}, ${student.first_name} ${student.middle_name}</dd>
                
                <dt class="col-sm-5">Grade Level:</dt>
                <dd class="col-sm-7">${student.level_name || 'N/A'}</dd>
                
                <dt class="col-sm-5">Strand:</dt>
                <dd class="col-sm-7">${student.strand_name || 'N/A'}</dd>
                
                <dt class="col-sm-5">Section:</dt>
                <dd class="col-sm-7">${student.section_name || '<span class="text-muted">N/A</span>'}</dd>
                
                <dt class="col-sm-5">Gender:</dt>
                <dd class="col-sm-7">${student.gender}</dd>
            </dl>
        `;
        $('#studentInfoContainer').html(html);
    }

    // Load student's classes
    function loadStudentClasses() {
        $.ajax({
            url: API_ROUTES.getStudentClasses,
            method: 'GET',
            success: function(response) {
                availableClasses = response.available;
                enrolledClasses = response.enrolled;
                selectedClasses = []; // Reset selection
                
                renderAvailableClasses();
                renderEnrolledClasses();
                updateEnrolledCount();
                updateSelectedCount();
            },
            error: function() {
                showAlert('error', 'Failed to load classes');
            }
        });
    }

    // Render available classes
    function renderAvailableClasses() {
        const container = $('#availableClassesContainer');
        container.empty();

        if (availableClasses.length === 0) {
            container.html(`
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                    <p>All classes are enrolled</p>
                </div>
            `);
            return;
        }

        availableClasses.forEach(cls => {
            const card = `
                <div class="card card-outline mb-2 available-class-item" 
                     data-class-id="${cls.id}"
                     data-class-name="${cls.class_name}">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="custom-control custom-checkbox flex-grow-1">
                                <input type="checkbox" 
                                       class="custom-control-input select-class-checkbox" 
                                       id="class_${cls.id}"
                                       data-class-id="${cls.id}"
                                       data-class-name="${cls.class_name}">
                                <label class="custom-control-label" for="class_${cls.id}">
                                    <strong>${cls.class_name}</strong><br>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> ${cls.teacher_name || 'No teacher'}
                                    </small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            container.append(card);
        });
    }

    // Render enrolled classes
    function renderEnrolledClasses() {
        const tbody = $('#enrolledClassesBody');
        const loadingIndicator = $('#enrolledClassesLoadingIndicator');
        const tableContainer = $('#enrolledClassesTableContainer');
        const noClassesMessage = $('#noEnrolledClassesMessage');

        loadingIndicator.hide();
        tbody.empty();

        if (enrolledClasses.length === 0) {
            tableContainer.hide();
            noClassesMessage.show();
            return;
        }

        tableContainer.show();
        noClassesMessage.hide();

        enrolledClasses.forEach(cls => {
            const row = `
                <tr>
                    <td>${cls.class_name}</td>
                    <td>
                        <small>
                            <i class="fas fa-user"></i> ${cls.teacher_name || 'No teacher'}
                        </small>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-danger unenroll-class-btn" 
                                data-class-id="${cls.id}"
                                data-class-name="${cls.class_name}">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Update enrolled count badge
    function updateEnrolledCount() {
        const count = enrolledClasses.length;
        $('#enrolledCountBadge').text(`${count} ${count === 1 ? 'Class' : 'Classes'}`);
    }

    // Update selected count badge
    function updateSelectedCount() {
        const count = selectedClasses.length;
        $('#selectedCountBadge').text(count);
        $('#enrollSelectedBtn').prop('disabled', count === 0);
        
        if (count > 0) {
            $('#enrollSelectedBtn').removeClass('btn-secondary').addClass('btn-success');
        } else {
            $('#enrollSelectedBtn').removeClass('btn-success').addClass('btn-secondary');
        }
    }

    // Handle checkbox selection
    $(document).on('change', '.select-class-checkbox', function() {
        const classId = parseInt($(this).data('class-id'));
        const className = $(this).data('class-name');
        
        if ($(this).is(':checked')) {
            // Add to selected
            if (!selectedClasses.find(c => c.id === classId)) {
                selectedClasses.push({ id: classId, name: className });
            }
        } else {
            // Remove from selected
            selectedClasses = selectedClasses.filter(c => c.id !== classId);
        }
        
        updateSelectedCount();
    });

    // Select all classes
    $('#selectAllClassesBtn').on('click', function() {
        $('.select-class-checkbox').prop('checked', true).trigger('change');
    });

    // Clear selection
    $('#clearSelectionBtn').on('click', function() {
        $('.select-class-checkbox').prop('checked', false);
        selectedClasses = [];
        updateSelectedCount();
    });

    // Enroll selected classes
    $('#enrollSelectedBtn').on('click', function() {
        if (selectedClasses.length === 0) {
            return;
        }

        const classNames = selectedClasses.map(c => c.name).join('<br>');
        
        Swal.fire({
            title: 'Confirm Enrollment',
            html: `Enroll the following ${selectedClasses.length} class(es)?<br><br><strong>${classNames}</strong>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Enroll',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                performEnrollment();
            }
        });
    });

    // Perform enrollment
    function performEnrollment() {
        const classIds = selectedClasses.map(c => c.id);
        
        // Show loading
        Swal.fire({
            title: 'Enrolling...',
            html: 'Please wait while we enroll the selected classes',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: API_ROUTES.enrollClass,
            method: 'POST',
            data: {
                student_id: STUDENT_ID,
                class_ids: classIds,
                semester_id: ACTIVE_SEMESTER_ID,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                    confirmButtonColor: '#007bff'
                });
                loadStudentClasses();
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Enrollment Failed',
                    text: xhr.responseJSON?.message || 'An error occurred',
                    confirmButtonColor: '#007bff'
                });
            }
        });
    }

    // Unenroll class
    $(document).on('click', '.unenroll-class-btn', function() {
        const classId = $(this).data('class-id');
        const className = $(this).data('class-name');
        
        Swal.fire({
            title: 'Remove Class?',
            html: `Are you sure you want to remove<br><strong>${className}</strong><br>from enrolled classes?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Remove',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                performUnenrollment(classId);
            }
        });
    });

    // Perform unenrollment
    function performUnenrollment(classId) {
        Swal.fire({
            title: 'Removing...',
            html: 'Please wait',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: API_ROUTES.unenrollClass,
            method: 'POST',
            data: {
                student_id: STUDENT_ID,
                class_id: classId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                    confirmButtonColor: '#007bff',
                    timer: 2000,
                    showConfirmButton: false
                });
                loadStudentClasses();
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Failed',
                    text: xhr.responseJSON?.message || 'An error occurred',
                    confirmButtonColor: '#007bff'
                });
            }
        });
    }

    // Search available classes
    $('#availableClassSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.available-class-item').each(function() {
            const className = $(this).data('class-name').toLowerCase();
            const matches = className.includes(searchTerm);
            $(this).toggle(matches);
        });
    });

    // Alert helper (fallback, but we use SweetAlert2)
    function showAlert(type, message) {
        if (type === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: message,
                confirmButtonColor: '#007bff',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: '#007bff'
            });
        }
    }
}); 