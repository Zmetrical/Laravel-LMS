$(document).ready(function () {
    let currentSchoolYear = null;
    let semesters = [];
    let selectedSemesterId = null;
    let selectedClassCode = null;
    let selectedClassName = null;

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

        // Reset selected class
        selectedClassCode = null;
        selectedClassName = null;

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
            
            const row = `
                <tr class="class-row ${isSelected}" style="cursor: pointer;" 
                    data-class-code="${cls.class_code}" 
                    data-class-name="${cls.class_name}">
                    <td><strong>${cls.class_code}</strong></td>
                    <td>${cls.class_name}</td>
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

        // Update class selection highlight
        $('.class-row').removeClass('table-primary');
        $(`.class-row[data-class-code="${classCode}"]`).addClass('table-primary');

        // Update header
        const semester = semesters.find(s => s.id === selectedSemesterId);


        // Show loading and load students
        $('#emptyState').hide();
        $('#studentsContent').hide();
        $('#noStudents').hide();
        $('#studentsLoading').show();

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
                    displayEnrollmentHistory(response.data);
                    $('#studentsContent').show();
                    $('#studentCount').text(`${response.data.length} Student${response.data.length > 1 ? 's' : ''}`);
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

    // Display Enrollment History
    function displayEnrollmentHistory(students) {
        const tbody = $('#studentsTableBody');
        tbody.empty();

        students.forEach(student => {
            const statusBadge = student.enrollment_status === 'enrolled' ? 'badge-success' :
                              student.enrollment_status === 'dropped' ? 'badge-danger' : 'badge-secondary';
            
            const remarksBadge = student.remarks === 'PASSED' ? 'badge-success' :
                               student.remarks === 'FAILED' ? 'badge-danger' : 'badge-warning';

            const row = `
                <tr>
                    <td><strong>${student.student_number}</strong></td>
                    <td>${student.first_name} ${student.last_name}</td>
                    <td>${student.section_name || 'N/A'}</td>
                    <td>
                        <span class="badge ${statusBadge}">${student.enrollment_status}</span>
                    </td>
                    <td class="text-center">
                        ${student.final_grade ? `<strong>${student.final_grade}</strong>` : '-'}
                    </td>
                    <td>
                        ${student.remarks ? `<span class="badge ${remarksBadge}">${student.remarks}</span>` : '-'}
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

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