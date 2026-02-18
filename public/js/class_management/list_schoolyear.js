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

        const latestYear = getLatestSchoolYear();
        if (latestYear) {
            const startYear = parseInt(latestYear.year_end, 10);
            $('#yearStart').val(startYear);
            $('#yearEnd').val(startYear + 1);
        } else {
            $('#yearStart').val('');
            $('#yearEnd').val('');
        }

        $('#schoolYearModal').modal('show');
    });

    // Edit Year Button
    $('#editYearBtn').click(function () {
        if (selectedYearId) {
            editSchoolYear(selectedYearId);
        }
    });

    // Archive Management Button
    $('#archiveManagementBtn').click(function () {
        if (selectedYearId) {
            window.location.href = API_ROUTES.archiveManagementPage + '?sy=' + selectedYearId;
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select a school year first'
            });
        }
    });

    // View Semesters Button
    $('#viewSemestersBtn').click(function () {
        if (!selectedYearId) return;

        const year = schoolYears.find(sy => sy.id === selectedYearId);
        if (year) {
            window.location.href = API_ROUTES.semestersPage + '?sy=' + selectedYearId;
        }
    });

    // ── ADDED: Graduation Button ──────────────────────────────────────────────
    $('#graduationBtn').click(function () {
        if (!selectedYearId) return;
        window.location.href = API_ROUTES.graduationPage.replace(':id', selectedYearId);
    });
    // ─────────────────────────────────────────────────────────────────────────

    // Add Semester Button - SINGLE HANDLER
    $('#addSemesterBtn').off('click').on('click', function () {
        if (!selectedYearId) return;

        if (currentSemesters.length >= MAX_SEMESTERS) {
            Swal.fire({
                icon: 'warning',
                title: 'Maximum Semesters Reached',
                text: `Only ${MAX_SEMESTERS} semesters are allowed per school year.`
            });
            return;
        }

        const year = schoolYears.find(sy => sy.id === selectedYearId);

        isSemesterEditMode = false;
        $('#semModalTitle').text(`Add Semester - SY ${year.year_start}-${year.year_end}`);
        $('#semesterId').val('');
        $('#semesterSchoolYearId').val(selectedYearId);
        $('#startDate').val('');
        $('#endDate').val('');

        // Set min and max date attributes based on school year
        const minDate = `${year.year_start}-01-01`;
        const maxDate = `${year.year_end}-12-31`;
        $('#startDate').attr('min', minDate).attr('max', maxDate);
        $('#endDate').attr('min', minDate).attr('max', maxDate);

        // Show helper text
        if (!$('#semesterDateHelper').length) {
            $('#endDate').parent().after(`
                <small id="semesterDateHelper" class="form-text text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Dates must be between ${formatDate(minDate)} and ${formatDate(maxDate)}
                </small>
            `);
        } else {
            $('#semesterDateHelper').html(`
                <i class="fas fa-info-circle"></i> 
                Dates must be between ${formatDate(minDate)} and ${formatDate(maxDate)}
            `);
        }

        $('#semesterModal').modal('show');
    });

    // Year input validation
    $('#yearStart, #yearEnd').on('input keyup paste', function () {
        let value = $(this).val();
        value = value.replace(/\D/g, '');
        if (value.length > 4) {
            value = value.substring(0, 4);
        }
        if ($(this).val() !== value) {
            $(this).val(value);
        }
    });

    $('#yearStart, #yearEnd').on('keypress', function (e) {
        const char = String.fromCharCode(e.which);

        if (!/[0-9]/.test(char)) {
            e.preventDefault();
            return false;
        }

        const currentValue = $(this).val();
        const selectionStart = this.selectionStart;
        const selectionEnd = this.selectionEnd;

        if (selectionStart !== selectionEnd) {
            return true;
        }

        if (currentValue.length >= 4) {
            e.preventDefault();
            return false;
        }
    });

    $('#yearStart').on('blur', function () {
        let value = $(this).val().trim();

        if (value.length > 4) {
            value = value.substring(0, 4);
            $(this).val(value);
        }

        if (value.length === 4) {
            const startYear = parseInt(value, 10);
            if (!isNaN(startYear) && startYear >= 2000 && startYear <= 3000) {
                let endYear = startYear + 1;
                if (endYear > 3000) {
                    endYear = 3000;
                }
                $('#yearEnd').val(endYear.toString());
            } else if (startYear < 2000) {
                $(this).val('2000');
                $('#yearEnd').val('2001');
            } else if (startYear > 3000) {
                $(this).val('3000');
                $('#yearEnd').val('3000');
            }
        }
    });

    $('#yearEnd').on('blur', function () {
        let value = $(this).val().trim();

        if (value.length > 4) {
            value = value.substring(0, 4);
            $(this).val(value);
        }

        if (value.length === 4) {
            const endYear = parseInt(value, 10);
            if (!isNaN(endYear) && endYear >= 2000 && endYear <= 3000) {
                let startYear = endYear - 1;
                if (startYear < 2000) {
                    startYear = 2000;
                }
                $('#yearStart').val(startYear.toString());
            } else if (endYear < 2000) {
                $(this).val('2000');
                $('#yearStart').val('2000');
            } else if (endYear > 3000) {
                $(this).val('3000');
                $('#yearStart').val('2999');
            }
        }
    });

    // School Year Form Submit
    $('#schoolYearForm').off('submit').on('submit', function (e) {
        e.preventDefault();

        const yearStartStr = $('#yearStart').val();
        const yearEndStr = $('#yearEnd').val();

        if (yearStartStr.length !== 4 || yearEndStr.length !== 4) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Year Format',
                text: 'Year must be exactly 4 digits'
            });
            return;
        }

        const yearStart = parseInt(yearStartStr);
        const yearEnd = parseInt(yearEndStr);

        if (yearStart < 2000 || yearStart > 3000 || yearEnd < 2000 || yearEnd > 3000) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Year Range',
                text: 'Year must be between 2000 and 3000'
            });
            return;
        }

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
            updateSchoolYear(formData);
        } else {
            createSchoolYear(formData);
        }
    });

    // Semester Form Submit - SINGLE HANDLER
    $('#semesterForm').off('submit').on('submit', function (e) {
        e.preventDefault();

        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        const schoolYearId = $('#semesterSchoolYearId').val();

        // Get school year bounds
        const year = schoolYears.find(sy => sy.id == schoolYearId);
        if (year) {
            const minDate = `${year.year_start}-01-01`;
            const maxDate = `${year.year_end}-12-31`;

            if (startDate < minDate || startDate > maxDate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Start Date',
                    html: `Start date must be between <br><strong>${formatDate(minDate)}</strong> and <strong>${formatDate(maxDate)}</strong>`
                });
                return;
            }

            if (endDate < minDate || endDate > maxDate) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid End Date',
                    html: `End date must be between <br><strong>${formatDate(minDate)}</strong> and <strong>${formatDate(maxDate)}</strong>`
                });
                return;
            }
        }

        const formData = {
            school_year_id: schoolYearId,
            start_date: startDate,
            end_date: endDate
        };

        if (isSemesterEditMode) {
            updateSemester(formData);
        } else {
            createSemester(formData);
        }
    });

    // Clean up helper text when modal closes
    $('#semesterModal').on('hidden.bs.modal', function () {
        $('#semesterDateHelper').remove();
        $('#startDate').removeAttr('min').removeAttr('max');
        $('#endDate').removeAttr('min').removeAttr('max');
    });

    // Helper Functions
    function getLatestSchoolYear() {
        if (schoolYears.length === 0) return null;

        return schoolYears.reduce((latest, current) => {
            if (!latest) return current;
            return current.year_end > latest.year_end ? current : latest;
        }, null);
    }

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

        // Use event delegation to prevent multiple bindings
        container.off('click', '.year-item').on('click', '.year-item', function (e) {
            e.preventDefault();
            const yearId = $(this).data('year-id');
            selectYear(yearId);
        });

        if (!selectedYearId) {
            const activeYear = filteredYears.find(sy => sy.status === 'active');
            if (activeYear) {
                selectYear(activeYear.id);
            }
        }
    }

    function selectYear(yearId) {
        selectedYearId = yearId;
        const year = schoolYears.find(sy => sy.id === yearId);

        if (!year) return;

        $('.year-item').removeClass('active');
        $(`.year-item[data-year-id="${yearId}"]`).addClass('active');

        $('#emptyState').hide();
        $('#detailsContent').show();
        $('#detailsTools').show();

        $('#detailsTitle').text(`School Year ${year.year_start}-${year.year_end}`);

        // ── ADDED: hide until semesters load and confirm all are completed ────
        $('#graduationBtn').hide();
        // ─────────────────────────────────────────────────────────────────────

        loadSemesters(yearId);
    }

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

                    if (currentSemesters.length >= MAX_SEMESTERS) {
                        $('#addSemesterBtn').prop('disabled', true)
                            .attr('title', `Maximum of ${MAX_SEMESTERS} semesters reached`);
                    } else {
                        $('#addSemesterBtn').prop('disabled', false)
                            .attr('title', 'Add Semester');
                    }

                    // ── ADDED: show graduation only if every semester is completed ──
                    const total     = response.data.length;
                    const completed = response.data.filter(s => s.status === 'completed').length;
                    $('#graduationBtn').toggle(total > 0 && total === completed);
                    // ─────────────────────────────────────────────────────────────

                } else {
                    currentSemesters = [];
                    $('#noSemesters').show();
                    $('#addSemesterBtn').prop('disabled', false);

                    // ── ADDED ─────────────────────────────────────────────────
                    $('#graduationBtn').hide();
                    // ─────────────────────────────────────────────────────────
                }
            },
            error: function () {
                $('#semestersLoading').hide();
                $('#noSemesters').show();

                // ── ADDED ─────────────────────────────────────────────────────
                $('#graduationBtn').hide();
                // ─────────────────────────────────────────────────────────────
            }
        });
    }

    function displaySemesters(semesters) {
        const tbody = $('#semestersTableBody');
        tbody.empty();

        const sortedSemesters = semesters.sort((a, b) => {
            const orderA = parseInt(a.name.match(/\d+/)[0]);
            const orderB = parseInt(b.name.match(/\d+/)[0]);
            return orderA - orderB;
        });

        sortedSemesters.forEach(sem => {
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
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        // Use event delegation
        tbody.off('click', '.edit-semester-btn').on('click', '.edit-semester-btn', function () {
            const semId = $(this).data('semester-id');
            editSemester(semId);
        });
    }

    function editSemester(semesterId) {
        const semester = currentSemesters.find(s => s.id === semesterId);
        if (!semester) return;

        const year = schoolYears.find(sy => sy.id === semester.school_year_id);

        isSemesterEditMode = true;
        $('#semModalTitle').text(`Edit Semester - ${semester.name} (SY ${year.year_start}-${year.year_end})`);
        $('#semesterId').val(semester.id);
        $('#semesterSchoolYearId').val(semester.school_year_id);
        $('#startDate').val(semester.start_date);
        $('#endDate').val(semester.end_date);

        const minDate = `${year.year_start}-01-01`;
        const maxDate = `${year.year_end}-12-31`;
        $('#startDate').attr('min', minDate).attr('max', maxDate);
        $('#endDate').attr('min', minDate).attr('max', maxDate);

        if (!$('#semesterDateHelper').length) {
            $('#endDate').parent().after(`
                <small id="semesterDateHelper" class="form-text text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Dates must be between ${formatDate(minDate)} and ${formatDate(maxDate)}
                </small>
            `);
        } else {
            $('#semesterDateHelper').html(`
                <i class="fas fa-info-circle"></i> 
                Dates must be between ${formatDate(minDate)} and ${formatDate(maxDate)}
            `);
        }

        $('#semesterModal').modal('show');
    }

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

    function editSchoolYear(id) {
        const sy = schoolYears.find(item => item.id === id);
        if (sy) {
            isEditMode = true;
            $('#syModalTitle').text('Edit School Year');
            $('#schoolYearId').val(sy.id);
            $('#yearStart').val(sy.year_start);
            $('#yearEnd').val(sy.year_end);
            $('#schoolYearModal').modal('show');
        }
    }

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