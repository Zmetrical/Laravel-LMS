$(document).ready(function () {
    let schoolYearData = null;
    let semestersData = [];

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
                        loadArchiveInfo();
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

    // Load Archive Information
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
                    displayArchiveInfo();
                }
            },
            error: function (xhr) {
                $('#contentLoading').hide();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load archive information'
                });
            }
        });
    }

    // Display Archive Information
    function displayArchiveInfo() {
        $('#contentLoading').hide();

        // Display School Year
        $('#syDisplay').text(`SY ${schoolYearData.year_start}-${schoolYearData.year_end}`);
        
        const statusBadge = getStatusBadgeClass(schoolYearData.status);
        $('#syStatusBadge').attr('class', `badge badge-lg ${statusBadge}`)
            .text(schoolYearData.status.toUpperCase());

        // Show archive button only for upcoming school years
        if (schoolYearData.status === 'upcoming') {
            $('#archiveSYBtn').show();
        }

        // Display Semesters
        if (semestersData.length > 0) {
            displaySemesters();
            $('#semestersContainer').show();
            $('#noSemesters').hide();
        } else {
            $('#semestersContainer').hide();
            $('#noSemesters').show();
        }

        $('#mainContent').show();
    }

    // Display Semesters
    function displaySemesters() {
        const container = $('#semestersContainer');
        container.empty();

        semestersData.forEach(sem => {
            const statusBadge = getStatusBadgeClass(sem.status);
            const canArchive = sem.status === 'upcoming';
            const canActivate = sem.status === 'upcoming';

            const card = `
                <div class="col-md-6 mb-3">
                    <div class="card semester-card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-calendar"></i> ${sem.name}
                                </h5>
                                <span class="badge ${statusBadge}">${sem.status.toUpperCase()}</span>
                            </div>
                            <small class="text-muted">${formatDate(sem.start_date)} - ${formatDate(sem.end_date)}</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="stat-box">
                                        <div class="stat-number">${sem.enrolled_students}</div>
                                        <div class="stat-label">Enrolled Students</div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="stat-box">
                                        <div class="stat-number">${sem.sections_count}</div>
                                        <div class="stat-label">Active Sections</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-box">
                                        <div class="stat-number">${sem.quarter_grades}</div>
                                        <div class="stat-label">Quarter Grades</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-box">
                                        <div class="stat-number">${sem.final_grades}</div>
                                        <div class="stat-label">Final Grades</div>
                                    </div>
                                </div>
                            </div>

                            ${canActivate || canArchive ? `
                                <hr>
                                <div class="text-right">
                                    ${canActivate ? `
                                        <button class="btn btn-sm btn-primary activate-sem-btn" 
                                                data-id="${sem.id}"
                                                data-name="${sem.name}">
                                            <i class="fas fa-check"></i> Set Active
                                        </button>
                                    ` : ''}
                                    ${canArchive ? `
                                        <button class="btn btn-sm btn-secondary archive-sem-btn" 
                                                data-id="${sem.id}"
                                                data-name="${sem.name}">
                                            <i class="fas fa-archive"></i> Archive
                                        </button>
                                    ` : ''}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            container.append(card);
        });

        // Attach event handlers
        $('.activate-sem-btn').click(function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            activateSemester(id, name);
        });

        $('.archive-sem-btn').click(function () {
            const id = $(this).data('id');
            const name = $(this).data('name');
            archiveSemester(id, name);
        });
    }

    // Archive School Year Button
    $('#archiveSYBtn').click(function () {
        archiveSchoolYear();
    });

    // Archive School Year
    function archiveSchoolYear() {
        const totalStudents = semestersData.reduce((sum, sem) => sum + sem.enrolled_students, 0);
        const totalGrades = semestersData.reduce((sum, sem) => sum + sem.final_grades, 0);

        let impactHtml = `
            <div class="text-left">
                <p><strong>This will archive:</strong></p>
                <ul>
                    <li>School Year ${schoolYearData.year_start}-${schoolYearData.year_end}</li>
                    <li>All ${semestersData.length} semester(s)</li>
                    <li>Data for ${totalStudents} student(s)</li>
                    <li>${totalGrades} final grade record(s)</li>
                </ul>
                <p class="mb-0"><small class="text-muted">Archived data will be preserved but marked as completed.</small></p>
            </div>
        `;

        Swal.fire({
            title: 'Archive School Year?',
            html: impactHtml,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-archive"></i> Yes, archive',
            cancelButtonText: 'Cancel',
            width: '500px'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.archiveSchoolYear.replace(':id', SCHOOL_YEAR_ID);

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
                            }).then(() => {
                                window.location.href = '/admin/schoolyears';
                            });
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
    function activateSemester(id, name) {
        const semester = semestersData.find(s => s.id === id);

        let impactHtml = `
            <div class="text-left">
                <p>Set <strong>${name}</strong> as the active semester?</p>
                <div class="info-card p-3 mb-3">
                    <p class="mb-2"><strong>Current Data:</strong></p>
                    <ul class="mb-0">
                        <li>${semester.enrolled_students} enrolled student(s)</li>
                        <li>${semester.sections_count} active section(s)</li>
                    </ul>
                </div>
                <p class="mb-0"><small class="text-muted">This will deactivate all other semesters.</small></p>
            </div>
        `;

        Swal.fire({
            title: 'Activate Semester?',
            html: impactHtml,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check"></i> Yes, activate',
            cancelButtonText: 'Cancel',
            width: '500px'
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
                            loadArchiveInfo();
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
    function archiveSemester(id, name) {
        const semester = semestersData.find(s => s.id === id);

        let impactHtml = `
            <div class="text-left">
                <p>Archive <strong>${name}</strong>?</p>
                <div class="info-card p-3 mb-3">
                    <p class="mb-2"><strong>Data to be archived:</strong></p>
                    <ul class="mb-0">
                        <li>${semester.enrolled_students} student enrollment(s)</li>
                        <li>${semester.quarter_grades} quarter grade(s)</li>
                        <li>${semester.final_grades} final grade(s)</li>
                    </ul>
                </div>
                <p class="mb-0"><small class="text-muted">Archived data will be preserved but marked as completed.</small></p>
            </div>
        `;

        Swal.fire({
            title: 'Archive Semester?',
            html: impactHtml,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-archive"></i> Yes, archive',
            cancelButtonText: 'Cancel',
            width: '500px'
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
                            loadArchiveInfo();
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

    // Helper Functions
    function getStatusBadgeClass(status) {
        return {
            'active': 'badge-primary',
            'completed': 'badge-dark',
            'upcoming': 'badge-secondary'
        }[status] || 'badge-light';
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
});