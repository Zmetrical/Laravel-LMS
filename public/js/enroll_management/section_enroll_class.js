$(document).ready(function () {
    const Toast = Swal.mixin({
        toast: true,
        position: "top-right",
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true
    });

    let selectedSectionId = null;
    let sections = [];
    let levels = [];
    let strands = [];
    let selectedClassesList = [];
    let allStudents = [];
    const MAX_CLASSES = 10;

    // Load sections on page load
    loadSections();

    // ========================================================================
    // LOAD SECTIONS
    // ========================================================================
    function loadSections() {
        showLoader('#sectionsListContainer', 'Loading sections...');

        $.ajax({
            url: API_ROUTES.getSections,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    sections = response.sections || [];
                    levels = response.levels || [];
                    strands = response.strands || [];

                    populateFilters();
                    renderSectionsList();
                } else {
                    showError('#sectionsListContainer', 'No sections found');
                }
            },
            error: function (xhr) {
                showError('#sectionsListContainer', 'Failed to load sections');
                Toast.fire({
                    icon: "error",
                    title: "Could not load sections. Please refresh the page."
                });
            }
        });
    }

    function loadSectionAdviser(sectionId) {
        $('#adviserDisplay').html('<small class="text-muted"><i class="fas fa-spinner fa-spin"></i> Loading adviser...</small>');

        const url = API_ROUTES.getSectionAdviser.replace(':id', sectionId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    if (response.adviser) {
                        const fullName = `${response.adviser.first_name} ${response.adviser.middle_name || ''} ${response.adviser.last_name}`.trim();
                        $('#adviserDisplay').html(`
                            <small>
                                <strong><i class="fas fa-user-tie"></i> Adviser:</strong> 
                                <span class="text-primary">${fullName}</span>
                            </small>
                        `);
                    } else {
                        $('#adviserDisplay').html('<small class="text-muted"><i class="fas fa-exclamation-circle"></i> No adviser assigned</small>');
                    }
                }
            },
            error: function (xhr) {
                $('#adviserDisplay').html('<small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Failed to load adviser</small>');
            }
        });
    }

    // =========================================================================
    // SELECT2 INITIALIZATION
    // =========================================================================
    function initializeClassSelect2() {
        if ($('#availableClassesSelect').hasClass('select2-hidden-accessible')) {
            $('#availableClassesSelect').select2('destroy');
        }

        loadAvailableClasses();
    }

    // ========================================================================
    // LOAD AVAILABLE CLASSES
    // ========================================================================
    function loadAvailableClasses() {
        $('#availableClassesSelect').html('<option value="">Loading...</option>').prop('disabled', true);
        $('#addClassToListBtn').prop('disabled', true);

        const url = API_ROUTES.getAvailableClasses.replace(':id', selectedSectionId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    const select = $('#availableClassesSelect');
                    select.empty().prop('disabled', false);

                    if (response.classes.length === 0) {
                        select.append('<option value="">No available classes</option>');
                        return;
                    }

                    select.append('<option value="">-- Select Class --</option>');

                    response.classes.forEach(cls => {
                        select.append(`
                            <option value="${cls.id}" 
                                    data-teachers="${cls.teachers}"
                                    data-class-name="${cls.class_name}">
                                ${cls.class_name}
                            </option>
                        `);
                    });

                    $('#availableClassesSelect').select2({
                        theme: 'bootstrap4',
                        placeholder: 'Search for class...',
                        allowClear: true,
                        dropdownParent: $('#enrollClassModal')
                    });

                    $('#availableClassesSelect').on('select2:select', function (e) {
                        $('#addClassToListBtn').prop('disabled', false);
                    });

                    $('#availableClassesSelect').on('select2:unselect select2:clear', function (e) {
                        $('#addClassToListBtn').prop('disabled', true);
                    });
                } else {
                    $('#availableClassesSelect').html('<option value="">No available classes</option>');
                }
            },
            error: function (xhr) {
                $('#availableClassesSelect').html('<option value="">Error loading classes</option>');
                Toast.fire({
                    icon: "error",
                    title: "Failed to load available classes"
                });
            }
        });
    }

    // ========================================================================
    // POPULATE FILTERS
    // ========================================================================
    function populateFilters() {
        $('#levelFilter').html('<option value="">All Levels</option>');
        levels.forEach(level => {
            $('#levelFilter').append(`<option value="${level.id}">${level.name}</option>`);
        });

        $('#strandFilter').html('<option value="">All Strands</option>');
        strands.forEach(strand => {
            $('#strandFilter').append(`<option value="${strand.id}">${strand.code}</option>`);
        });
    }

    // ========================================================================
    // RENDER SECTIONS LIST
    // ========================================================================
    function renderSectionsList() {
        const container = $('#sectionsListContainer');
        const search = $('#sectionSearch').val().toLowerCase();
        const levelFilter = $('#levelFilter').val();
        const strandFilter = $('#strandFilter').val();

        let filtered = sections.filter(section => {
            const matchSearch = !search ||
                section.name.toLowerCase().includes(search) ||
                section.code.toLowerCase().includes(search);
            const matchLevel = !levelFilter || section.level_id == levelFilter;
            const matchStrand = !strandFilter || section.strand_id == strandFilter;
            return matchSearch && matchLevel && matchStrand;
        });

        container.empty();

        if (filtered.length === 0) {
            container.html(`
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p class="mb-0 small">No sections found</p>
                </div>
            `);
            return;
        }

        filtered.forEach(section => {
            const sectionDisplay = `${section.level_name} - ${section.strand_code || section.strand_name}`;

            // Show adviser name or "No adviser" if not assigned
            const adviserDisplay = section.adviser_name
                ? `<i class="fas fa-user-tie"></i> ${section.adviser_name}`
                : '<span class="text-muted"><i class="fas fa-user-tie"></i> No adviser</span>';

            const item = `
                <a href="#" class="list-group-item list-group-item-action section-item" 
                   data-section-id="${section.id}"
                   data-section-name="${section.name}"
                   data-section-level="${section.level_name}"
                   data-section-strand="${section.strand_name}"
                   data-section-code="${section.code}">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <h6 class="mb-1"><strong>${section.name}</strong></h6>
                            <p class="mb-1 text-muted small">${sectionDisplay}</p>
                            <small class="text-muted">
                                ${adviserDisplay}
                            </small>
                        </div>
                        <span class="badge badge-secondary badge-pill ml-2">${section.class_count || 0}</span>
                    </div>
                </a>
            `;
            container.append(item);
        });
    }

    // ========================================================================
    // SECTION ITEM CLICK
    // ========================================================================
    $(document).on('click', '.section-item', function (e) {
        e.preventDefault();

        $('.section-item').removeClass('active');
        $(this).addClass('active');

        selectedSectionId = $(this).data('section-id');
        const sectionName = $(this).data('section-name');
        const sectionLevel = $(this).data('section-level');
        const sectionStrand = $(this).data('section-strand');

        $('#selectedSectionName').html(`<i class="fas fa-users"></i> ${sectionName}`);
        $('#levelDisplay').text(sectionLevel || '-');
        $('#strandDisplay').text(sectionStrand || '-');

        $('#noSectionSelected').hide();
        $('#enrollmentSection').show();

        resetStudentFilters();

        loadSectionDetails(selectedSectionId);
        loadSectionAdviser(selectedSectionId);
    });

    // ========================================================================
    // LOAD SECTION DETAILS
    // ========================================================================
    function loadSectionDetails(sectionId) {
        showLoader('#enrolledClassesBody', 'Loading classes...', 4);
        showLoader('#studentsBody', 'Loading students...', 6);

        const url = API_ROUTES.getSectionDetails.replace(':id', sectionId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    renderClasses(response.classes || []);
                    allStudents = response.students || [];
                    renderStudents(allStudents);
                } else {
                    showError('#enrolledClassesBody', 'Failed to load classes', 4);
                    showError('#studentsBody', 'Failed to load students', 6);
                }
            },
            error: function (xhr) {
                showError('#enrolledClassesBody', 'Failed to load data', 4);
                showError('#studentsBody', 'Failed to load data', 6);
                Toast.fire({
                    icon: "error",
                    title: "Could not load section details"
                });
            }
        });
    }

    // ========================================================================
    // RENDER CLASSES
    // ========================================================================
    function renderClasses(classes) {
        const tbody = $('#enrolledClassesBody');
        tbody.empty();

        $('#classesCount').text(`${classes.length} ${classes.length === 1 ? 'Class' : 'Classes'}`);

        if (classes.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">No classes enrolled yet</p>
                    </td>
                </tr>
            `);
            return;
        }

        classes.forEach((cls, index) => {
            const teachers = cls.teachers
                ? cls.teachers
                : '<span class="text-muted">No teacher assigned</span>';

            const row = `
                <tr>
                    <td>${index + 1}</td>
                    <td><strong>${cls.class_name}</strong></td>
                    <td>${teachers}</td>
                    <td class="text-center">
                        <button class="btn btn-danger btn-sm remove-class-btn" 
                                data-class-id="${cls.id}" 
                                data-class-name="${cls.class_name}"
                                title="Remove class">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // ========================================================================
    // RENDER STUDENTS
    // ========================================================================
    function renderStudents(students) {
        const tbody = $('#studentsBody');
        tbody.empty();

        const totalCount = allStudents.length;
        const filteredCount = students.length;
        const countText = filteredCount === totalCount
            ? `${totalCount} ${totalCount === 1 ? 'Student' : 'Students'}`
            : `${filteredCount} of ${totalCount} Students`;

        $('#studentsCount').text(countText);

        if (students.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">${allStudents.length === 0 ? 'No students in this section' : 'No students match your filters'}</p>
                    </td>
                </tr>
            `);
            return;
        }

        students.forEach((student, index) => {
            const fullName = `${student.last_name}, ${student.first_name} ${student.middle_name || ''}`.trim();
            const typeColor = student.student_type === 'regular' ? 'primary' : 'secondary';

            const row = `
                <tr>
                    <td>${student.student_number}</td>
                    <td>${fullName}</td>
                    <td class="text-center">
                        ${student.gender || '-'}
                    </td>
                    <td>${student.email || '-'}</td>
                    <td class="text-center">
                        <span class="badge badge-${typeColor}">
                            ${student.student_type ? student.student_type.toUpperCase() : 'N/A'}
                        </span>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // ========================================================================
    // STUDENT FILTERS
    // ========================================================================
    function applyStudentFilters() {
        const searchTerm = $('#studentSearchFilter').val().toLowerCase();
        const genderFilter = $('#genderFilter').val();
        const typeFilter = $('#studentTypeFilter').val();

        const filtered = allStudents.filter(student => {
            const fullName = `${student.first_name} ${student.middle_name} ${student.last_name}`.toLowerCase();

            const matchSearch = !searchTerm ||
                student.student_number.toLowerCase().includes(searchTerm) ||
                fullName.includes(searchTerm);

            const matchGender = !genderFilter || student.gender === genderFilter;
            const matchType = !typeFilter || student.student_type === typeFilter;

            return matchSearch && matchGender && matchType;
        });

        renderStudents(filtered);
    }

    $('#studentSearchFilter, #genderFilter, #studentTypeFilter').on('input change', applyStudentFilters);

    $('#resetStudentFiltersBtn').click(function () {
        resetStudentFilters();
        renderStudents(allStudents);
    });

    function resetStudentFilters() {
        $('#studentSearchFilter').val('');
        $('#genderFilter').val('');
        $('#studentTypeFilter').val('');
    }

    // ========================================================================
    // ENROLL CLASS BUTTON
    // ========================================================================
    $('#enrollClassBtn').click(function () {
        if (!selectedSectionId) return;

        selectedClassesList = [];
        updateSelectedClassesDisplay();

        initializeClassSelect2();

        $('#enrollClassModal').modal('show');
    });

    // ========================================================================
    // ADD CLASS TO LIST
    // ========================================================================
    $('#addClassToListBtn').click(function () {
        if (selectedClassesList.length >= MAX_CLASSES) {
            Toast.fire({
                icon: "warning",
                title: `You can only add up to ${MAX_CLASSES} classes at once`
            });
            return;
        }

        const select = $('#availableClassesSelect');
        const selectedOption = select.find(':selected');
        const classId = selectedOption.val();

        if (!classId) return;

        if (selectedClassesList.some(c => c.id === classId)) {
            Toast.fire({
                icon: "info",
                title: "This class is already in the list"
            });
            return;
        }

        const classData = {
            id: classId,
            name: selectedOption.data('class-name'),
            teachers: selectedOption.data('teachers')
        };

        selectedClassesList.push(classData);
        selectedOption.remove();
        select.val('').trigger('change');
        $('#addClassToListBtn').prop('disabled', true);

        updateSelectedClassesDisplay();

        Toast.fire({
            icon: "success",
            title: "Class added to list"
        });
    });

    // ========================================================================
    // UPDATE SELECTED CLASSES DISPLAY
    // ========================================================================
    function updateSelectedClassesDisplay() {
        const container = $('#selectedClassesContainer');
        const count = selectedClassesList.length;

        $('#selectedClassesCount').text(count);
        $('#enrollCountBadge').text(count);

        if (count === 0) {
            container.html(`
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p class="mb-0">No classes selected yet</p>
                    <small>Select a class above and click "Add to List"</small>
                </div>
            `);
            $('#confirmEnrollBtn').prop('disabled', true);
            return;
        }

        let html = '<ul class="list-group list-group-flush">';

        selectedClassesList.forEach((cls, index) => {
            html += `
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${cls.name}</strong>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-chalkboard-teacher"></i> ${cls.teachers}
                        </small>
                    </div>
                    <button class="btn btn-sm btn-danger remove-from-list-btn" 
                            data-index="${index}"
                            title="Remove from list">
                        <i class="fas fa-times"></i>
                    </button>
                </li>
            `;
        });

        html += '</ul>';
        container.html(html);

        $('#confirmEnrollBtn').prop('disabled', false);
    }

    // ========================================================================
    // REMOVE FROM LIST
    // ========================================================================
    $(document).on('click', '.remove-from-list-btn', function () {
        const index = $(this).data('index');
        const removedClass = selectedClassesList[index];

        selectedClassesList.splice(index, 1);

        const select = $('#availableClassesSelect');
        select.append(`
            <option value="${removedClass.id}" 
                    data-teachers="${removedClass.teachers}"
                    data-class-name="${removedClass.name}">
                ${removedClass.name}
            </option>
        `);

        const options = select.find('option:not(:first)').sort((a, b) => {
            return $(a).text().localeCompare($(b).text());
        });
        select.find('option:not(:first)').remove();
        select.append(options);

        updateSelectedClassesDisplay();

        Toast.fire({
            icon: "info",
            title: "Class removed from list"
        });
    });

    // ========================================================================
    // CONFIRM ENROLL
    // ========================================================================
    $('#confirmEnrollBtn').click(function () {
        if (selectedClassesList.length === 0 || !selectedSectionId) return;

        if (!ACTIVE_SEMESTER_ID) {
            Toast.fire({
                icon: "error",
                title: "No active semester found. Please set an active semester first."
            });
            return;
        }

        const classIds = selectedClassesList.map(c => c.id);
        const url = API_ROUTES.enrollClass.replace(':id', selectedSectionId);

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                class_ids: classIds,
                semester_id: ACTIVE_SEMESTER_ID,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: "success",
                        title: "Success",
                        text: response.message,
                        timer: 2000
                    });
                    $('#enrollClassModal').modal('hide');
                    loadSectionDetails(selectedSectionId);
                    loadSections();
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to enroll classes';
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: message
                });
            }
        });
    });

    // ========================================================================
    // RESET MODAL ON CLOSE
    // ========================================================================
    $('#enrollClassModal').on('hidden.bs.modal', function () {
        selectedClassesList = [];

        if ($('#availableClassesSelect').hasClass('select2-hidden-accessible')) {
            $('#availableClassesSelect').select2('destroy');
        }

        $('#availableClassesSelect').empty().html('<option value="">Search for class...</option>');
        $('#addClassToListBtn').prop('disabled', true);
        updateSelectedClassesDisplay();
    });

    // ========================================================================
    // REMOVE CLASS FROM SECTION
    // ========================================================================
    $(document).on('click', '.remove-class-btn', function () {
        const classId = $(this).data('class-id');
        const className = $(this).data('class-name');

        Swal.fire({
            title: "Remove Class?",
            text: `Are you sure you want to remove "${className}"?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#6c757d",
            confirmButtonText: "Yes, remove it"
        }).then(result => {
            if (result.isConfirmed) {
                removeClassFromSection(classId);
            }
        });
    });

    function removeClassFromSection(classId) {
        const url = API_ROUTES.removeClass
            .replace(':sectionId', selectedSectionId)
            .replace(':classId', classId);

        $.ajax({
            url: url,
            method: "DELETE",
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                Swal.fire({
                    icon: "success",
                    title: "Class removed",
                    text: response.message,
                    timer: 2000
                });

                loadSectionDetails(selectedSectionId);
                loadSections();
            },
            error: function (xhr) {
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: xhr.responseJSON?.message || "Failed to remove class"
                });
            }
        });
    }

    // ========================================================================
    // SECTION FILTERS
    // ========================================================================
    $('#sectionSearch, #levelFilter, #strandFilter').on('input change', function () {
        renderSectionsList();
    });

    // ========================================================================
    // ADVISER MANAGEMENT
    // ========================================================================
    $('#assignAdviserBtn').click(function () {
        if (!selectedSectionId) return;
        loadAdviserModal();
        $('#assignAdviserModal').modal('show');
    });

    function loadAdviserModal() {
        const select = $('#adviserTeacherSelect');
        select.html('<option value="">Loading teachers...</option>').prop('disabled', true);

        loadCurrentAdviser();

        $.ajax({
            url: API_ROUTES.getAvailableTeachers,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    select.empty().prop('disabled', false);
                    select.append('<option value="">-- Select Teacher --</option>');

                    response.teachers.forEach(teacher => {
                        const fullName = `${teacher.last_name}, ${teacher.first_name} ${teacher.middle_name || ''}`.trim();
                        select.append(`
                            <option value="${teacher.id}">
                                ${fullName}
                            </option>
                        `);
                    });

                    if (!select.hasClass('select2-hidden-accessible')) {
                        select.select2({
                            theme: 'bootstrap4',
                            placeholder: '-- Select Teacher --',
                            allowClear: true,
                            dropdownParent: $('#assignAdviserModal')
                        });
                    }
                }
            },
            error: function () {
                select.html('<option value="">Failed to load teachers</option>');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Could not load teachers list'
                });
            }
        });
    }

    function loadCurrentAdviser() {
        const url = API_ROUTES.getSectionAdviser.replace(':id', selectedSectionId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success && response.adviser) {
                    const adviser = response.adviser;
                    const fullName = `${adviser.first_name} ${adviser.middle_name || ''} ${adviser.last_name}`.trim();
                    $('#currentAdviserNameModal').text(fullName);
                    $('#currentAdviserEmail').text(adviser.email);
                    $('#currentAdviserSection').show();
                    $('#removeAdviserBtn').show();
                } else {
                    $('#currentAdviserSection').hide();
                }
            },
            error: function () {
                $('#currentAdviserSection').hide();
            }
        });
    }

    $('#adviserTeacherSelect').on('select2:select', function () {
        $('#confirmAssignAdviserBtn').prop('disabled', false);
        $('#removeAdviserBtn').hide();
    });

    $('#adviserTeacherSelect').on('select2:unselect select2:clear', function () {
        $('#confirmAssignAdviserBtn').prop('disabled', true);
        loadCurrentAdviser();
    });

    $('#confirmAssignAdviserBtn').click(function () {
        const teacherId = $('#adviserTeacherSelect').val();

        if (!teacherId) {
            Swal.fire({
                icon: 'warning',
                title: 'No Teacher Selected',
                text: 'Please select a teacher to assign as adviser'
            });
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Assigning...');

        const url = API_ROUTES.assignAdviser.replace(':id', selectedSectionId);

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                teacher_id: teacherId,
                semester_id: ACTIVE_SEMESTER_ID,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#assignAdviserModal').modal('hide');
                    loadSectionAdviser(selectedSectionId);
                    loadSections();
                }
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Failed to assign adviser'
                });
            },
            complete: function () {
                btn.prop('disabled', false).html('<i class="fas fa-check"></i> Assign Adviser');
            }
        });
    });

    $(document).on('click', '#removeAdviserBtn', function () {
        Swal.fire({
            icon: 'warning',
            title: 'Remove Adviser?',
            text: 'Are you sure you want to remove this adviser from the section?',
            showCancelButton: true,
            confirmButtonText: 'Yes, remove',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = $('#removeAdviserBtn');
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Removing...');

                const url = API_ROUTES.removeAdviser.replace(':id', selectedSectionId);

                $.ajax({
                    url: url,
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            $('#assignAdviserModal').modal('hide');
                            loadSectionAdviser(selectedSectionId);
                            loadSections();
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to remove adviser'
                        });
                    },
                    complete: function () {
                        btn.prop('disabled', false).html('<i class="fas fa-times"></i> Remove Adviser');
                    }
                });
            }
        });
    });

    $('#assignAdviserModal').on('hidden.bs.modal', function () {
        if ($('#adviserTeacherSelect').hasClass('select2-hidden-accessible')) {
            $('#adviserTeacherSelect').select2('destroy');
        }
        $('#adviserTeacherSelect').empty().html('<option value="">-- Select Teacher --</option>');
        $('#confirmAssignAdviserBtn').prop('disabled', false);
        $('#currentAdviserSection').hide();
    });

    // ========================================================================
    // UTILITY FUNCTIONS
    // ========================================================================
    function showLoader(selector, message = 'Loading...', colspan = 1) {
        const loader = colspan > 1
            ? `<tr><td colspan="${colspan}" class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 mb-0 small">${message}</p>
               </td></tr>`
            : `<div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 mb-0 small">${message}</p>
               </div>`;

        $(selector).html(loader);
    }

    function showError(selector, message, colspan = 1) {
        const error = colspan > 1
            ? `<tr><td colspan="${colspan}" class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-triangle mb-2"></i>
                    <p class="mb-0 small">${message}</p>
               </td></tr>`
            : `<div class="text-center py-3 text-danger">
                    <i class="fas fa-exclamation-triangle mb-2"></i>
                    <p class="mb-0 small">${message}</p>
               </div>`;

        $(selector).html(error);
    }

    // ========================================================================
    // RENDER SECTIONS LIST
    // ========================================================================
    function renderSectionsList() {
        const container = $('#sectionsListContainer');
        const search = $('#sectionSearch').val().toLowerCase();
        const levelFilter = $('#levelFilter').val();
        const strandFilter = $('#strandFilter').val();
        const adviserFilter = $('#adviserFilter').val().toLowerCase();

        let filtered = sections.filter(section => {
            const matchSearch = !search ||
                section.name.toLowerCase().includes(search) ||
                section.code.toLowerCase().includes(search);
            const matchLevel = !levelFilter || section.level_id == levelFilter;
            const matchStrand = !strandFilter || section.strand_id == strandFilter;
            const matchAdviser = !adviserFilter ||
                (section.adviser_name && section.adviser_name.toLowerCase().includes(adviserFilter));

            return matchSearch && matchLevel && matchStrand && matchAdviser;
        });

        container.empty();

        if (filtered.length === 0) {
            container.html(`
            <div class="text-center py-4 text-muted">
                <i class="fas fa-inbox fa-2x mb-2"></i>
                <p class="mb-0 small">No sections found</p>
            </div>
        `);
            return;
        }

        filtered.forEach(section => {
            const sectionDisplay = `${section.level_name} - ${section.strand_code || section.strand_name}`;

            const adviserDisplay = section.adviser_name
                ? `<i class="fas fa-user-tie"></i> ${section.adviser_name}`
                : '<span class="text-muted"><i class="fas fa-user-tie"></i> No adviser</span>';

            const item = `
            <a href="#" class="list-group-item list-group-item-action section-item" 
               data-section-id="${section.id}"
               data-section-name="${section.name}"
               data-section-level="${section.level_name}"
               data-section-strand="${section.strand_name}"
               data-section-code="${section.code}">
                <div class="d-flex w-100 justify-content-between align-items-start">
                    <div style="flex: 1;">
                        <h6 class="mb-1"><strong>${section.name}</strong></h6>
                        <p class="mb-1 text-muted small">${sectionDisplay}</p>
                        <small class="text-muted">
                            ${adviserDisplay}
                        </small>
                    </div>
                    <span class="badge badge-secondary badge-pill ml-2">${section.class_count || 0}</span>
                </div>
            </a>
        `;
            container.append(item);
        });
    }

    // ========================================================================
    // SECTION FILTERS - Update this section
    // ========================================================================
    $('#sectionSearch, #levelFilter, #strandFilter, #adviserFilter').on('input change', function () {
        renderSectionsList();
    });
});