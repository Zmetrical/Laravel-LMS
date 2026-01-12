$(document).ready(function () {
    let currentSchoolYear = null;
    let semesters = [];
    let quarters = [];
    let selectedSemesterId = null;
    let selectedClassCode = null;
    let selectedClassName = null;
    let selectedQuarter = 'final';
    let allStudents = [];
    let allSections = [];

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

        // Click handlers
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

        // Reset selected class and quarter
        selectedClassCode = null;
        selectedClassName = null;
        selectedQuarter = 'final';
        allStudents = [];
        allSections = [];
        quarters = [];

        // Hide filters and quarter tabs
        $('#filtersSection').hide();
        $('#quarterTabsSection').hide();
        resetFilters();
        resetQuarterTabs();

        // Show classes card and hide students
        $('#classesCard').show();
        $('#emptyState').show();
        $('#studentsContent').hide();
        $('#noStudents').hide();
        $('#studentsLoading').hide();

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
            const isSelected = cls.class_code === selectedClassCode ? 'table-primary' : '';
            const teacherDisplay = cls.teachers || '<em class="text-muted">No teacher assigned</em>';
            
            const row = `
                <tr class="class-row ${isSelected}" style="cursor: pointer;" 
                    data-class-code="${cls.class_code}" 
                    data-class-name="${cls.class_name}">
                    <td>
                        <strong>${cls.class_name}</strong>
                    </td>
                    <td>
                        <small class="text-muted">
                            <i class="fas fa-chalkboard-teacher"></i> ${teacherDisplay}
                        </small>
                    </td>

                    <td class="text-center">
                        <span class="badge badge-primary">${cls.student_count || 0}</span>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        // Click handler for class rows
        tbody.find('.class-row').click(function () {
            const classCode = $(this).data('class-code');
            const className = $(this).data('class-name');
            selectClass(classCode, className);
        });
    }

    // Select Class
    function selectClass(classCode, className) {
        selectedClassCode = classCode;
        selectedClassName = className;
        selectedQuarter = 'final';

        // Update class selection highlight
        $('.class-row').removeClass('table-primary');
        $(`.class-row[data-class-code="${classCode}"]`).addClass('table-primary');

        // Show loading and load students
        $('#emptyState').hide();
        $('#studentsContent').hide();
        $('#noStudents').hide();
        $('#studentsLoading').show();
        $('#filtersSection').hide();
        $('#quarterTabsSection').hide();
        resetQuarterTabs();

        loadEnrollmentHistory(classCode);
    }

    // Load Enrollment History
    function loadEnrollmentHistory(classCode) {
        const url = API_ROUTES.getEnrollmentHistory
            .replace(':semesterId', selectedSemesterId)
            .replace(':classCode', classCode);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                $('#studentsLoading').hide();
                
                if (response.success && response.data.length > 0) {
                    allStudents = response.data;
                    quarters = response.quarters || [];
                    
                    // Extract unique sections
                    allSections = [...new Set(allStudents.map(s => s.section_name).filter(Boolean))];
                    populateSectionFilter(allSections);
                    
                    // Build quarter tabs dynamically
                    buildQuarterTabs(quarters);
                    
                    displayEnrollmentHistory(allStudents, selectedQuarter);
                    $('#studentsContent').show();
                    $('#filtersSection').show();
                    $('#quarterTabsSection').show();
                    updateStudentCount(allStudents);
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

    // Build Quarter Tabs
    function buildQuarterTabs(quartersData) {
        const tabsContainer = $('#quarterTabsSection .quarter-tabs');
        tabsContainer.empty();

        // Final Grade Tab (Always first)
        tabsContainer.append(`
            <li class="nav-item">
                <a class="nav-link active" href="#" data-quarter="final">
                    <i class="fas fa-trophy"></i> Final Grade
                </a>
            </li>
        `);

        // Dynamic Quarter Tabs
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

    // Populate Section Filter
    function populateSectionFilter(sections) {
        const select = $('#sectionFilter');
        select.find('option:not(:first)').remove();
        
        sections.forEach(section => {
            select.append(`<option value="${section}">${section}</option>`);
        });
    }

    // Display Enrollment History
    function displayEnrollmentHistory(students, quarter) {
        const tbody = $('#studentsTableBody');
        tbody.empty();

        if (students.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">No students found</p>
                    </td>
                </tr>
            `);
            return;
        }

        students.forEach(student => {
            const statusBadge = student.student_type === 'regular' ? 'badge-primary' :
                              student.student_type === 'irregular' ? 'badge-secondary' : 'badge-secondary';
            
            let displayGrade = '-';
            let displayRemarks = '-';
            
            // Determine which grade to display based on selected quarter
            if (quarter === 'final') {
                displayGrade = student.final_grade || '-';
                displayRemarks = student.remarks || '-';
            } else if (quarter === '1ST') {
                displayGrade = student.q1_transmuted || student.q1_grade || '-';
                displayRemarks = displayGrade !== '-' && displayGrade >= 75 ? 'PASSED' : 
                                displayGrade !== '-' ? 'FAILED' : '-';
            } else if (quarter === '2ND') {
                displayGrade = student.q2_transmuted || student.q2_grade || '-';
                displayRemarks = displayGrade !== '-' && displayGrade >= 75 ? 'PASSED' : 
                                displayGrade !== '-' ? 'FAILED' : '-';
            }
            
            const remarksBadge = displayRemarks === 'PASSED' ? 'badge-success' :
                               displayRemarks === 'FAILED' ? 'badge-danger' : 
                               displayRemarks === 'INC' ? 'badge-warning' : 'badge-secondary';

            const row = `
                <tr>
                    <td><strong>${student.student_number}</strong></td>
                    <td>${student.first_name} ${student.last_name}</td>
                    <td>${student.section_name || 'N/A'}</td>
                    <td>
                        <span class="badge ${statusBadge}">${student.student_type}</span>
                    </td>
                    <td class="text-center">
                        ${displayGrade !== '-' ? `<strong>${displayGrade}</strong>` : '-'}
                    </td>
                    <td>
                        ${displayRemarks !== '-' ? `<span class="badge ${remarksBadge}">${displayRemarks}</span>` : '-'}
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Update Student Count
    function updateStudentCount(students) {
        const count = students.length;
        $('#studentCount').text(`${count} Student${count !== 1 ? 's' : ''}`);
    }

    // Apply Filters
    function applyFilters() {
        const searchTerm = $('#studentSearch').val().toLowerCase();
        const sectionFilter = $('#sectionFilter').val();
        const remarksFilter = $('#remarksFilter').val();

        const filtered = allStudents.filter(student => {
            const matchSearch = !searchTerm || 
                student.student_number.toLowerCase().includes(searchTerm) ||
                student.first_name.toLowerCase().includes(searchTerm) ||
                student.last_name.toLowerCase().includes(searchTerm);
            
            const matchSection = !sectionFilter || student.section_name === sectionFilter;
            
            // Adjust remarks filter based on selected quarter
            let studentRemarks = '-';
            if (selectedQuarter === 'final') {
                studentRemarks = student.remarks || '-';
            } else if (selectedQuarter === '1ST') {
                const grade = student.q1_transmuted || student.q1_grade;
                studentRemarks = grade && grade >= 75 ? 'PASSED' : grade ? 'FAILED' : '-';
            } else if (selectedQuarter === '2ND') {
                const grade = student.q2_transmuted || student.q2_grade;
                studentRemarks = grade && grade >= 75 ? 'PASSED' : grade ? 'FAILED' : '-';
            }
            
            const matchRemarks = !remarksFilter || studentRemarks === remarksFilter;

            return matchSearch && matchSection && matchRemarks;
        });

        displayEnrollmentHistory(filtered, selectedQuarter);
        updateStudentCount(filtered);
    }

    // Reset Filters
    function resetFilters() {
        $('#studentSearch').val('');
        $('#sectionFilter').val('');
        $('#remarksFilter').val('');
        
        if (allStudents.length > 0) {
            displayEnrollmentHistory(allStudents, selectedQuarter);
            updateStudentCount(allStudents);
        }
    }

    // Reset Quarter Tabs
    function resetQuarterTabs() {
        $('#quarterTabsSection .nav-link').removeClass('active');
        $('#quarterTabsSection .nav-link[data-quarter="final"]').addClass('active');
        selectedQuarter = 'final';
    }

    // Quarter Tab Click Handler
    $('#quarterTabsSection').on('click', '.nav-link', function(e) {
        e.preventDefault();
        
        $('#quarterTabsSection .nav-link').removeClass('active');
        $(this).addClass('active');
        
        selectedQuarter = $(this).data('quarter');
        
        // Re-display students with selected quarter
        applyFilters();
        
        // Update column header based on quarter
        const gradeHeader = selectedQuarter === 'final' ? 'Final Grade' :
                           selectedQuarter === '1ST' ? '1st Quarter' :
                           selectedQuarter === '2ND' ? '2nd Quarter' : 'Grade';
        
        $('#studentsTableBody').closest('table').find('thead th:eq(4)').text(gradeHeader);
    });

    // Filter Event Handlers
    $('#studentSearch, #sectionFilter, #remarksFilter').on('input change', applyFilters);
    $('#resetFiltersBtn').click(resetFilters);

    // Get Status Badge Class
    function getStatusBadgeClass(status) {
        const badges = {
            'active': 'badge-primary',
            'completed': 'badge-dark',
            'upcoming': 'badge-secondary'
        };
        return badges[status] || 'badge-light';
    }
});