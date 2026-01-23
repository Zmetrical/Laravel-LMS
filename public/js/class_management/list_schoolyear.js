$(document).ready(function () {
    let schoolYears = [];
    let selectedYearId = null;
    let currentSemesters = [];
    let isEditMode = false;
    let isSemesterEditMode = false;
    const MAX_SEMESTERS = window.MAX_SEMESTERS || 3;

    // Initialize
    loadSchoolYears();

    // Status Filter
    $('#statusFilter').change(function () {
        displaySchoolYears();
    });

    // Add School Year Button
    $('#addSchoolYearBtn').click(function () {
        isEditMode = false;
        $('#syModalTitle').text('Add School Year');
        $('#schoolYearId').val('');
        $('#yearStart').val('');
        $('#yearEnd').val('');
        $('#statusGroup').hide();
        $('#schoolYearModal').modal('show');
    });

    // Edit Year Button
    $('#editYearBtn').click(function () {
        if (selectedYearId) {
            editSchoolYear(selectedYearId);
        }
    });

    // Auto-fill end year
    $('#yearStart').on('input', function () {
        const startYear = parseInt($(this).val());
        if (!isNaN(startYear)) {
            $('#yearEnd').val(startYear + 1);
        }
    });

    // School Year Form Submit
    $('#schoolYearForm').submit(function (e) {
        e.preventDefault();

        const yearStart = parseInt($('#yearStart').val());
        const yearEnd = parseInt($('#yearEnd').val());

        if (yearEnd - yearStart !== 1) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Year Range',
                text: 'School year must span exactly one year (e.g., 2024-2025)'
            });
            return;
        }

        const formData = {
            year_start: yearStart,
            year_end: yearEnd
        };

        if (isEditMode) {
            formData.status = $('#status').val();
            updateSchoolYear(formData);
        } else {
            createSchoolYear(formData);
        }
    });

    // Activate Button
    $('#activateBtn').click(function () {
        if (selectedYearId) {
            activateSchoolYear(selectedYearId);
        }
    });

    // Archive Button
    $('#archiveBtn').click(function () {
        if (selectedYearId) {
            archiveSchoolYear(selectedYearId);
        }
    });

    // Add Semester Button
    $('#addSemesterBtn').click(function () {
        if (!selectedYearId) return;
        
        // Check if max semesters reached
        if (currentSemesters.length >= MAX_SEMESTERS) {
            Swal.fire({
                icon: 'warning',
                title: 'Maximum Semesters Reached',
                text: `Only ${MAX_SEMESTERS} semesters are allowed per school year.`
            });
            return;
        }
        
        isSemesterEditMode = false;
        $('#semModalTitle').text('Add Semester');
        $('#semesterId').val('');
        $('#semesterSchoolYearId').val(selectedYearId);
        $('#startDate').val('');
        $('#endDate').val('');
        $('#semStatusGroup').hide();
        $('#semesterModal').modal('show');
    });

    // View Semesters Button
    $('#viewSemestersBtn').click(function () {
        if (!selectedYearId) return;
        
        const year = schoolYears.find(sy => sy.id === selectedYearId);
        if (year) {
            window.location.href = API_ROUTES.semestersPage + '?sy=' + selectedYearId;
        }
    });

    // Semester Form Submit
    $('#semesterForm').submit(function (e) {
        e.preventDefault();

        const formData = {
            school_year_id: $('#semesterSchoolYearId').val(),
            start_date: $('#startDate').val(),
            end_date: $('#endDate').val()
        };

        if (isSemesterEditMode) {
            formData.status = $('#semStatus').val();
            updateSemester(formData);
        } else {
            createSemester(formData);
        }
    });

    // Load School Years
    function loadSchoolYears() {
        $('#loadingIndicator').show();
        $('#schoolYearsContainer').hide();
        $('#emptyYears').hide();

        $.ajax({
            url: API_ROUTES.getSchoolYears,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    schoolYears = response.data;
                    $('#loadingIndicator').hide();
                    displaySchoolYears();
                }
            },
            error: function (xhr) {
                console.error('Failed to load school years:', xhr);
                $('#loadingIndicator').hide();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load school years'
                });
            }
        });
    }

    // Display School Years
    function displaySchoolYears() {
        const statusFilter = $('#statusFilter').val();
        let filteredYears = schoolYears;

        if (statusFilter) {
            filteredYears = schoolYears.filter(sy => sy.status === statusFilter);
        }

        if (filteredYears.length === 0) {
            $('#schoolYearsContainer').hide();
            $('#emptyYears').show();
            return;
        }

        const container = $('#schoolYearsList');
        container.empty();

        filteredYears.forEach(sy => {
            const statusBadge = getStatusBadgeClass(sy.status);
            const isSelected = sy.id === selectedYearId ? 'active' : '';
            
            const item = `
                <a href="#" class="list-group-item list-group-item-action year-item ${isSelected}" 
                   data-year-id="${sy.id}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">
                                <i class="fas fa-calendar mr-2"></i>
                                SY ${sy.year_start}-${sy.year_end}
                            </h6>
                        </div>
                        <span class="badge ${statusBadge}">${sy.status.toUpperCase()}</span>
                    </div>
                </a>
            `;
            container.append(item);
        });

        $('#schoolYearsContainer').show();
        $('#emptyYears').hide();

        // Click handler
        container.find('.year-item').click(function (e) {
            e.preventDefault();
            const yearId = $(this).data('year-id');
            selectYear(yearId);
        });

        // Auto-select active year if exists and no selection
        if (!selectedYearId) {
            const activeYear = filteredYears.find(sy => sy.status === 'active');
            if (activeYear) {
                selectYear(activeYear.id);
            }
        }
    }

    // Select Year
    function selectYear(yearId) {
        selectedYearId = yearId;
        const year = schoolYears.find(sy => sy.id === yearId);
        
        if (!year) return;

        // Update selection in lists
        $('.year-item').removeClass('active');
        $(`.year-item[data-year-id="${yearId}"]`).addClass('active');

        // Show details
        $('#emptyState').hide();
        $('#detailsContent').show();
        $('#detailsTools').show();

        // Update details
        $('#detailsTitle').text(`School Year ${year.year_start}-${year.year_end}`);
        
        // Show/hide action buttons
        $('#activateBtn').hide();
        $('#archiveBtn').hide();
        
        if (year.status === 'upcoming') {
            $('#activateBtn').show();
        } else if (year.status === 'active') {
            $('#archiveBtn').show();
        }

        // Load semesters
        loadSemesters(yearId);
    }

    // Load Semesters
    function loadSemesters(yearId) {
        $('#semestersLoading').show();
        $('#semestersTableContainer').hide();
        $('#noSemesters').hide();

        $.ajax({
            url: API_ROUTES.getSemesters,
            method: 'GET',
            data: { school_year_id: yearId },
            success: function (response) {
                $('#semestersLoading').hide();
                
                if (response.success && response.data.length > 0) {
                    currentSemesters = response.data;
                    displaySemesters(response.data);
                    $('#semestersTableContainer').show();
                    
                    // Update Add button state
                    if (currentSemesters.length >= MAX_SEMESTERS) {
                        $('#addSemesterBtn').prop('disabled', true)
                            .attr('title', `Maximum of ${MAX_SEMESTERS} semesters reached`);
                    } else {
                        $('#addSemesterBtn').prop('disabled', false)
                            .attr('title', 'Add Semester');
                    }
                } else {
                    currentSemesters = [];
                    $('#noSemesters').show();
                    $('#addSemesterBtn').prop('disabled', false);
                }
            },
            error: function () {
                $('#semestersLoading').hide();
                $('#noSemesters').show();
            }
        });
    }

    // Display Semesters
    function displaySemesters(semesters) {
        const tbody = $('#semestersTableBody');
        tbody.empty();

        semesters.forEach(sem => {
            const statusBadge = sem.status === 'active' ? 'badge-primary' :
                              sem.status === 'upcoming' ? 'badge-secondary' : 'badge-dark';
            
            const row = `
                <tr>
                    <td><strong>${sem.name}</strong></td>
                    <td><small>${formatDate(sem.start_date)} - ${formatDate(sem.end_date)}</small></td>
                    <td><span class="badge ${statusBadge}">${sem.status.toUpperCase()}</span></td>
                    <td>
                        <button class="btn btn-sm btn-secondary edit-semester-btn" data-semester-id="${sem.id}" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        ${sem.status !== 'active' ? `
                        <button class="btn btn-sm btn-primary activate-semester-btn" data-semester-id="${sem.id}" 
                                data-semester-name="${sem.name}" title="Set Active">
                            <i class="fas fa-check"></i>
                        </button>
                        ` : ''}
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        // Action handlers
        $('.edit-semester-btn').click(function () {
            const semId = $(this).data('semester-id');
            editSemester(semId);
        });

        $('.activate-semester-btn').click(function () {
            const semId = $(this).data('semester-id');
            const semName = $(this).data('semester-name');
            activateSemester(semId, semName);
        });
    }

    // Edit Semester
    function editSemester(semesterId) {
        const semester = currentSemesters.find(s => s.id === semesterId);
        if (!semester) return;

        isSemesterEditMode = true;
        $('#semModalTitle').text('Edit Semester - ' + semester.name);
        $('#semesterId').val(semester.id);
        $('#semesterSchoolYearId').val(semester.school_year_id);
        $('#startDate').val(semester.start_date);
        $('#endDate').val(semester.end_date);
        $('#semStatus').val(semester.status);
        $('#semStatusGroup').show();
        $('#semesterModal').modal('show');
    }

    // Activate Semester
    function activateSemester(semesterId, semesterName) {
        Swal.fire({
            title: 'Activate Semester?',
            html: `Set <strong>${semesterName}</strong> as active?<br><small class="text-muted">This will deactivate all other semesters</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check"></i> Yes, activate',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.setActiveSemester.replace(':id', semesterId);

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
                            loadSemesters(selectedYearId);
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
    }

    // Get Status Badge Class
    function getStatusBadgeClass(status) {
        return {
            'active': 'badge-primary',
            'completed': 'badge-dark',
            'upcoming': 'badge-secondary'
        }[status] || 'badge-light';
    }

    // Format Date
    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    // Create School Year
    function createSchoolYear(formData) {
        $.ajax({
            url: API_ROUTES.createSchoolYear,
            method: 'POST',
            data: formData,
            headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
            success: function (response) {
                if (response.success) {
                    $('#schoolYearModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
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
                    text: xhr.responseJSON?.message || 'Failed to create school year'
                });
            }
        });
    }

    // Edit School Year
    function editSchoolYear(id) {
        const sy = schoolYears.find(item => item.id === id);
        if (sy) {
            isEditMode = true;
            $('#syModalTitle').text('Edit School Year');
            $('#schoolYearId').val(sy.id);
            $('#yearStart').val(sy.year_start);
            $('#yearEnd').val(sy.year_end);
            $('#status').val(sy.status);
            $('#statusGroup').show();
            $('#schoolYearModal').modal('show');
        }
    }

    // Update School Year
    function updateSchoolYear(formData) {
        const id = $('#schoolYearId').val();
        const url = API_ROUTES.updateSchoolYear.replace(':id', id);

        $.ajax({
            url: url,
            method: 'PUT',
            data: formData,
            headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
            success: function (response) {
                if (response.success) {
                    $('#schoolYearModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
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
                    text: xhr.responseJSON?.message || 'Failed to update school year'
                });
            }
        });
    }

    // Activate School Year
    function activateSchoolYear(id) {
        const year = schoolYears.find(sy => sy.id === id);
        
        Swal.fire({
            title: 'Activate School Year?',
            html: `Set <strong>SY ${year.year_start}-${year.year_end}</strong> as active?<br><small class="text-muted">This will deactivate all other school years</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check"></i> Yes, activate',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.setActive.replace(':id', id);

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
                            loadSchoolYears();
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to activate school year'
                        });
                    }
                });
            }
        });
    }

    // Archive School Year
    function archiveSchoolYear(id) {
        const year = schoolYears.find(sy => sy.id === id);
        
        Swal.fire({
            title: 'Archive School Year?',
            html: `Archive <strong>SY ${year.year_start}-${year.year_end}</strong>?<br><small class="text-muted">This will mark it as completed</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-archive"></i> Yes, archive',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.updateSchoolYear.replace(':id', id);

                $.ajax({
                    url: url,
                    method: 'PUT',
                    data: {
                        year_start: year.year_start,
                        year_end: year.year_end,
                        status: 'completed'
                    },
                    headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Archived',
                                text: 'School year archived successfully',
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
                            text: 'Failed to archive school year'
                        });
                    }
                });
            }
        });
    }

    // Create Semester
    function createSemester(formData) {
        $.ajax({
            url: API_ROUTES.createSemester,
            method: 'POST',
            data: formData,
            headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
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
                    loadSemesters(selectedYearId);
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Failed to create semester'
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
            headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
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
                    loadSemesters(selectedYearId);
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Failed to update semester'
                });
            }
        });
    }
});