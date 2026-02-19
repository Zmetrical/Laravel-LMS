console.log("Irreg Grade View");

$(document).ready(function () {
    const Toast = Swal.mixin({
        toast: true,
        position: "top-right",
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true
    });

    let allStudents          = [];
    let selectedStudentNumber = null;

    loadStudents();

    // ========================================================================
    // LOAD STUDENTS
    // ========================================================================
    function loadStudents() {
        showContainerLoader('#studentListContainer');

        $.ajax({
            url: API_ROUTES.getStudents,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    allStudents = response.students || [];
                    renderStudentList(allStudents);
                } else {
                    showContainerError('#studentListContainer', response.message || 'Failed to load students');
                }
            },
            error: function (xhr) {
                showContainerError('#studentListContainer', xhr.responseJSON?.message || 'Failed to load students');
                Toast.fire({ icon: 'error', title: 'Could not load students. Please refresh.' });
            }
        });
    }

    // ========================================================================
    // RENDER STUDENT LIST
    // ========================================================================
    function renderStudentList(students) {
        const container        = $('#studentListContainer');
        const search           = $('#studentSearch').val().toLowerCase();
        const submissionFilter = $('#submissionFilter').val();

        let filtered = students.filter(function (s) {
            const fullName = `${s.first_name} ${s.middle_name || ''} ${s.last_name}`.toLowerCase();
            const matchSearch = !search ||
                s.student_number.toLowerCase().includes(search) ||
                fullName.includes(search);

            let matchStatus = true;
            if (submissionFilter === 'complete') {
                matchStatus = s.submitted_count >= s.total_classes && s.total_classes > 0;
            } else if (submissionFilter === 'partial') {
                matchStatus = s.submitted_count > 0 && s.submitted_count < s.total_classes;
            } else if (submissionFilter === 'none') {
                matchStatus = s.submitted_count === 0;
            }

            return matchSearch && matchStatus;
        });

        $('#studentListCount').text(`${filtered.length} Student${filtered.length !== 1 ? 's' : ''}`);

        container.empty();

        if (filtered.length === 0) {
            container.html(`
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p class="mb-0 small">${allStudents.length === 0 ? 'No irregular students enrolled this semester' : 'No students match your filters'}</p>
                </div>
            `);
            return;
        }

        filtered.forEach(function (student) {
            const fullName   = `${student.last_name}, ${student.first_name}${student.middle_name ? ' ' + student.middle_name : ''}`;
            const percentage = student.submission_percentage || 0;
            const meta       = [student.level_name, student.strand_code || student.strand_name]
                                 .filter(Boolean).join(' - ');

            const isSelected = student.student_number === selectedStudentNumber;

            container.append(`
                <a href="#" class="list-group-item list-group-item-action student-list-item ${isSelected ? 'active' : ''}"
                   data-student-number="${student.student_number}">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <h6 class="mb-1"><strong>${fullName}</strong></h6>
                            <p class="mb-1 text-muted small">${student.student_number}</p>
                            <small class="text-muted">
                                ${meta ? `<i class="fas fa-graduation-cap"></i> ${meta} &nbsp;` : ''}
                            </small>
                            <div class="submission-progress mt-2">
                                <div class="submission-progress-bar bg-primary"
                                     style="width: ${percentage}%"></div>
                            </div>
                            <small class="text-muted">${student.submitted_count}/${student.total_classes} submitted</small>
                        </div>
                    </div>
                </a>
            `);
        });
    }

    // ========================================================================
    // STUDENT LIST ITEM CLICK
    // ========================================================================
    $(document).on('click', '.student-list-item', function (e) {
        e.preventDefault();

        $('.student-list-item').removeClass('active');
        $(this).addClass('active');

        selectedStudentNumber = $(this).data('student-number');

        $('#noStudentSelected').hide();
        $('#studentDetails').show();

        loadStudentDetails(selectedStudentNumber);
    });

    // ========================================================================
    // LOAD STUDENT DETAILS
    // ========================================================================
    function loadStudentDetails(studentNumber) {
        $('#classesContainer').html(`
            <div class="text-center py-4 text-primary">
                <i class="fas fa-spinner fa-spin"></i> Loading classes...
            </div>
        `);
        $('#detailStudentName').text('Loading...');
        $('#detailStudentMeta').text('');
        $('#classesCount').text('0 Classes');

        const url = API_ROUTES.getStudentDetails.replace(':sn', encodeURIComponent(studentNumber));

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    renderStudentHeader(response.student);
                    renderClasses(response.classes, response.submitted_count, response.total_classes);
                } else {
                    $('#detailStudentName').text('Error');
                    showContainerError('#classesContainer', response.message || 'Failed to load details');
                }
            },
            error: function () {
                showContainerError('#classesContainer', 'Failed to load student details');
                Toast.fire({ icon: 'error', title: 'Could not load student details' });
            }
        });
    }

    // ========================================================================
    // RENDER STUDENT HEADER
    // ========================================================================
    function renderStudentHeader(student) {
        const fullName = `${student.last_name}, ${student.first_name}${student.middle_name ? ' ' + student.middle_name : ''}`;
        const meta     = [student.level_name, student.strand_name, student.section_name]
                           .filter(Boolean).join(' | ');

        $('#detailStudentName').html(`<i class="fas fa-user-graduate"></i> ${fullName} <small class="text-muted">(${student.student_number})</small>`);
        $('#detailStudentMeta').text(meta || '');
    }

    // ========================================================================
    // RENDER CLASSES
    // ========================================================================
    function renderClasses(classes, submittedCount, totalClasses) {
        const container = $('#classesContainer');
        container.empty();

        $('#classesCount').text(`${totalClasses} Class${totalClasses !== 1 ? 'es' : ''}`);

        if (!classes || classes.length === 0) {
            container.html(`
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p class="mb-0">No classes enrolled this semester</p>
                </div>
            `);
            return;
        }

        classes.forEach(function (cls) {
            const isSubmitted  = cls.is_submitted;
            const borderClass  = isSubmitted ? 'border-success' : 'border-danger';
            const statusText   = isSubmitted
                ? '<span class="text-success"><i class="fas fa-check-circle"></i> Submitted</span>'
                : '<span class="text-danger"><i class="fas fa-clock"></i> Pending</span>';

            const teachers = cls.teachers || '<span class="text-muted">No teacher assigned</span>';

            const gradeInfo = isSubmitted
                ? `<small class="text-muted d-block">Final Grade</small>
                   <strong>${cls.final_grade ?? '-'}</strong>
                   <small class="d-block text-muted">${cls.remarks ?? ''}</small>`
                : `<small class="text-muted">—</small>`;

            const submittedAt = isSubmitted && cls.submitted_at
                ? `<small class="text-muted d-block mt-1"><i class="fas fa-calendar-check"></i> ${new Date(cls.submitted_at).toLocaleDateString()}</small>`
                : '';

            container.append(`
                <div class="card class-card ${borderClass} mb-2">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                ${statusText}
                                ${submittedAt}
                            </div>
                            <div class="col-md-7">
                                <h6 class="mb-0"><strong>${cls.class_name}</strong></h6>
                                <small class="text-muted">
                                    <i class="fas fa-chalkboard-teacher"></i> ${teachers}
                                </small>
                            </div>
                            <div class="col-md-3 text-center">
                                ${gradeInfo}
                            </div>
                        </div>
                    </div>
                </div>
            `);
        });
    }

    // ========================================================================
    // FILTERS
    // ========================================================================
    $('#studentSearch').on('input', function () {
        renderStudentList(allStudents);
    });

    $('#submissionFilter').on('change', function () {
        renderStudentList(allStudents);
    });

    // ========================================================================
    // UTILITY
    // ========================================================================
    function showContainerLoader(selector) {
        $(selector).html(`
            <div class="text-center py-4">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-2 mb-0 small">Loading...</p>
            </div>
        `);
    }

    function showContainerError(selector, message) {
        $(selector).html(`
            <div class="text-center py-4 text-danger">
                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                <p class="mb-0 small">${message}</p>
            </div>
        `);
    }
});