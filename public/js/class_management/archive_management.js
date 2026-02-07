$(document).ready(function () {
    let schoolYears = [];
    let semesters = [];

    // Verification Form Submit
    $('#verificationForm').submit(function (e) {
        e.preventDefault();

        const password = $('#adminPassword').val();

        if (!password) {
            Swal.fire({
                icon: 'warning',
                title: 'Required',
                text: 'Please enter your admin password'
            });
            return;
        }

        verifyAccess(password);
    });

    // Verify Access
    function verifyAccess(password) {
        $.ajax({
            url: API_ROUTES.verifyAccess,
            method: 'POST',
            data: { admin_password: password },
            headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
            success: function (response) {
                if (response.success) {
                    $('#verificationCard').fadeOut(300, function () {
                        $('#archiveContent').fadeIn(300);
                        loadData();
                    });

                    Swal.fire({
                        icon: 'success',
                        title: 'Access Granted',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
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
    }

    // Load Data
    function loadData() {
        loadSchoolYears();
        loadSemesters();
    }

    // Load School Years
    function loadSchoolYears() {
        $('#syLoading').show();
        $('#syTableContainer').hide();
        $('#syEmpty').hide();

        $.ajax({
            url: API_ROUTES.getSchoolYears,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    schoolYears = response.data.filter(sy => 
                        sy.status === 'upcoming' || sy.status === 'completed'
                    );
                    displaySchoolYears();
                }
            },
            error: function () {
                $('#syLoading').hide();
                $('#syEmpty').show();
            }
        });
    }

    // Display School Years
    function displaySchoolYears() {
        $('#syLoading').hide();

        if (schoolYears.length === 0) {
            $('#syEmpty').show();
            return;
        }

        const tbody = $('#syTableBody');
        tbody.empty();

        schoolYears.forEach(sy => {
            const statusBadge = sy.status === 'upcoming' ? 'badge-secondary' : 'badge-dark';
            const canArchive = sy.status === 'upcoming';

            const row = `
                <tr>
                    <td><strong>SY ${sy.year_start}-${sy.year_end}</strong></td>
                    <td><span class="badge ${statusBadge}">${sy.status.toUpperCase()}</span></td>
                    <td>
                        ${canArchive ? `
                        <button class="btn btn-sm btn-secondary archive-sy-btn" 
                                data-id="${sy.id}"
                                data-code="SY ${sy.year_start}-${sy.year_end}"
                                title="Archive">
                            <i class="fas fa-archive"></i>
                        </button>
                        ` : `
                        <span class="text-muted"><small>Archived</small></span>
                        `}
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        $('#syTableContainer').show();

        // Archive button handler
        $('.archive-sy-btn').click(function () {
            const id = $(this).data('id');
            const code = $(this).data('code');
            archiveSchoolYear(id, code);
        });
    }

    // Load Semesters
    function loadSemesters() {
        $('#semLoading').show();
        $('#semTableContainer').hide();
        $('#semEmpty').hide();

        $.ajax({
            url: API_ROUTES.getSemesters,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    semesters = response.data.filter(sem => 
                        sem.status === 'upcoming' || sem.status === 'completed' || sem.status === 'active'
                    );
                    displaySemesters();
                }
            },
            error: function () {
                $('#semLoading').hide();
                $('#semEmpty').show();
            }
        });
    }

    // Display Semesters
    function displaySemesters() {
        $('#semLoading').hide();

        if (semesters.length === 0) {
            $('#semEmpty').show();
            return;
        }

        const tbody = $('#semTableBody');
        tbody.empty();

        semesters.forEach(sem => {
            const statusBadge = sem.status === 'upcoming' ? 'badge-secondary' : 
                              sem.status === 'active' ? 'badge-primary' : 'badge-dark';
            const canArchive = sem.status === 'upcoming';
            const canActivate = sem.status === 'upcoming';

            const row = `
                <tr>
                    <td><strong>${sem.name}</strong></td>
                    <td>SY ${sem.year_start}-${sem.year_end}</td>
                    <td><span class="badge ${statusBadge}">${sem.status.toUpperCase()}</span></td>
                    <td>
                        ${canActivate ? `
                        <button class="btn btn-sm btn-primary activate-sem-btn" 
                                data-id="${sem.id}"
                                data-name="${sem.name}"
                                data-sy="SY ${sem.year_start}-${sem.year_end}"
                                title="Set Active">
                            <i class="fas fa-check"></i>
                        </button>
                        ` : ''}
                        ${canArchive ? `
                        <button class="btn btn-sm btn-secondary archive-sem-btn" 
                                data-id="${sem.id}"
                                data-name="${sem.name}"
                                data-sy="SY ${sem.year_start}-${sem.year_end}"
                                title="Archive">
                            <i class="fas fa-archive"></i>
                        </button>
                        ` : ''}
                        ${!canArchive && !canActivate ? `
                        <span class="text-muted"><small>Archived</small></span>
                        ` : ''}
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        $('#semTableContainer').show();

        // Activate button handler
        $('.activate-sem-btn').click(function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const sy = $(this).data('sy');
            activateSemester(id, name, sy);
        });

        // Archive button handler
        $('.archive-sem-btn').click(function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const sy = $(this).data('sy');
            archiveSemester(id, name, sy);
        });
    }

    // Archive School Year
    function archiveSchoolYear(id, code) {
        Swal.fire({
            title: 'Archive School Year?',
            html: `Archive <strong>${code}</strong>?<br><small class="text-muted">This will mark it as completed</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-archive"></i> Yes, archive',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.archiveSchoolYear.replace(':id', id);

                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Archived',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadSchoolYears();
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to archive school year'
                        });
                    }
                });
            }
        });
    }

    // Activate Semester
    function activateSemester(id, name, sy) {
        Swal.fire({
            title: 'Activate Semester?',
            html: `Set <strong>${name}</strong> for <strong>${sy}</strong> as active?<br><small class="text-muted">This will deactivate all other semesters</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check"></i> Yes, activate',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.activateSemester.replace(':id', id);

                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Activated',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadSemesters();
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to activate semester'
                        });
                    }
                });
            }
        });
    }

    // Archive Semester
    function archiveSemester(id, name, sy) {
        Swal.fire({
            title: 'Archive Semester?',
            html: `Archive <strong>${name}</strong> for <strong>${sy}</strong>?<br><small class="text-muted">This will mark it as completed</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-archive"></i> Yes, archive',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.archiveSemester.replace(':id', id);

                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Archived',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadSemesters();
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to archive semester'
                        });
                    }
                });
            }
        });
    }
});