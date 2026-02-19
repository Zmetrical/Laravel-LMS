$(document).ready(function () {
    let availableClasses = [];
    let enrolledClasses = [];
    let selectedClasses = [];

    // Toast config (matches teacher page)
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    // Load initial data
    loadStudentInfo();
    loadStudentClasses();

    // ========================================================================
    // LOAD STUDENT INFO
    // ========================================================================
    function loadStudentInfo() {
        $.ajax({
            url: API_ROUTES.getStudentInfo,
            method: 'GET',
            success: function (response) {
                renderStudentInfo(response.data);
            },
            error: function () {
                $('#studentInfoContainer').html(`
                    <div class="text-center py-3 text-danger">
                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                        <p class="mb-0">Failed to load student information</p>
                    </div>
                `);
            }
        });
    }

    // ========================================================================
    // RENDER STUDENT INFO
    // ========================================================================
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
            </dl>
        `;
        $('#studentInfoContainer').html(html);
    }

    // ========================================================================
    // LOAD STUDENT CLASSES
    // ========================================================================
    function loadStudentClasses() {
        showLoader('#availableClassesContainer');
        showTableLoader('#enrolledClassesLoadingIndicator');
    $('#enrolledClassesTableContainer').hide();
    $('#noEnrolledClassesMessage').hide();

        $.ajax({
            url: API_ROUTES.getStudentClasses,
            method: 'GET',
            success: function (response) {
                availableClasses = response.available;
                enrolledClasses = response.enrolled;
                selectedClasses = [];

                renderAvailableClasses();
                renderEnrolledClasses();
                updateEnrolledCount();
                updateSelectedCount();
            },
            error: function () {
                showError('#availableClassesContainer', 'Failed to load classes');
                showToast('error', 'Could not load classes. Please refresh the page.');
            }
        });
    }

    // ========================================================================
    // RENDER AVAILABLE CLASSES
    // ========================================================================
    function renderAvailableClasses() {
        const container = $('#availableClassesContainer');
        container.empty();

        if (availableClasses.length === 0) {
            container.html(`
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p class="mb-0">All classes are enrolled</p>
                </div>
            `);
            return;
        }

        let items = '';
        availableClasses.forEach(cls => {
            items += `
                <a href="#" class="list-group-item list-group-item-action available-class-item"
                   data-class-id="${cls.id}"
                   data-class-name="${cls.class_name}">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox"
                               class="custom-control-input select-class-checkbox"
                               id="class_${cls.id}"
                               data-class-id="${cls.id}"
                               data-class-name="${cls.class_name}">
                        <label class="custom-control-label w-100" for="class_${cls.id}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong>${cls.class_name}</strong>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-user-tie"></i> ${cls.teacher_name || 'No teacher'}
                                    </small>
                                </div>
                            </div>
                        </label>
                    </div>
                </a>
            `;
        });

        container.html(`<div class="list-group list-group-flush">${items}</div>`);
    }

    // ========================================================================
    // RENDER ENROLLED CLASSES
    // ========================================================================
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
            tbody.append(`
                <tr>
                    <td><strong>${cls.class_name}</strong></td>
                    <td>
                        <small class="text-muted">
                            <i class="fas fa-user-tie"></i> ${cls.teacher_name || 'No teacher'}
                        </small>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-default unenroll-class-btn"
                                data-class-id="${cls.id}"
                                data-class-name="${cls.class_name}">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    // ========================================================================
    // COUNT BADGES
    // ========================================================================
    function updateEnrolledCount() {
        const count = enrolledClasses.length;
        $('#enrolledCountBadge').text(`${count} ${count === 1 ? 'Class' : 'Classes'}`);
    }

    function updateSelectedCount() {
        const count = selectedClasses.length;
        $('#selectedCountBadge').text(count);
        $('#enrollSelectedBtn').prop('disabled', count === 0);
    }

    // ========================================================================
    // CHECKBOX SELECTION
    // ========================================================================
    $(document).on('change', '.select-class-checkbox', function () {
        const classId = parseInt($(this).data('class-id'));
        const className = $(this).data('class-name');

        if ($(this).is(':checked')) {
            if (!selectedClasses.find(c => c.id === classId)) {
                selectedClasses.push({ id: classId, name: className });
            }
        } else {
            selectedClasses = selectedClasses.filter(c => c.id !== classId);
        }

        updateSelectedCount();
    });

    // Clear selection
    $('#clearSelectionBtn').on('click', function () {
        $('#availableClassSearch').val('');
        $('.available-class-item').show();
        $('.select-class-checkbox').prop('checked', false);
        selectedClasses = [];
        updateSelectedCount();
    });

    // ========================================================================
    // ENROLL SELECTED
    // ========================================================================
    $('#enrollSelectedBtn').on('click', function () {
        if (selectedClasses.length === 0) return;

        const classNames = selectedClasses.map(c => `<strong>${c.name}</strong>`).join('<br>');

        Swal.fire({
            title: 'Confirm Enrollment',
            html: `Enroll the following ${selectedClasses.length} class(es)?<br><br>${classNames}`,
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

    function performEnrollment() {
        const classIds = selectedClasses.map(c => c.id);
        const btn = $('#enrollSelectedBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Enrolling...');

        $.ajax({
            url: API_ROUTES.enrollClass,
            method: 'POST',
            data: {
                student_id: STUDENT_ID,
                class_ids: classIds,
                semester_id: ACTIVE_SEMESTER_ID,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                showToast('success', response.message);
                loadStudentClasses();
            },
            error: function (xhr) {
                showToast('error', xhr.responseJSON?.message || 'An error occurred');
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="fas fa-plus-circle"></i> Enroll Selected Classes');
            }
        });
    }

    // ========================================================================
    // UNENROLL
    // ========================================================================
    $(document).on('click', '.unenroll-class-btn', function () {
        const classId = $(this).data('class-id');
        const className = $(this).data('class-name');

        Swal.fire({
            title: 'Remove Class?',
            html: `Remove <strong>${className}</strong> from enrolled classes?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Remove',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                performUnenrollment(classId, $(this));
            }
        });
    });

    function performUnenrollment(classId, btn) {
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: API_ROUTES.unenrollClass,
            method: 'POST',
            data: {
                student_id: STUDENT_ID,
                class_id: classId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                showToast('success', response.message);
                loadStudentClasses();
            },
            error: function (xhr) {
                showToast('error', xhr.responseJSON?.message || 'An error occurred');
                btn.prop('disabled', false).html('<i class="fas fa-times"></i> Remove');
            }
        });
    }

    // ========================================================================
    // SEARCH AVAILABLE CLASSES
    // ========================================================================
    $('#availableClassSearch').on('keyup', function () {
        const searchTerm = $(this).val().toLowerCase();
        $('.available-class-item').each(function () {
            const className = $(this).data('class-name').toLowerCase();
            $(this).toggle(className.includes(searchTerm));
        });
    });

    // ========================================================================
    // UTILITY FUNCTIONS
    // ========================================================================
    function showLoader(selector, message = 'Loading...') {
        $(selector).html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2 mb-0">${message}</p>
            </div>
        `);
    }

    function showTableLoader(selector) {
        $(selector).show().html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Loading enrolled classes...</p>
            </div>
        `);
    }

    function showError(selector, message = 'An error occurred') {
        $(selector).html(`
            <div class="text-center py-4 text-danger">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <p class="mb-0">${message}</p>
            </div>
        `);
    }

    function showToast(icon, title) {
        Toast.fire({ icon: icon, title: title });
    }
});