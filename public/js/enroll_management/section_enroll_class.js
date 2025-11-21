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
    let selectedClassesList = []; // Store selected classes
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

    // ========================================================================
    // POPULATE FILTERS
    // ========================================================================
    function populateFilters() {
        // Levels
        $('#levelFilter').html('<option value="">All Levels</option>');
        levels.forEach(level => {
            $('#levelFilter').append(`<option value="${level.id}">${level.name}</option>`);
        });

        // Strands
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
                                <i class="fas fa-user-graduate"></i> ${section.student_count || 0} students
                            </small>
                        </div>
                        <span class="badge badge-light badge-pill ml-2">${section.class_count || 0}</span>
                    </div>
                </a>
            `;
            container.append(item);
        });
    }

    // ========================================================================
    // SECTION ITEM CLICK
    // ========================================================================
    $(document).on('click', '.section-item', function(e) {
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
        
        loadSectionDetails(selectedSectionId);
    });

    // ========================================================================
    // LOAD SECTION DETAILS
    // ========================================================================
    function loadSectionDetails(sectionId) {
        showLoader('#enrolledClassesBody', 'Loading classes...', 5);
        showLoader('#studentsBody', 'Loading students...', 5);
        
        const url = API_ROUTES.getSectionDetails.replace(':id', sectionId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    renderClasses(response.classes || []);
                    renderStudents(response.students || []);
                } else {
                    showError('#enrolledClassesBody', 'Failed to load classes', 5);
                    showError('#studentsBody', 'Failed to load students', 5);
                }
            },
            error: function (xhr) {
                showError('#enrolledClassesBody', 'Failed to load data', 5);
                showError('#studentsBody', 'Failed to load data', 5);
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
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">No classes enrolled yet</p>
                    </td>
                </tr>
            `);
            return;
        }

        classes.forEach((cls, index) => {
            const teachers = cls.teachers && cls.teachers.length > 0
                ? cls.teachers.map(t => t.name).join(', ')
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
        
        $('#studentsCount').text(`${students.length} ${students.length === 1 ? 'Student' : 'Students'}`);

        if (students.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">No students in this section</p>
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
                    <td>${index + 1}</td>
                    <td>${student.student_number}</td>
                    <td>${fullName}</td>
                    <td>${student.email || '-'}</td>
                    <td>
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
    // ENROLL CLASS BUTTON - OPEN MODAL
    // ========================================================================
    $('#enrollClassBtn').click(function () {
        if (!selectedSectionId) return;
        
        // Reset selected classes list
        selectedClassesList = [];
        updateSelectedClassesDisplay();
        
        loadAvailableClasses();
        $('#enrollClassModal').modal('show');
    });

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
    // AVAILABLE CLASSES SELECT CHANGE
    // ========================================================================
    $('#availableClassesSelect').change(function () {
        const selected = $(this).find(':selected');
        if (selected.val()) {
            $('#addClassToListBtn').prop('disabled', false);
        } else {
            $('#addClassToListBtn').prop('disabled', true);
        }
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

        // Check if already added
        if (selectedClassesList.some(c => c.id === classId)) {
            Toast.fire({
                icon: "info",
                title: "This class is already in the list"
            });

            return;
        }

        // Add to list
        const classData = {
            id: classId,
            name: selectedOption.data('class-name'),
            teachers: selectedOption.data('teachers')
        };

        selectedClassesList.push(classData);
        
        // Remove from dropdown
        selectedOption.remove();
        
        // Reset select
        select.val('');
        $('#addClassToListBtn').prop('disabled', true);
        
        // Update display
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
        
        // Remove from array
        selectedClassesList.splice(index, 1);
        
        // Add back to dropdown
        const select = $('#availableClassesSelect');
        select.append(`
            <option value="${removedClass.id}" 
                    data-teachers="${removedClass.teachers}"
                    data-class-name="${removedClass.name}">
                ${removedClass.name}
            </option>
        `);
        
        // Sort options alphabetically
        const options = select.find('option:not(:first)').sort((a, b) => {
            return $(a).text().localeCompare($(b).text());
        });
        select.find('option:not(:first)').remove();
        select.append(options);
        
        // Update display
        updateSelectedClassesDisplay();
        
                Toast.fire({
                    icon: "info",
                    title: "Class removed from list"
                });
    });

    // ========================================================================
    // CONFIRM ENROLL ALL CLASSES
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
        $('#availableClassesSelect').val('');
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
    // FILTERS
    // ========================================================================
    $('#sectionSearch, #levelFilter, #strandFilter').on('input change', function () {
        renderSectionsList();
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
});