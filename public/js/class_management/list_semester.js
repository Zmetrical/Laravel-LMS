$(document).ready(function () {
    let currentSchoolYear = null;
    let semesters = [];
    let selectedSemesterId = null;
    let isEditMode = false;

    // Check if school year ID is provided
    if (!SCHOOL_YEAR_ID) {
        Swal.fire({
            icon: 'error',
            title: 'No School Year Selected',
            text: 'Please select a school year first',
            confirmButtonText: 'Go Back'
        }).then(() => {
            window.location.href = '/admin/schoolyears';
        });
        return;
    }

    // Initialize
    loadSchoolYear();
    loadSemesters();

    // Add Semester Buttons
    $('#addSemesterBtn, #addFirstSemesterBtn').click(function () {
        openSemesterModal();
    });

    // Edit Semester Button
    $('#editSemesterBtn').click(function () {
        if (selectedSemesterId) {
            openSemesterModal(selectedSemesterId);
        }
    });

    // Activate Semester Button
    $('#activateSemBtn').click(function () {
        if (selectedSemesterId) {
            activateSemester(selectedSemesterId);
        }
    });

    // Auto-generate semester code
    $('#semesterName').on('change', function () {
        const name = $(this).val();
        let code = '';
        
        if (name === '1st Semester') {
            code = 'SEM1';
        } else if (name === '2nd Semester') {
            code = 'SEM2';
        } else if (name === 'Summer') {
            code = 'SUMMER';
        }
        
        if (code && !isEditMode) {
            $('#semesterCode').val(code);
        }
    });

    // Form Submit
    $('#semesterForm').submit(function (e) {
        e.preventDefault();
        
        const startDate = new Date($('#startDate').val());
        const endDate = new Date($('#endDate').val());
        
        if (endDate <= startDate) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Date Range',
                text: 'End date must be after start date'
            });
            return;
        }

        const formData = {
            school_year_id: SCHOOL_YEAR_ID,
            name: $('#semesterName').val(),
            code: $('#semesterCode').val().toUpperCase(),
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val()
        };

        if (isEditMode) {
            formData.status = $('#status').val();
            updateSemester(formData);
        } else {
            createSemester(formData);
        }
    });

    // Load School Year
    function loadSchoolYear() {
        $('#schoolYearLoading').show();
        $('#schoolYearInfo').hide();

        $.ajax({
            url: API_ROUTES.getSchoolYear,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    currentSchoolYear = response.data.find(sy => sy.id == SCHOOL_YEAR_ID);
                    
                    if (!currentSchoolYear) {
                        Swal.fire({
                            icon: 'error',
                            title: 'School Year Not Found',
                            confirmButtonText: 'Go Back'
                        }).then(() => {
                            window.location.href = '/admin/schoolyears';
                        });
                        return;
                    }

                    displaySchoolYear();
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load school year'
                });
            }
        });
    }

    // Display School Year
    function displaySchoolYear() {
        $('#syDisplay').text(`SY ${currentSchoolYear.year_start}-${currentSchoolYear.year_end}`);
        $('#codeDisplay').text(currentSchoolYear.code);
        
        const badgeClass = getStatusBadgeClass(currentSchoolYear.status);
        $('#statusBadge').attr('class', `badge badge-lg ${badgeClass}`)
                        .text(currentSchoolYear.status.toUpperCase());

        $('#schoolYearLoading').hide();
        $('#schoolYearInfo').show();
        $('#addSemesterBtn').prop('disabled', false);
    }

    // Load Semesters
    function loadSemesters() {
        $('#semestersLoading').show();
        $('#semestersList').hide();
        $('#noSemesters').hide();

        $.ajax({
            url: API_ROUTES.getSemesters,
            method: 'GET',
            data: { school_year_id: SCHOOL_YEAR_ID },
            success: function (response) {
                $('#semestersLoading').hide();
                
                if (response.success && response.data.length > 0) {
                    semesters = response.data;
                    displaySemesters();
                    $('#semestersList').show();
                    
                    // Auto-select active semester or first
                    const activeSem = semesters.find(s => s.status === 'active');
                    if (activeSem) {
                        selectSemester(activeSem.id);
                    } else if (semesters.length > 0) {
                        selectSemester(semesters[0].id);
                    }
                } else {
                    $('#noSemesters').show();
                }
            },
            error: function (xhr) {
                $('#semestersLoading').hide();
                $('#noSemesters').show();
                console.error('Failed to load semesters:', xhr);
            }
        });
    }

    // Display Semesters
    function displaySemesters() {
        const container = $('#semestersList');
        container.empty();

        semesters.forEach(sem => {
            const statusBadge = getStatusBadgeClass(sem.status);
            const isSelected = sem.id === selectedSemesterId ? 'active' : '';
            
            const item = `
                <a href="#" class="list-group-item list-group-item-action semester-item ${isSelected}" 
                   data-semester-id="${sem.id}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">
                                <i class="fas fa-calendar mr-2"></i>
                                ${sem.name}
                            </h6>
                            <small class="text-muted">${sem.code}</small>
                        </div>
                        <span class="badge ${statusBadge}">${sem.status.toUpperCase()}</span>
                    </div>
                </a>
            `;
            container.append(item);
        });

        // Click handler
        container.find('.semester-item').click(function (e) {
            e.preventDefault();
            const semesterId = $(this).data('semester-id');
            selectSemester(semesterId);
        });
    }

    // Select Semester
    function selectSemester(semesterId) {
        selectedSemesterId = semesterId;
        const semester = semesters.find(s => s.id === semesterId);
        
        if (!semester) return;

        // Update selection in list
        $('.semester-item').removeClass('active');
        $(`.semester-item[data-semester-id="${semesterId}"]`).addClass('active');

        // Show details
        $('#emptyState').hide();
        $('#detailsContent').show();
        $('#detailsTools').show();

        // Update details
        $('#detailsTitle').html(`<i class="fas fa-info-circle"></i> ${semester.name}`);
        
        const startDate = new Date(semester.start_date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
        const endDate = new Date(semester.end_date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
        
        $('#durationDisplay').text(`${startDate} - ${endDate}`);
        $('#semCodeDisplay').text(semester.code);
        
        const badgeClass = getStatusBadgeClass(semester.status);
        $('#semStatusBadge').attr('class', `badge badge-lg ${badgeClass}`)
                           .text(semester.status.toUpperCase());

        // Show/hide activate button
        if (semester.status !== 'active') {
            $('#activateSemBtn').show();
        } else {
            $('#activateSemBtn').hide();
        }

        // Load classes for this semester
        loadSemesterClasses(semesterId);
    }

    // Load Semester Classes
    function loadSemesterClasses(semesterId) {
        $('#classesLoading').show();
        $('#classesTable').hide();
        $('#noClasses').hide();

        const url = API_ROUTES.getSemesterClasses.replace(':id', semesterId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                $('#classesLoading').hide();
                
                if (response.success && response.data.length > 0) {
                    displayClasses(response.data);
                    $('#classesTable').show();
                } else {
                    $('#noClasses').show();
                }
            },
            error: function () {
                $('#classesLoading').hide();
                $('#noClasses').show();
            }
        });
    }

    // Display Classes
    function displayClasses(classes) {
        const tbody = $('#classesTableBody');
        tbody.empty();

        classes.forEach(cls => {
            const row = `
                <tr>
                    <td><code>${cls.class_code}</code></td>
                    <td><strong>${cls.class_name}</strong></td>
                    <td class="text-center">
                        <span class="badge badge-primary">${cls.student_count || 0}</span>
                    </td>
                    <td class="text-center">
                        <button class="btn btn-info btn-sm" 
                                onclick="viewEnrollmentHistory('${cls.class_code}', '${cls.class_name}')"
                                title="View Enrollment History">
                            <i class="fas fa-history"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // View Enrollment History
    window.viewEnrollmentHistory = function(classCode, className) {
        $('#historyClassName').text(className);
        $('#enrollmentHistoryModal').modal('show');
        loadEnrollmentHistory(classCode);
    };

    // Load Enrollment History
    function loadEnrollmentHistory(classCode) {
        $('#historyLoading').show();
        $('#historyContent').hide();
        $('#noHistory').hide();

        const url = API_ROUTES.getEnrollmentHistory
            .replace(':semesterId', selectedSemesterId)
            .replace(':classCode', classCode);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                $('#historyLoading').hide();
                
                if (response.success && response.data.length > 0) {
                    displayEnrollmentHistory(response.data);
                    $('#historyContent').show();
                } else {
                    $('#noHistory').show();
                }
            },
            error: function () {
                $('#historyLoading').hide();
                $('#noHistory').show();
            }
        });
    }

    // Display Enrollment History
    function displayEnrollmentHistory(students) {
        const tbody = $('#historyTableBody');
        tbody.empty();

        students.forEach(student => {
            const statusBadge = student.enrollment_status === 'enrolled' ? 'badge-success' :
                              student.enrollment_status === 'dropped' ? 'badge-danger' : 'badge-secondary';
            
            const remarksBadge = student.remarks === 'PASSED' ? 'badge-success' :
                               student.remarks === 'FAILED' ? 'badge-danger' : 'badge-warning';

            const row = `
                <tr>
                    <td><code>${student.student_number}</code></td>
                    <td>${student.first_name} ${student.last_name}</td>
                    <td>${student.section_name || 'N/A'}</td>
                    <td>
                        <span class="badge ${statusBadge}">${student.enrollment_status}</span>
                    </td>
                    <td class="text-center">
                        ${student.final_grade ? `<strong>${student.final_grade}</strong>` : '-'}
                    </td>
                    <td>
                        ${student.remarks ? `<span class="badge ${remarksBadge}">${student.remarks}</span>` : '-'}
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Open Semester Modal
    function openSemesterModal(semesterId = null) {
        isEditMode = !!semesterId;
        
        if (isEditMode) {
            const semester = semesters.find(s => s.id === semesterId);
            if (!semester) return;

            $('#modalTitle').text('Edit Semester');
            $('#semesterId').val(semester.id);
            $('#semesterName').val(semester.name);
            $('#semesterCode').val(semester.code);
            $('#startDate').val(semester.start_date);
            $('#endDate').val(semester.end_date);
            $('#status').val(semester.status);
            $('#statusGroup').show();
        } else {
            $('#modalTitle').text('Add Semester');
            $('#semesterForm')[0].reset();
            $('#semesterId').val('');
            $('#statusGroup').hide();
        }

        $('#schoolYearId').val(SCHOOL_YEAR_ID);
        $('#semesterModal').modal('show');
    }

    // Create Semester
    function createSemester(formData) {
        $.ajax({
            url: API_ROUTES.createSemester,
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': API_ROUTES.csrfToken
            },
            success: function (response) {
                if (response.success) {
                    $('#semesterModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadSemesters();
                }
            },
            error: function (xhr) {
                let errorMsg = 'Failed to create semester';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
            }
        });
    }

    // Update Semester
    function updateSemester(formData) {
        const id = $('#semesterId').val();
        const url = API_ROUTES.updateSemester.replace(':id', id);

        $.ajax({
            url: url,
            method: 'PUT',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': API_ROUTES.csrfToken
            },
            success: function (response) {
                if (response.success) {
                    $('#semesterModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadSemesters();
                }
            },
            error: function (xhr) {
                let errorMsg = 'Failed to update semester';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
            }
        });
    }

    // Activate Semester
    function activateSemester(id) {
        const semester = semesters.find(s => s.id === id);
        
        Swal.fire({
            title: 'Activate Semester?',
            html: `Set <strong>${semester.name}</strong> as active?<br><small class="text-muted">This will deactivate all other semesters</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check"></i> Yes, activate it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.setActive.replace(':id', id);

                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': API_ROUTES.csrfToken
                    },
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
                    error: function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to activate semester'
                        });
                    }
                });
            }
        });
    }

    // Get Status Badge Class
    function getStatusBadgeClass(status) {
        const badges = {
            'active': 'badge-success',
            'completed': 'badge-secondary',
            'upcoming': 'badge-warning'
        };
        return badges[status] || 'badge-light';
    }
});