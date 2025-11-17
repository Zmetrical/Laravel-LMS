$(document).ready(function () {
    let isEditMode = false;

    // Load school years on page load
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

    // Auto-fill end year when start year is entered
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

        // Validate
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

    // Load School Years
    function loadSchoolYears() {
        $('#loadingIndicator').show();
        $('#tableContainer').hide();
        $('#noDataMessage').hide();

        $.ajax({
            url: API_ROUTES.getSchoolYears,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    displaySchoolYears(response.data);
                }
            },
            error: function (xhr) {
                console.error('Failed to load school years:', xhr);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load school years'
                });
            }
        });
    }

    // Display School Years
    function displaySchoolYears(schoolYears) {
        $('#loadingIndicator').hide();

        if (schoolYears.length === 0) {
            $('#noDataMessage').show();
            return;
        }

        $('#tableContainer').show();
        const tbody = $('#schoolYearsTableBody');
        tbody.empty();

        schoolYears.forEach((sy, index) => {
            const statusBadge = getStatusBadge(sy.status);
            const activeBtn = sy.status !== 'active' ?
                `<button class="btn btn-success btn-xs" onclick="setActive(${sy.id})" title="Set as Active">
                    <i class="fas fa-check-circle"></i>
                </button>` : '';

            const row = `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${sy.year_start} - ${sy.year_end}</strong></td>
                    <td><span class="badge badge-secondary">${sy.code}</span></td>
                    <td>${statusBadge}</td>
                    <td class="text-center">
                        <span class="badge badge-info">${sy.semesters_count}</span>
                    </td>
                    <td>
                        <a href="${API_ROUTES.semestersPage}?sy=${sy.id}" class="btn btn-info btn-xs" title="View Semesters">
                            <i class="fas fa-list"></i>
                        </a>
                        ${activeBtn}
                        <button class="btn btn-primary btn-xs" onclick="editSchoolYear(${sy.id})" title="Edit">
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

    // Create School Year
    function createSchoolYear(formData) {
        $.ajax({
            url: API_ROUTES.createSchoolYear,
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    $('#schoolYearModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000
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

    // Edit School Year (Global Function)
    window.editSchoolYear = function (id) {
        $.ajax({
            url: API_ROUTES.getSchoolYears,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    const sy = response.data.find(item => item.id === id);
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
            }
        });
    };

    // Update School Year
    function updateSchoolYear(formData) {
        const id = $('#schoolYearId').val();
        const url = API_ROUTES.updateSchoolYear.replace(':id', id);

        $.ajax({
            url: url,
            method: 'PUT',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    $('#schoolYearModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000
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

    // Set Active (Global Function)
    window.setActive = function (id) {
        Swal.fire({
            title: 'Set as Active?',
            text: 'This will deactivate all other school years',
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
    };
});