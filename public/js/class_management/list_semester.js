$(document).ready(function () {
    let isEditMode = false;
    let selectedSchoolYearId = null;
    let schoolYearsData = [];

    const urlParams = new URLSearchParams(window.location.search);
    const schoolYearIdFromUrl = urlParams.get('sy');
    // Load school years on page load
    loadSchoolYears();

    // Auto-generate code based on semester name
    $('#semesterName').change(function () {
        const name = $(this).val();
        const codeMap = {
            '1st Semester': 'SEM1',
            '2nd Semester': 'SEM2',
            'Summer': 'SUMMER'
        };
        $('#semesterCode').val(codeMap[name] || '');
    });

    // Add Semester Button
    $('#addSemesterBtn').click(function () {
        if (!selectedSchoolYearId) {
            Swal.fire({
                icon: 'warning',
                title: 'No School Year Selected',
                text: 'Please select a school year first'
            });
            return;
        }

        isEditMode = false;
        $('#modalTitle').text('Add Semester');
        $('#semesterId').val('');
        $('#schoolYearId').val(selectedSchoolYearId);
        $('#semesterName').val('');
        $('#semesterCode').val('');
        $('#startDate').val('');
        $('#endDate').val('');
        $('#statusGroup').hide();
        $('#semesterModal').modal('show');
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
            school_year_id: $('#schoolYearId').val(),
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

    // Load School Years
    function loadSchoolYears() {
        $.ajax({
            url: API_ROUTES.getSchoolYears,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    schoolYearsData = response.data;
                    displaySchoolYearsList(response.data);
                    updateSchoolYearHeader();
                }
            },
            error: function (xhr) {
                console.error('Failed to load school years:', xhr);
            }
        });
    }

    // Display School Years List (Left Panel)
    function displaySchoolYearsList(schoolYears) {
        const container = $('#schoolYearsListContainer');
        container.empty();

        if (schoolYears.length === 0) {
            container.html(`
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                    <p class="text-muted">No school years found</p>
                </div>
            `);
            return;
        }

        schoolYears.forEach(sy => {
            const isActive = sy.status === 'active';
            const statusBadge = getStatusBadge(sy.status);

            const card = `
                <div class="card mb-2 school-year-card ${isActive ? 'border-primary' : ''}" 
                     data-id="${sy.id}" style="cursor: pointer;">
                    <div class="card-body p-3">
                        <h6 class="mb-1">
                            ${isActive ? '<i class="fas fa-check-circle text-primary mr-1"></i>' : ''}
                            ${sy.year_start} - ${sy.year_end}
                        </h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt"></i> ${sy.semesters_count} semester(s)
                            </small>
                            ${statusBadge}
                        </div>
                    </div>
                </div>
            `;
            container.append(card);
        });

        // Click handler for school year cards
        $('.school-year-card').click(function () {
            selectSchoolYear($(this).data('id'));
        });

        // Auto-select school year from URL
        if (schoolYearIdFromUrl) {
            setTimeout(() => {
                selectSchoolYear(parseInt(schoolYearIdFromUrl));
            }, 100);
        }
    }
    // Select School Year
    function selectSchoolYear(schoolYearId) {
        $('.school-year-card').removeClass('border-primary bg-light');
        $(`.school-year-card[data-id="${schoolYearId}"]`).addClass('border-primary bg-light');

        selectedSchoolYearId = schoolYearId;
        $('#addSemesterBtn').prop('disabled', false);

        loadSemesters(selectedSchoolYearId);
        updateSelectedSchoolYearInfo();
    }
    // Update School Year Header
    function updateSchoolYearHeader() {
        const activeSY = schoolYearsData.find(sy => sy.status === 'active');

        if (activeSY) {
            $('#schoolYearHeader').html(`
                <h5><i class="icon fas fa-calendar-alt"></i> Active School Year: ${activeSY.year_start}-${activeSY.year_end}</h5>
                Manage semesters for the current and upcoming school years.
            `);
        } else {
            $('#schoolYearHeader').html(`
                <h5><i class="icon fas fa-exclamation-triangle"></i> No Active School Year</h5>
                Please set an active school year in the School Year Management page.
            `);
        }
    }

    // Update Selected School Year Info
    function updateSelectedSchoolYearInfo() {
        const sy = schoolYearsData.find(item => item.id === selectedSchoolYearId);
        if (sy) {
            $('#selectedSchoolYearInfo').html(`
                <h6 class="mb-1"><strong>School Year:</strong> ${sy.year_start} - ${sy.year_end}</h6>
                <p class="mb-0"><strong>Code:</strong> ${sy.code} | <strong>Status:</strong> ${getStatusBadge(sy.status)}</p>
            `);
        }
    }

    // Load Semesters
    function loadSemesters(schoolYearId) {
        $('#noSchoolYearSelected').hide();
        $('#semestersContainer').show();
        $('#semestersLoadingIndicator').show();
        $('#semestersTableContainer').hide();
        $('#noSemestersMessage').hide();

        $.ajax({
            url: API_ROUTES.getSemesters,
            method: 'GET',
            data: { school_year_id: schoolYearId },
            success: function (response) {
                if (response.success) {
                    displaySemesters(response.data);
                }
            },
            error: function (xhr) {
                console.error('Failed to load semesters:', xhr);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load semesters'
                });
            }
        });
    }

    // Display Semesters
    function displaySemesters(semesters) {
        $('#semestersLoadingIndicator').hide();

        if (semesters.length === 0) {
            $('#noSemestersMessage').show();
            return;
        }

        $('#semestersTableContainer').show();
        const tbody = $('#semestersTableBody');
        tbody.empty();

        semesters.forEach((sem, index) => {
            const statusBadge = getStatusBadge(sem.status);
            const activeBtn = sem.status !== 'active' ?
                `<button class="btn btn-success btn-xs" onclick="setActiveSemester(${sem.id})" title="Set as Active">
                    <i class="fas fa-check-circle"></i>
                </button>` : '';

            const startDate = new Date(sem.start_date).toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric'
            });
            const endDate = new Date(sem.end_date).toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric'
            });

            const row = `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${sem.name}</strong></td>
                    <td><span class="badge badge-secondary">${sem.code}</span></td>
                    <td><small>${startDate} - ${endDate}</small></td>
                    <td>${statusBadge}</td>
                    <td>
                        ${activeBtn}
                        <button class="btn btn-primary btn-xs" onclick="editSemester(${sem.id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Get Status Badge
    function getStatusBadge(status) {
        const badges = {
            'active': '<span class="badge badge-success">Active</span>',
            'completed': '<span class="badge badge-secondary">Completed</span>',
            'upcoming': '<span class="badge badge-warning">Upcoming</span>'
        };
        return badges[status] || '<span class="badge badge-light">Unknown</span>';
    }

    // Create Semester
    function createSemester(formData) {
        $.ajax({
            url: API_ROUTES.createSemester,
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    $('#semesterModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000
                    });
                    loadSemesters(selectedSchoolYearId);
                    loadSchoolYears(); // Refresh count
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

    // Edit Semester (Global Function)
    window.editSemester = function (id) {
        $.ajax({
            url: API_ROUTES.getSemesters,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    const sem = response.data.find(item => item.id === id);
                    if (sem) {
                        isEditMode = true;
                        $('#modalTitle').text('Edit Semester');
                        $('#semesterId').val(sem.id);
                        $('#schoolYearId').val(sem.school_year_id);
                        $('#semesterName').val(sem.name);
                        $('#semesterCode').val(sem.code);
                        $('#startDate').val(sem.start_date);
                        $('#endDate').val(sem.end_date);
                        $('#status').val(sem.status);
                        $('#statusGroup').show();
                        $('#semesterModal').modal('show');
                    }
                }
            }
        });
    };

    // Update Semester
    function updateSemester(formData) {
        const id = $('#semesterId').val();
        const url = API_ROUTES.updateSemester.replace(':id', id);

        $.ajax({
            url: url,
            method: 'PUT',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    $('#semesterModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000
                    });
                    loadSemesters(selectedSchoolYearId);
                    loadSchoolYears();
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

    // Set Active Semester (Global Function)
    window.setActiveSemester = function (id) {
        Swal.fire({
            title: 'Set as Active?',
            text: 'This will deactivate all other semesters and set the parent school year as active',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, activate it'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.setActive.replace(':id', id);

                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Activated',
                                text: response.message,
                                timer: 2000
                            });
                            loadSemesters(selectedSchoolYearId);
                            loadSchoolYears();
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to activate semester'
                        });
                    }
                });
            }
        });
    };
});