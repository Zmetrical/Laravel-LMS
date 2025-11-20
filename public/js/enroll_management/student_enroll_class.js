$(document).ready(function() {
    let availableClasses = [];
    let enrolledClasses = [];

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
                
                renderAvailableClasses();
                renderEnrolledClasses();
                updateEnrolledCount();
            },
            error: function() {
                showToast('error', 'Failed to load classes');
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
                     data-class-name="${cls.class_name}"
                     data-class-code="${cls.class_code}">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${cls.class_code}</strong><br>
                                <small>${cls.class_name}</small><br>
                                <small class="text-muted">
                                    <i class="fas fa-user"></i> ${cls.teacher_name || 'No teacher'}
                                </small>
                            </div>
                            <button class="btn btn-sm btn-primary enroll-class-btn" 
                                    data-class-id="${cls.id}">
                                <i class="fas fa-plus"></i> Enroll
                            </button>
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
                    <td><strong>${cls.class_code}</strong></td>
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

    // Enroll class
    $(document).on('click', '.enroll-class-btn', function() {
        const classId = $(this).data('class-id');
        const btn = $(this);
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: API_ROUTES.enrollClass,
            method: 'POST',
            data: {
                student_id: STUDENT_ID,
                class_ids: [classId],
                semester_id: ACTIVE_SEMESTER_ID,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showToast('success', response.message);
                loadStudentClasses();
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'Enrollment failed');
                btn.prop('disabled', false).html('<i class="fas fa-plus"></i> Enroll');
            }
        });
    });

    // Unenroll class
    $(document).on('click', '.unenroll-class-btn', function() {
        const classId = $(this).data('class-id');
        const className = $(this).data('class-name');
        
        if (!confirm(`Remove "${className}" from enrolled classes?`)) {
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: API_ROUTES.unenrollClass,
            method: 'POST',
            data: {
                student_id: STUDENT_ID,
                class_id: classId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showToast('success', response.message);
                loadStudentClasses();
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'Unenrollment failed');
                btn.prop('disabled', false).html('<i class="fas fa-times"></i> Remove');
            }
        });
    });

    // Search available classes
    $('#availableClassSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.available-class-item').each(function() {
            const className = $(this).data('class-name').toLowerCase();
            const classCode = $(this).data('class-code').toLowerCase();
            const matches = className.includes(searchTerm) || classCode.includes(searchTerm);
            $(this).toggle(matches);
        });
    });

    // Toast notification
    function showToast(type, message) {
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 3000
        };

        if (type === 'success') {
            toastr.success(message);
        } else {
            toastr.error(message);
        }
    }
});