console.log("Section Grades View");

$(document).ready(function () {
    const Toast = Swal.mixin({
        toast: true,
        position: "top-right",
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true
    });

    let selectedSectionId = null;
    let selectedClassCode = null;
    let sections    = [];
    let levels      = [];
    let strands     = [];
    let allStudents = [];
    let gradeMap    = {};       // { class_code: [student_numbers that submitted] }
    let loadedClasses = [];

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
                console.log('Sections response:', response);

                if (response.success) {
                    sections = Array.isArray(response.sections) ? response.sections : [];
                    levels   = Array.isArray(response.levels)   ? response.levels   : [];
                    strands  = Array.isArray(response.strands)  ? response.strands  : [];

                    console.log('Loaded sections:', sections.length);

                    if (sections.length === 0) {
                        showError('#sectionsListContainer', 'No sections with enrolled classes found for current semester');
                        return;
                    }

                    populateFilters();
                    renderSectionsList();
                } else {
                    console.error('Failed to load sections:', response.message);
                    showError('#sectionsListContainer', response.message || 'No sections found');
                }
            },
            error: function (xhr) {
                console.error('AJAX Error:', xhr);
                showError('#sectionsListContainer', xhr.responseJSON?.message || 'Failed to load sections');
                Toast.fire({ icon: "error", title: "Could not load sections. Please refresh the page." });
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
        const container    = $('#sectionsListContainer');
        const search       = $('#sectionSearch').val().toLowerCase();
        const levelFilter  = $('#levelFilter').val();
        const strandFilter = $('#strandFilter').val();

        let filtered = sections.filter(section => {
            const matchSearch  = !search || section.name.toLowerCase().includes(search) || section.code.toLowerCase().includes(search);
            const matchLevel   = !levelFilter  || section.level_id  == levelFilter;
            const matchStrand  = !strandFilter || section.strand_id == strandFilter;
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
            const percentage     = section.submission_percentage || 0;

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
                                <i class="fas fa-user-graduate"></i> ${section.student_count || 0} students |
                                <i class="fas fa-book"></i> ${section.class_count || 0} classes
                            </small>
                            <div class="submission-progress mt-2">
                                <div class="submission-progress-bar bg-primary"
                                     style="width: ${percentage}%"></div>
                            </div>
                            <small class="text-muted">${percentage}% submitted</small>
                        </div>
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
        const sectionName   = $(this).data('section-name');
        const sectionLevel  = $(this).data('section-level');
        const sectionStrand = $(this).data('section-strand');

        $('#selectedSectionName').html(`<i class="fas fa-users"></i> ${sectionName}`);
        $('#levelDisplay').text(sectionLevel   || '-');
        $('#strandDisplay').text(sectionStrand || '-');

        $('#noSectionSelected').hide();
        $('#gradesSection').show();

        selectedClassCode = null;
        resetStudentFilters();
        loadSectionDetails(selectedSectionId);
    });

    // ========================================================================
    // LOAD SECTION DETAILS
    // ========================================================================
    function loadSectionDetails(sectionId) {
        $('#classesContainer').html(`
            <div class="text-center py-4 text-primary">
                <i class="fas fa-spinner fa-spin"></i> Loading classes...
            </div>
        `);
        showLoader('#studentsBody', 'Loading students...', 4);

        gradeMap      = {};
        loadedClasses = [];

        const url = API_ROUTES.getSectionDetails.replace(':id', sectionId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    loadedClasses = response.classes   || [];
                    gradeMap      = response.grade_map || {};
                    allStudents   = response.students  || [];

                    renderClasses(loadedClasses);
                    renderStudents(allStudents);
                } else {
                    showError('#classesContainer', 'Failed to load classes');
                    showError('#studentsBody', 'Failed to load students', 4);
                }
            },
            error: function () {
                showError('#classesContainer', 'Failed to load data');
                showError('#studentsBody', 'Failed to load data', 4);
                Toast.fire({ icon: "error", title: "Could not load section details" });
            }
        });
    }

    // ========================================================================
    // RENDER CLASSES WITH GRADE STATUS
    // ========================================================================
    function renderClasses(classes) {
        const container = $('#classesContainer');
        container.empty();

        $('#classesCount').text(`${classes.length} ${classes.length === 1 ? 'Class' : 'Classes'}`);

        if (classes.length === 0) {
            container.html(`
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p class="mb-0">No classes enrolled</p>
                </div>
            `);
            return;
        }

        classes.forEach(cls => {
            const statusText  = cls.is_complete
                ? '<span class="text-success">Complete</span>'
                : '<span class="text-danger">Not Complete</span>';
            const borderClass = cls.is_complete ? 'border-success' : 'border-danger';
            const teachers    = cls.teachers || '<span class="text-muted">No teacher assigned</span>';

            const sectionCode  = $('.section-item.active').data('section-code') || '';
            const gradeListUrl = `/admin/grades/list?class=${encodeURIComponent(cls.class_code)}&section=${encodeURIComponent(sectionCode)}&semester=${cls.semester_id || ''}`;

            const viewButton = cls.is_complete
                ? `<a href="${gradeListUrl}" class="btn btn-sm btn-secondary" title="View Grades"
                      onclick="event.stopPropagation()">
                       <i class="fas fa-eye"></i>
                   </a>`
                : '';

            container.append(`
                <div class="card class-card ${borderClass} mb-2"
                     data-class-code="${cls.class_code}"
                     title="Click to filter students by grade status for this class"
                     style="cursor: pointer;">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                ${statusText}
                            </div>
                            <div class="col-md-6">
                                <h6 class="mb-0"><strong>${cls.class_name}</strong></h6>
                                <small class="text-muted">
                                    <i class="fas fa-chalkboard-teacher"></i> ${teachers}
                                </small>
                            </div>
                            <div class="col-md-2 text-center">
                                <small class="text-muted d-block">Submitted</small>
                                <strong>${cls.submitted_count} / ${cls.total_students}</strong>
                            </div>
                            <div class="col-md-2 text-right">
                                ${viewButton}
                            </div>
                        </div>
                    </div>
                </div>
            `);
        });
    }

    // ========================================================================
    // CLASS CARD CLICK — selects class to show per-student grade status
    // ========================================================================
    $(document).on('click', '.class-card', function () {
        const clickedCode = $(this).data('class-code');

        // Toggle off if clicking the same card
        if (selectedClassCode === clickedCode) {
            selectedClassCode = null;
            $('.class-card').removeClass('class-card-selected');
            applyStudentFilters();
            return;
        }

        selectedClassCode = clickedCode;
        $('.class-card').removeClass('class-card-selected');
        $(this).addClass('class-card-selected');

        applyStudentFilters();
    });

    // ========================================================================
    // RENDER STUDENTS
    // ========================================================================
    function renderStudents(students) {
        const tbody         = $('#studentsBody');
        const totalCount    = allStudents.length;
        const filteredCount = students.length;

        tbody.empty();

        const countText = filteredCount === totalCount
            ? `${totalCount} ${totalCount === 1 ? 'Student' : 'Students'}`
            : `${filteredCount} of ${totalCount} Students`;
        $('#studentsCount').text(countText);

        if (students.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">${allStudents.length === 0 ? 'No students in this section' : 'No students match your filters'}</p>
                    </td>
                </tr>
            `);
            return;
        }

        students.forEach(student => {
            const fullName = `${student.last_name}, ${student.first_name} ${student.middle_name || ''}`.trim();

            let statusCell;
            if (!selectedClassCode) {
                statusCell = '<span class="text-muted">-</span>';
            } else {
                const submittedList = gradeMap[selectedClassCode] || [];
                const isSubmitted   = submittedList.includes(student.student_number);
                statusCell = isSubmitted
                    ? '<span class="badge badge-primary">Submitted</span>'
                    : '<span class="badge badge-secondary">Pending</span>';
            }

            tbody.append(`
                <tr>
                    <td>${student.student_number}</td>
                    <td>${fullName}</td>
                    <td class="text-center">${student.gender || '-'}</td>
                    <td class="text-center">${statusCell}</td>
                </tr>
            `);
        });
    }

    // ========================================================================
    // STUDENT FILTERS
    // ========================================================================
    function applyStudentFilters() {
        const searchTerm        = $('#studentSearchFilter').val().toLowerCase();
        const genderFilter      = $('#genderFilter').val();
        const gradeStatusFilter = $('#gradeStatusFilter').val();

        const filtered = allStudents.filter(student => {
            const fullName = `${student.first_name} ${student.middle_name} ${student.last_name}`.toLowerCase();

            const matchSearch = !searchTerm ||
                student.student_number.toLowerCase().includes(searchTerm) ||
                fullName.includes(searchTerm);

            const matchGender = !genderFilter || student.gender === genderFilter;

            // Status filter only applies when a class card is selected
            let matchStatus = true;
            if (gradeStatusFilter && selectedClassCode) {
                const submittedList = gradeMap[selectedClassCode] || [];
                const isSubmitted   = submittedList.includes(student.student_number);
                if (gradeStatusFilter === 'submitted') matchStatus = isSubmitted;
                if (gradeStatusFilter === 'pending')   matchStatus = !isSubmitted;
            }

            return matchSearch && matchGender && matchStatus;
        });

        renderStudents(filtered);
    }

    $('#studentSearchFilter, #genderFilter, #gradeStatusFilter').on('input change', applyStudentFilters);

    $('#resetStudentFiltersBtn').click(function () {
        resetStudentFilters();
        renderStudents(allStudents);
    });

    function resetStudentFilters() {
        $('#studentSearchFilter').val('');
        $('#genderFilter').val('');
        $('#gradeStatusFilter').val('');
        selectedClassCode = null;
        $('.class-card').removeClass('class-card-selected');
    }

    // ========================================================================
    // SECTION FILTERS
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