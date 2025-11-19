$(document).ready(function () {
    let schoolYears = [];
    let selectedYearId = null;
    let isEditMode = false;

    // Initialize
    loadSchoolYears();

    // Add School Year Button
    $('#addSchoolYearBtn').click(function () {
        isEditMode = false;
        $('#modalTitle').text('Add School Year');
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

    // Form Submit
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

    // Load School Years
    function loadSchoolYears() {
        $('#loadingIndicator').show();
        $('#statusTabContent').hide();

        $.ajax({
            url: API_ROUTES.getSchoolYears,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    schoolYears = response.data;
                    displaySchoolYears();
                    $('#loadingIndicator').hide();
                    $('#statusTabContent').show();
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
        const activeYears = schoolYears.filter(sy => sy.status === 'active');
        const upcomingYears = schoolYears.filter(sy => sy.status === 'upcoming');
        const archivedYears = schoolYears.filter(sy => sy.status === 'completed');

        renderYearsList(activeYears, '#activeYearsList', 'active');
        renderYearsList(upcomingYears, '#upcomingYearsList', 'upcoming');
        renderYearsList(archivedYears, '#archivedYearsList', 'archived');

        // Auto-select active year if exists
        if (activeYears.length > 0 && !selectedYearId) {
            selectYear(activeYears[0].id);
        }
    }

    // Render Years List
    function renderYearsList(years, containerId, type) {
        const container = $(containerId);
        container.empty();

        if (years.length === 0) {
            const emptyMsg = type === 'active' ? 'No active school year' :
                           type === 'upcoming' ? 'No upcoming school years' :
                           'No archived school years';
            container.append(`
                <div class="text-center text-muted py-4">
                    <i class="fas fa-info-circle"></i>
                    <p class="mb-0 mt-2">${emptyMsg}</p>
                </div>
            `);
            return;
        }

        years.forEach(sy => {
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
                            <small class="text-muted">${sy.code}</small>
                        </div>
                        <span class="badge ${statusBadge}">${sy.status.toUpperCase()}</span>
                    </div>
                </a>
            `;
            container.append(item);
        });

        // Click handler
        container.find('.year-item').click(function (e) {
            e.preventDefault();
            const yearId = $(this).data('year-id');
            selectYear(yearId);
        });
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
        $('#detailsTitle').html(`<i class="fas fa-info-circle"></i> SY ${year.year_start}-${year.year_end}`);
        $('#yearDisplay').text(`${year.year_start} - ${year.year_end}`);
        $('#codeDisplay').text(year.code);
        
        const badgeClass = getStatusBadgeClass(year.status);
        $('#statusBadge').attr('class', `badge badge-lg ${badgeClass}`)
                         .text(year.status.toUpperCase());

        // Show/hide action buttons
        $('#activateBtn').hide();
        $('#archiveBtn').hide();
        
        if (year.status === 'upcoming') {
            $('#activateBtn').show();
        } else if (year.status === 'active') {
            $('#archiveBtn').show();
        }

        // Update manage semesters link
        $('#manageSemestersBtn').attr('href', `${API_ROUTES.semestersPage}?sy=${year.id}`);

        // Load semesters
        loadSemesters(yearId);
    }

    // Load Semesters
    function loadSemesters(yearId) {
        $('#semestersLoading').show();
        $('#semestersList').hide();
        $('#noSemesters').hide();

        $.ajax({
            url: API_ROUTES.getSemesters,
            method: 'GET',
            data: { school_year_id: yearId },
            success: function (response) {
                $('#semestersLoading').hide();
                
                if (response.success && response.data.length > 0) {
                    displaySemesters(response.data);
                    $('#semestersList').show();
                } else {
                    $('#noSemesters').show();
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
        const container = $('#semestersContainer');
        container.empty();

        semesters.forEach(sem => {
            const statusBadge = sem.status === 'active' ? 'badge-success' :
                              sem.status === 'upcoming' ? 'badge-info' : 'badge-secondary';
            
            const item = `
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">${sem.name}</h6>
                            <small class="text-muted">${sem.code}</small>
                        </div>
                        <span class="badge ${statusBadge}">${sem.status}</span>
                    </div>
                </div>
            `;
            container.append(item);
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

    // Create School Year
    function createSchoolYear(formData) {
        $.ajax({
            url: API_ROUTES.createSchoolYear,
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': API_ROUTES.csrfToken
            },
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
                let errorMsg = 'Failed to create school year';
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

    // Edit School Year
    function editSchoolYear(id) {
        const sy = schoolYears.find(item => item.id === id);
        if (sy) {
            isEditMode = true;
            $('#modalTitle').text('Edit School Year');
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
            headers: {
                'X-CSRF-TOKEN': API_ROUTES.csrfToken
            },
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
                let errorMsg = 'Failed to update school year';
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

    // Activate School Year
    function activateSchoolYear(id) {
        const year = schoolYears.find(sy => sy.id === id);
        
        Swal.fire({
            title: 'Activate School Year?',
            html: `Set <strong>SY ${year.year_start}-${year.year_end}</strong> as the active school year?<br><small class="text-muted">This will deactivate all other school years</small>`,
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
            html: `Archive <strong>SY ${year.year_start}-${year.year_end}</strong>?<br><small class="text-muted">This will mark the school year as completed</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-archive"></i> Yes, archive it',
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
                    headers: {
                        'X-CSRF-TOKEN': API_ROUTES.csrfToken
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Archived',
                                text: 'School year has been archived',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // Switch to archived tab
                            $('#archived-tab').tab('show');
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
});