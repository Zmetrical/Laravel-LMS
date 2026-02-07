$(document).ready(function () {
    let currentSchoolYear = null;
    let semesters = [];
    let quarters = [];
    let selectedSemesterId = null;
    let selectedSectionId = null;
    let selectedSectionName = null;
    let selectedClassCode = null;
    let selectedQuarter = 'final';
    let allStudents = [];
    let allClasses = [];
    let filteredStudents = [];

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

    loadSchoolYear();
    loadSemesters();

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

    function displaySchoolYear() {
        $('#syDisplay').text(`SY ${currentSchoolYear.year_start}-${currentSchoolYear.year_end}`);
        
        const badgeClass = getStatusBadgeClass(currentSchoolYear.status);
        $('#statusBadge').attr('class', `badge badge-lg ${badgeClass}`)
                        .text(currentSchoolYear.status.toUpperCase());

        $('#schoolYearLoading').hide();
        $('#schoolYearInfo').show();
    }

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

    function displaySemesters() {
        const container = $('#semestersList');
        container.empty();

        semesters.forEach(sem => {
            const statusBadge = getStatusBadgeClass(sem.status);
            const isSelected = sem.id === selectedSemesterId ? 'active' : '';
            
            const startDate = new Date(sem.start_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            const endDate = new Date(sem.end_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            
            const item = `
                <a href="#" class="list-group-item list-group-item-action semester-item ${isSelected}" 
                   data-semester-id="${sem.id}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar mr-1"></i>
                                    ${sem.name}
                                </h6>
                                <span class="badge ${statusBadge}">${sem.status.toUpperCase()}</span>
                            </div>
                            <small class="text-muted d-block">${sem.code}</small>
                            <small class="text-muted">${startDate} - ${endDate}</small>
                        </div>
                    </div>
                </a>
            `;
            container.append(item);
        });

        container.find('.semester-item').click(function (e) {
            e.preventDefault();
            const semesterId = $(this).data('semester-id');
            selectSemester(semesterId);
        });
    }

    function selectSemester(semesterId) {
        selectedSemesterId = semesterId;
        const semester = semesters.find(s => s.id === semesterId);
        
        if (!semester) return;

        $('.semester-item').removeClass('active');
        $(`.semester-item[data-semester-id="${semesterId}"]`).addClass('active');

        selectedSectionId = null;
        selectedSectionName = null;
        selectedClassCode = null;
        allStudents = [];
        allClasses = [];
        filteredStudents = [];
        quarters = [];

        $('#filtersSection').hide();
        resetFilters();

        $('#sectionsCard').show();
        $('#emptyState').show();
        $('#studentsContent').hide();
        $('#noStudents').hide();
        $('#studentsLoading').hide();

        loadSemesterSections(semesterId);
    }

    function loadSemesterSections(semesterId) {
        $('#sectionsLoading').show();
        $('#sectionsList').hide();
        $('#noSections').hide();
        $('#sectionFiltersContainer').hide();

        const url = API_ROUTES.getSemesterSections.replace(':id', semesterId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                $('#sectionsLoading').hide();
                
                if (response.success && response.data.length > 0) {
                    populateSectionFilters(response.data);
                    displaySections(response.data);
                    $('#sectionFiltersContainer').show();
                    $('#sectionsList').show();
                } else {
                    $('#noSections').show();
                }
            },
            error: function () {
                $('#sectionsLoading').hide();
                $('#noSections').show();
            }
        });
    }

    function populateSectionFilters(sections) {
        const levels = [...new Set(sections.map(s => s.level_name))].sort();
        const levelSelect = $('#sectionLevelFilter');
        levelSelect.html('<option value="">All Levels</option>');
        levels.forEach(level => {
            levelSelect.append(`<option value="${level}">${level}</option>`);
        });

        const strands = [...new Set(sections.map(s => s.strand_code))].sort();
        const strandSelect = $('#sectionStrandFilter');
        strandSelect.html('<option value="">All Strands</option>');
        strands.forEach(strand => {
            strandSelect.append(`<option value="${strand}">${strand}</option>`);
        });
    }

    function displaySections(sections) {
        window.allSections = sections;
        renderFilteredSections();
    }

    function renderFilteredSections() {
        const container = $('#sectionsListContainer');
        const searchTerm = $('#sectionSearch').val().toLowerCase();
        const levelFilter = $('#sectionLevelFilter').val();
        const strandFilter = $('#sectionStrandFilter').val();

        let filtered = window.allSections.filter(sec => {
            const matchSearch = !searchTerm ||
                sec.section_name.toLowerCase().includes(searchTerm) ||
                sec.section_code.toLowerCase().includes(searchTerm);
            const matchLevel = !levelFilter || sec.level_name === levelFilter;
            const matchStrand = !strandFilter || sec.strand_code === strandFilter;
            
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

        filtered.forEach(sec => {
            const isSelected = sec.id === selectedSectionId ? 'active' : '';
            const sectionDisplay = `${sec.level_name} - ${sec.strand_code}`;
            
            const item = `
                <a href="#" class="list-group-item list-group-item-action section-item ${isSelected}" 
                   data-section-id="${sec.id}" 
                   data-section-name="${sec.section_name}">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <h6 class="mb-1"><strong>${sec.section_name}</strong></h6>
                            <p class="mb-1 text-muted small">${sectionDisplay}</p>
                            <small class="text-muted">
                                <i class="fas fa-user-graduate"></i> ${sec.regular_student_count || 0} students
                            </small>
                        </div>
                        <span class="badge badge-light badge-pill ml-2">${sec.class_count || 0}</span>
                    </div>
                </a>
            `;
            container.append(item);
        });

        container.find('.section-item').click(function (e) {
            e.preventDefault();
            const sectionId = $(this).data('section-id');
            const sectionName = $(this).data('section-name');
            selectSection(sectionId, sectionName);
        });
    }

    $('#sectionSearch, #sectionLevelFilter, #sectionStrandFilter').on('input change', function() {
        renderFilteredSections();
    });

    function selectSection(sectionId, sectionName) {
        selectedSectionId = sectionId;
        selectedSectionName = sectionName;
        selectedClassCode = null;
        selectedQuarter = 'final';

        $('.section-item').removeClass('active');
        $(`.section-item[data-section-id="${sectionId}"]`).addClass('active');

        $('#emptyState').hide();
        $('#studentsContent').hide();
        $('#noStudents').hide();
        $('#studentsLoading').show();
        $('#filtersSection').hide();
        $('#quarterTabsSection').hide();
        resetQuarterTabs();

        loadSectionEnrollment(sectionId);
    }

    function loadSectionEnrollment(sectionId) {
        const url = API_ROUTES.getSectionEnrollment
            .replace(':semesterId', selectedSemesterId)
            .replace(':sectionId', sectionId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                $('#studentsLoading').hide();
                
                if (response.success && response.students.length > 0) {
                    allStudents = response.students;
                    allClasses = response.classes || [];
                    quarters = response.quarters || [];
                    
                    populateClassDropdown(allClasses);
                    buildQuarterTabs(quarters);
                    
                    applyFilters();
                    $('#studentsContent').show();
                    $('#filtersSection').show();
                    $('#quarterTabsSection').show();
                } else {
                    $('#noStudents').show();
                    $('#studentCount').text('0 Students');
                }
            },
            error: function () {
                $('#studentsLoading').hide();
                $('#noStudents').show();
                $('#studentCount').text('0 Students');
            }
        });
    }

    function populateClassDropdown(classes) {
        const dropdown = $('#classDropdownList');
        dropdown.empty();
        
        dropdown.append('<li><a class="dropdown-item class-option" data-id="">All Classes (Overview)</a></li>');
        
        if (classes.length > 0) {
            dropdown.append('<li><hr class="dropdown-divider"></li>');
            classes.forEach(cls => {
                dropdown.append(`<li><a class="dropdown-item class-option" data-id="${cls.class_code}">${cls.class_name}</a></li>`);
            });
        }
    }

    function buildQuarterTabs(quartersData) {
        const tabsContainer = $('#quarterTabsSection .quarter-tabs');
        tabsContainer.empty();

        tabsContainer.append(`
            <li class="nav-item">
                <a class="nav-link active" href="#" data-quarter="final">
                    <i class="fas fa-trophy"></i> Final Grade
                </a>
            </li>
        `);

        quartersData.forEach(quarter => {
            const icon = quarter.code === '1ST' ? 'fa-calendar-day' : 
                        quarter.code === '2ND' ? 'fa-calendar-week' : 'fa-calendar';
            
            tabsContainer.append(`
                <li class="nav-item">
                    <a class="nav-link" href="#" data-quarter="${quarter.code}">
                        <i class="fas ${icon}"></i> ${quarter.name}
                    </a>
                </li>
            `);
        });
    }

    function displaySectionEnrollment(students, filterClassCode, quarter) {
        const tbody = $('#studentsTableBody');
        const thead = $('#studentsTableHead');
        tbody.empty();
        thead.empty();

        $('.classes-detail-row').remove();
        $('.expand-btn').removeClass('expanded');

        if (students.length === 0) {
            thead.html('<tr><th>No Data</th></tr>');
            tbody.html(`
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">No students found</p>
                    </td>
                </tr>
            `);
            return;
        }

        let headerHtml = '<tr>';

        if (!filterClassCode) {
            headerHtml += '<th width="40"></th>';
        }

        headerHtml += `
            <th width="150">Student Number</th>
            <th>Name</th>
        `;

        if (filterClassCode) {
            if (quarter === 'final') {
                headerHtml += `
                    <th width="100" class="text-center">Q1</th>
                    <th width="100" class="text-center">Q2</th>
                    <th width="100" class="text-center">Final Grade</th>
                    <th width="100">Remarks</th>
                `;
            } else {
                headerHtml += `<th width="100" class="text-center">${quarter === '1ST' ? '1st Quarter' : '2nd Quarter'} Grade</th>`;
            }
        } else {
            headerHtml += `<th width="80" class="text-center">Action</th>`;
        }

        headerHtml += `</tr>`;
        thead.html(headerHtml);

        students.forEach(student => {
            if (filterClassCode) {
                const classGrade = student.class_grades.find(g => g.class_code === filterClassCode);
                
                let row = `
                    <tr data-student-id="${student.student_number}">
                        <td><strong>${student.student_number}</strong></td>
                        <td>${student.full_name}</td>
                `;

                if (classGrade) {
                    if (quarter === 'final') {
                        const q1 = classGrade.q1 || '-';
                        const q2 = classGrade.q2 || '-';
                        const final = classGrade.final_grade || '-';
                        const remarks = classGrade.remarks || '-';
                        const remarksColor = classGrade.remarks === 'PASSED' ? '#28a745' :
                                           classGrade.remarks === 'FAILED' ? '#dc3545' : '#6c757d';
                        
                        row += `
                            <td class="text-center">${q1}</td>
                            <td class="text-center">${q2}</td>
                            <td class="text-center"><strong>${final}</strong></td>
                            <td class="text-center"><span style="color: ${remarksColor}; font-weight: 600;">${remarks}</span></td>
                        `;
                    } else if (quarter === '1ST') {
                        row += `<td class="text-center"><strong>${classGrade.q1 || '-'}</strong></td>`;
                    } else if (quarter === '2ND') {
                        row += `<td class="text-center"><strong>${classGrade.q2 || '-'}</strong></td>`;
                    }
                } else {
                    if (quarter === 'final') {
                        row += `<td class="text-center">-</td><td class="text-center">-</td><td class="text-center">-</td><td class="text-center">-</td>`;
                    } else {
                        row += `<td class="text-center">-</td>`;
                    }
                }

                row += `</tr>`;
                tbody.append(row);
            } else {
                const viewCardUrl = API_ROUTES.viewGradeCard
                    .replace(':student_number', student.student_number)
                    .replace(':semester_id', selectedSemesterId);
                
                let row = `
                    <tr data-student-id="${student.student_number}" data-classes='${JSON.stringify(student.class_grades)}'>
                        <td class="text-center">
                            <button class="expand-btn" data-student-id="${student.student_number}" title="Show Classes">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </td>
                        <td><strong>${student.student_number}</strong></td>
                        <td>${student.full_name}</td>
                        <td class="text-center">
                            <a href="${viewCardUrl}" class="btn btn-sm btn-primary" title="View Grade Card">
                                <i class="fas fa-file-alt"></i>
                            </a>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            }
        });
    }

    $(document).on('click', '.expand-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const studentId = $(this).data('student-id');
        const mainRow = $(this).closest('tr');
        const existingDetailRow = mainRow.next('.classes-detail-row');
        const isExpanded = $(this).hasClass('expanded');
        
        if (isExpanded) {
            $(this).removeClass('expanded');
            existingDetailRow.remove();
        } else {
            $('.expand-btn').removeClass('expanded');
            $('.classes-detail-row').remove();
            
            $(this).addClass('expanded');
            
            const classesData = JSON.parse(mainRow.attr('data-classes'));
            
            let classesHtml = '';
            if (classesData && classesData.length > 0) {
                classesData.forEach(function(classGrade) {
                    const cls = allClasses.find(c => c.class_code === classGrade.class_code);
                    const className = cls ? cls.class_name : classGrade.class_code;
                    
                    // ONLY SHOW GRADE FOR SELECTED QUARTER
                    let gradeDisplay = '';
                    let gradeColor = '#6c757d';
                    
                    if (selectedQuarter === 'final') {
                        const final = classGrade.final_grade || '-';
                        gradeDisplay = final;
                        gradeColor = final !== '-' && parseFloat(final) >= 75 ? '#28a745' : 
                                    final !== '-' ? '#dc3545' : '#6c757d';
                    } else if (selectedQuarter === '1ST') {
                        const q1 = classGrade.q1 || '-';
                        gradeDisplay = q1;
                        gradeColor = q1 !== '-' && parseFloat(q1) >= 75 ? '#28a745' : 
                                    q1 !== '-' ? '#dc3545' : '#6c757d';
                    } else if (selectedQuarter === '2ND') {
                        const q2 = classGrade.q2 || '-';
                        gradeDisplay = q2;
                        gradeColor = q2 !== '-' && parseFloat(q2) >= 75 ? '#28a745' : 
                                    q2 !== '-' ? '#dc3545' : '#6c757d';
                    }
                    
                    const remarks = classGrade.remarks || '-';
                    const remarksColor = classGrade.remarks === 'PASSED' ? '#28a745' :
                                       classGrade.remarks === 'FAILED' ? '#dc3545' : '#6c757d';
                    
                    classesHtml += `
                        <div class="class-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="class-name">${className}</div>
                                <div>
                                    <span style="color: ${gradeColor}; font-weight: 600; font-size: 1.1rem; margin-right: 1rem;">${gradeDisplay}</span>
                                    ${selectedQuarter === 'final' ? `<span style="color: ${remarksColor}; font-weight: 600;">${remarks}</span>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                classesHtml = `
                    <div class="no-classes">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>No classes enrolled</p>
                    </div>
                `;
            }
            
            const colSpan = 4;
            const detailRow = $(`
                <tr class="classes-detail-row">
                    <td colspan="${colSpan}" class="classes-detail-cell">
                        <div class="classes-container">
                            ${classesHtml}
                        </div>
                    </td>
                </tr>
            `);
            
            mainRow.after(detailRow);
        }
    });

    function updateStudentCount(students) {
        const count = students.length;
        $('#studentCount').text(`${count} Student${count !== 1 ? 's' : ''}`);
    }

    function applyFilters() {
        const searchTerm = $('#studentSearch').val().toLowerCase();
        const remarksFilter = $('#remarksFilter').val();

        filteredStudents = allStudents.filter(student => {
            const matchSearch = !searchTerm || 
                student.student_number.toLowerCase().includes(searchTerm) ||
                student.first_name.toLowerCase().includes(searchTerm) ||
                student.last_name.toLowerCase().includes(searchTerm) ||
                student.full_name.toLowerCase().includes(searchTerm);
            
            let matchRemarks = true;
            if (remarksFilter && selectedClassCode) {
                const classGrade = student.class_grades.find(g => g.class_code === selectedClassCode);
                matchRemarks = classGrade?.remarks === remarksFilter;
            }

            return matchSearch && matchRemarks;
        });

        displaySectionEnrollment(filteredStudents, selectedClassCode, selectedQuarter);
        updateStudentCount(filteredStudents);
    }

    function resetFilters() {
        $('#studentSearch').val('');
        $('#classSearchInput').val('').removeData('selected-class').attr('placeholder', 'All Classes (Overview)');
        $('#remarksFilter').val('');
        selectedClassCode = null;
        
        if (allStudents.length > 0) {
            applyFilters();
        }
    }

    function resetQuarterTabs() {
        $('#quarterTabsSection .nav-link').removeClass('active');
        $('#quarterTabsSection .nav-link[data-quarter="final"]').addClass('active');
        selectedQuarter = 'final';
    }

    $('#classSearchInput').on('click', function() {
        $(this).dropdown('toggle');
    });

    $(document).on('click', '.class-option', function (e) {
        e.preventDefault();

        const classCode = $(this).data('id');
        const className = $(this).text().trim();

        $('#classSearchInput').val(className).data('selected-class', classCode);

        $('#classDropdownList').removeClass('show');
        $('#classDropdownList').parent().removeClass('show');

        selectedClassCode = classCode || null;

        applyFilters();
    });

    $(document).on('click', function (e) {
        const target = $(e.target);
        if (!target.is('#classSearchInput') && target.closest('#classDropdownList').length === 0) {
            $('#classDropdownList').removeClass('show');
            $('#classDropdownList').parent().removeClass('show');
        }
    });

    $('#quarterTabsSection').on('click', '.nav-link', function(e) {
        e.preventDefault();
        
        // Collapse all expanded rows when changing quarter
        $('.expand-btn').removeClass('expanded');
        $('.classes-detail-row').remove();
        
        $('#quarterTabsSection .nav-link').removeClass('active');
        $(this).addClass('active');
        
        selectedQuarter = $(this).data('quarter');
        
        applyFilters();
    });

    $('#studentSearch').on('input', applyFilters);
    $('#remarksFilter').on('change', applyFilters);
    $('#resetFiltersBtn').click(resetFilters);

    function getStatusBadgeClass(status) {
        const badges = {
            'active': 'badge-primary',
            'completed': 'badge-dark',
            'upcoming': 'badge-secondary'
        };
        return badges[status] || 'badge-light';
    }
});