$(document).ready(function () {
    let schoolYearData = null;
    let semestersData = [];
    let semesterDetailsCache = {};

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

        // Show warning if active
        if (schoolYearData.status === 'active') {
            $('#warningText').text('This is the active school year. To archive it, you must first activate a new school year.');
            $('#warningBox').show();
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
            const semId = `sem-${sem.id}`;

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
                            <!-- Details Section -->
                            <div id="${semId}-details">
                                <div class="text-center py-3">
                                    <i class="fas fa-spinner fa-spin"></i> Loading details...
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

        // Auto-load details for each semester
        semestersData.forEach(sem => {
            loadSemesterDetails(sem.id);
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

    // Load Semester Details
    function loadSemesterDetails(semesterId) {
        const url = API_ROUTES.getSemesterDetails.replace(':id', semesterId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    semesterDetailsCache[semesterId] = response.data;
                    displaySemesterDetails(semesterId, response.data);
                }
            },
            error: function () {
                $(`#sem-${semesterId}-details`).html(`
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-exclamation-triangle"></i> Failed to load details
                    </div>
                `);
            }
        });
    }

    // Display Semester Details
    function displaySemesterDetails(semesterId, data) {
        const detailsDiv = $(`#sem-${semesterId}-details`);
        
        let html = `
            <!-- Tabs -->
            <ul class="nav nav-pills nav-fill mb-3">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="pill" href="#sem-${semesterId}-sections">
                        <i class="fas fa-users"></i> Sections (${data.sections.length})
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="pill" href="#sem-${semesterId}-teachers">
                        <i class="fas fa-chalkboard-teacher"></i> Teachers (${data.teachers.length})
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Sections Tab -->
                <div class="tab-pane fade show active" id="sem-${semesterId}-sections">
                    <div class="tab-content-area">
        `;

        if (data.sections.length > 0) {
            data.sections.forEach(section => {
                html += `
                    <div class="section-item-archive">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <strong>${section.section_name}</strong>
                                <br>
                                <small class="text-muted">${section.level_name} - ${section.strand_code}</small>
                            </div>
                            <div class="text-right ml-3">
                                <span class="badge badge-primary mb-2">${section.student_count} student${section.student_count !== 1 ? 's' : ''}</span>
                                <br>
                                <a href="#" class="text-secondary view-students-link" 
                                   data-section-id="${section.id}" 
                                   data-semester-id="${semesterId}"
                                   data-section-name="${section.section_name}">
                                    <small><i class="fas fa-list"></i> View list</small>
                                </a>
                            </div>
                        </div>
                        <div class="student-list mt-2" id="students-${semesterId}-${section.id}" style="display: none;">
                            <div class="text-center py-2">
                                <i class="fas fa-spinner fa-spin"></i> Loading students...
                            </div>
                        </div>
                    </div>
                `;
            });
        } else {
            html += `<div class="text-center text-muted py-3">No sections found</div>`;
        }

        html += `
                    </div>
                </div>

                <!-- Teachers Tab -->
                <div class="tab-pane fade" id="sem-${semesterId}-teachers">
                    <div class="tab-content-area">
        `;

        if (data.teachers.length > 0) {
            data.teachers.forEach(teacher => {
                html += `
                    <div class="teacher-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong><i class="fas fa-user"></i> ${teacher.name}</strong>
                                <div class="mt-2">
                `;
                
                teacher.classes.forEach(cls => {
                    html += `<span class="class-tag">${cls.class_name}</span>`;
                });

                html += `
                                </div>
                            </div>
                            <span class="badge badge-secondary">${teacher.classes.length} ${teacher.classes.length === 1 ? 'class' : 'classes'}</span>
                        </div>
                    </div>
                `;
            });
        } else {
            html += `<div class="text-center text-muted py-3">No teachers assigned</div>`;
        }

        html += `
                    </div>
                </div>
            </div>
        `;

        detailsDiv.html(html);

        // Attach student view handlers
        $('.view-students-link').click(function (e) {
            e.preventDefault();
            const sectionId = $(this).data('section-id');
            const semesterId = $(this).data('semester-id');
            const sectionName = $(this).data('section-name');
            toggleStudentList(semesterId, sectionId, sectionName, $(this));
        });
    }

    // Toggle Student List
    function toggleStudentList(semesterId, sectionId, sectionName, linkElement) {
        const studentListDiv = $(`#students-${semesterId}-${sectionId}`);
        const isVisible = studentListDiv.is(':visible');

        if (isVisible) {
            studentListDiv.slideUp(300);
            linkElement.html('<small><i class="fas fa-list"></i> View list</small>');
            linkElement.closest('.section-item-archive').removeClass('expanded');
        } else {
            // Hide all other student lists in this semester
            $(`#sem-${semesterId}-sections .student-list`).slideUp(300);
            $(`#sem-${semesterId}-sections .view-students-link`).html('<small><i class="fas fa-list"></i> View list</small>');
            $(`#sem-${semesterId}-sections .section-item-archive`).removeClass('expanded');

            studentListDiv.slideDown(300);
            linkElement.html('<small><i class="fas fa-list-ul"></i> Hide list</small>');
            linkElement.closest('.section-item-archive').addClass('expanded');

            if (studentListDiv.find('.student-item').length === 0) {
                loadSectionStudents(semesterId, sectionId);
            }
        }
    }

    // Load Section Students
    function loadSectionStudents(semesterId, sectionId) {
        const url = API_ROUTES.getSectionStudents
            .replace(':semesterId', semesterId)
            .replace(':sectionId', sectionId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    displaySectionStudents(semesterId, sectionId, response.data);
                }
            },
            error: function () {
                $(`#students-${semesterId}-${sectionId}`).html(`
                    <div class="text-center text-muted py-2">
                        <i class="fas fa-exclamation-triangle"></i> Failed to load students
                    </div>
                `);
            }
        });
    }

    // Display Section Students
    function displaySectionStudents(semesterId, sectionId, students) {
        const container = $(`#students-${semesterId}-${sectionId}`);
        
        if (students.length === 0) {
            container.html(`
                <div class="text-center text-muted py-2">No students enrolled</div>
            `);
            return;
        }

        let html = '<div class="p-2">';
        students.forEach((student, index) => {
            const genderIcon = student.gender === 'Male' ? 'fa-mars text-primary' : 'fa-venus text-danger';
            html += `
                <div class="student-item">
                    <i class="fas ${genderIcon}"></i>
                    <strong>${student.student_number}</strong> - ${student.full_name}
                </div>
            `;
        });
        html += '</div>';

        container.html(html);
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
                        <li>${semester.teachers_count} teacher(s) assigned</li>
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