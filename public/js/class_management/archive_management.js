$(document).ready(function () {
    let schoolYearData = null;
    let semestersData = [];
    let selectedSemesterId = null;

    // Verify Form
    $('#verificationForm').submit(function (e) {
        e.preventDefault();
        const password = $('#adminPassword').val();
        if (!password) return;

        $.ajax({
            url: API_ROUTES.verifyAccess,
            method: 'POST',
            data: { admin_password: password },
            headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
            success: function (response) {
                if (response.success) {
                    $('#verificationCard').fadeOut(300, function () {
                        $('#archiveContent').fadeIn(300);
                        loadArchiveInfo();
                    });
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: xhr.responseJSON?.message || 'Invalid password'
                });
                $('#adminPassword').val('').focus();
            }
        });
    });

    // Load Archive Info
    function loadArchiveInfo() {
        $('#contentLoading').show();
        $('#mainContent').hide();

        const url = API_ROUTES.getArchiveInfo.replace(':id', SCHOOL_YEAR_ID);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    schoolYearData = response.data.school_year;
                    semestersData = response.data.semesters;
                    displaySchoolYear();
                    displaySemesters();
                    $('#contentLoading').hide();
                    $('#mainContent').show();
                }
            },
            error: function () {
                $('#contentLoading').hide();
                Swal.fire('Error', 'Failed to load data', 'error');
            }
        });
    }

    // Display School Year
    function displaySchoolYear() {
        $('#syDisplay').text(`SY ${schoolYearData.year_start}-${schoolYearData.year_end}`);
        
        const badgeClass = {
            'active': 'badge-primary',
            'completed': 'badge-dark',
            'upcoming': 'badge-secondary'
        }[schoolYearData.status] || 'badge-light';
        
        $('#syStatusBadge').attr('class', `badge badge-lg ${badgeClass}`)
            .text(schoolYearData.status.toUpperCase());

        if (schoolYearData.status === 'upcoming') {
            $('#archiveSYBtn').show();
        }
    }

    // Display Semesters
    function displaySemesters() {
        const container = $('#semestersList');
        container.empty();

        if (semestersData.length === 0) {
            $('#semestersLoading').hide();
            $('#noSemesters').show();
            return;
        }

        semestersData.forEach(sem => {
            const badgeClass = {
                'active': 'badge-primary',
                'completed': 'badge-dark',
                'upcoming': 'badge-secondary'
            }[sem.status] || 'badge-light';

            const item = `
                <a href="#" class="list-group-item list-group-item-action semester-item" data-id="${sem.id}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong>${sem.name}</strong>
                        <span class="badge ${badgeClass}">${sem.status.toUpperCase()}</span>
                    </div>
                    <small class="text-muted d-block">${sem.code}</small>
                    <small class="text-muted">${formatDate(sem.start_date)} - ${formatDate(sem.end_date)}</small>
                </a>
            `;
            container.append(item);
        });

        $('#semestersLoading').hide();
        $('#semestersList').show();

        // Click handler
        $('.semester-item').click(function (e) {
            e.preventDefault();
            const semId = $(this).data('id');
            selectSemester(semId);
        });
    }

    // Select Semester
    function selectSemester(semId) {
        selectedSemesterId = semId;
        
        $('.semester-item').removeClass('active');
        $(`.semester-item[data-id="${semId}"]`).addClass('active');

        $('#emptyState').hide();
        $('#detailsContent').hide();
        $('#detailsLoading').show();

        loadSemesterDetails(semId);
    }

    // Load Details
    function loadSemesterDetails(semId) {
        const url = API_ROUTES.getSemesterDetails.replace(':id', semId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    displayDetails(semId, response.data);
                }
            },
            error: function () {
                $('#detailsLoading').hide();
                Swal.fire('Error', 'Failed to load details', 'error');
            }
        });
    }

    // Display Details
    function displayDetails(semId, data) {
        const sem = semestersData.find(s => s.id === semId);

        // Stats
        $('#studentsCount').text(sem.enrolled_students);
        $('#sectionsCount').text(sem.sections_count);
        $('#teachersCount').text(sem.teachers_count);
        $('#gradesCount').text(sem.final_grades);

        // Sections Table
        const sectionsBody = $('#sectionsTableBody');
        sectionsBody.empty();
        if (data.sections.length > 0) {
            data.sections.forEach(sec => {
                sectionsBody.append(`
                    <tr>
                        <td>${sec.section_name}</td>
                        <td>${sec.level_name}</td>
                        <td>${sec.strand_code}</td>
                        <td class="text-right">${sec.student_count}</td>
                    </tr>
                `);
            });
        } else {
            sectionsBody.append('<tr><td colspan="4" class="text-center text-muted">No sections</td></tr>');
        }

        // Teachers Table
        const teachersBody = $('#teachersTableBody');
        teachersBody.empty();
        if (data.teachers.length > 0) {
            data.teachers.forEach(teacher => {
                const classes = teacher.classes.map(c => c.class_name).join(', ');
                teachersBody.append(`
                    <tr>
                        <td>${teacher.name}</td>
                        <td><small>${classes}</small></td>
                    </tr>
                `);
            });
        } else {
            teachersBody.append('<tr><td colspan="2" class="text-center text-muted">No teachers</td></tr>');
        }

        // Action Buttons
        const actionsDiv = $('#actionButtons');
        actionsDiv.empty();

        if (sem.status === 'upcoming') {
            actionsDiv.append(`
                <button class="btn btn-sm btn-primary" onclick="activateSemester(${semId}, '${sem.name}')">
                    <i class="fas fa-check"></i> Set Active
                </button>
                <button class="btn btn-sm btn-secondary ml-2" onclick="archiveSemester(${semId}, '${sem.name}')">
                    <i class="fas fa-archive"></i> Archive
                </button>
            `);
        }

        $('#detailsLoading').hide();
        $('#detailsContent').show();
    }

    // Archive School Year
    $('#archiveSYBtn').click(function () {
        const total = semestersData.reduce((sum, s) => sum + s.enrolled_students, 0);

        Swal.fire({
            title: 'Archive School Year?',
            html: `
                <p>This will archive:</p>
                <ul class="text-left">
                    <li>${semestersData.length} semester(s)</li>
                    <li>${total} student enrollment(s)</li>
                </ul>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Archive',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.archiveSchoolYear.replace(':id', SCHOOL_YEAR_ID);
                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Archived', response.message, 'success').then(() => {
                                window.location.href = '/admin/schoolyears';
                            });
                        }
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error');
                    }
                });
            }
        });
    });

    // Activate Semester
    window.activateSemester = function(id, name) {
        Swal.fire({
            title: `Activate ${name}?`,
            text: 'This will deactivate all other semesters',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Activate'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.activateSemester.replace(':id', id);
                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Activated', response.message, 'success');
                            loadArchiveInfo();
                        }
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error');
                    }
                });
            }
        });
    };

    // Archive Semester
    window.archiveSemester = function(id, name) {
        const sem = semestersData.find(s => s.id === id);

        Swal.fire({
            title: `Archive ${name}?`,
            html: `
                <ul class="text-left">
                    <li>${sem.enrolled_students} student(s)</li>
                    <li>${sem.final_grades} grade(s)</li>
                </ul>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Archive'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.archiveSemester.replace(':id', id);
                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire('Archived', response.message, 'success');
                            loadArchiveInfo();
                        }
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed', 'error');
                    }
                });
            }
        });
    };

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
});