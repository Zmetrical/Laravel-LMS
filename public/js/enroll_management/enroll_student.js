$(document).ready(function() {
    let students = [];
    let currentStudentId = null;
    let availableClasses = [];
    let enrolledClasses = [];

    // Load initial data
    loadLevelsAndStrands();
    loadStudents();

    // Load levels and strands for filters
    function loadLevelsAndStrands() {
        $.ajax({
            url: API_ROUTES.getLevels,
            method: 'GET',
            success: function(response) {
                $('#irregularGradeFilter').append(
                    response.data.map(level => 
                        `<option value="${level.id}">${level.name}</option>`
                    ).join('')
                );
            }
        });

        $.ajax({
            url: API_ROUTES.getStrands,
            method: 'GET',
            success: function(response) {
                $('#irregularStrandFilter').append(
                    response.data.map(strand => 
                        `<option value="${strand.id}">${strand.name}</option>`
                    ).join('')
                );
            }
        });
    }

    // Load students
    function loadStudents() {
        $('#loadingIndicator').show();
        $('#irregularStudentsContainer').empty();

        $.ajax({
            url: API_ROUTES.getStudents,
            method: 'GET',
            success: function(response) {
                students = response.data;
                renderStudents(students);
            },
            error: function() {
                showToast('error', 'Failed to load students');
            },
            complete: function() {
                $('#loadingIndicator').hide();
            }
        });
    }

    // Render students as cards
    function renderStudents(data) {
        const container = $('#irregularStudentsContainer');
        container.empty();

        if (data.length === 0) {
            container.html(`
                <div class="col-12">
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i> No irregular students found.
                    </div>
                </div>
            `);
            return;
        }

        data.forEach(student => {
            const card = `
                <div class="col-md-4 col-sm-6 mb-3 student-card" 
                     data-level="${student.level_id}" 
                     data-strand="${student.strand_id}"
                     data-status="${student.class_count > 0 ? 'enrolled' : 'not_enrolled'}">
                    <div class="card card-outline ${student.class_count > 0 ? 'card-info' : 'card-secondary'}">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-user"></i> 
                                <strong>${student.last_name}, ${student.first_name}</strong>
                            </h3>
                            <div class="card-tools">
                                <span class="badge ${student.class_count > 0 ? 'badge-info' : 'badge-secondary'}">
                                    ${student.class_count} Classes
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Student No:</dt>
                                <dd class="col-sm-7">${student.student_number}</dd>
                                
                                <dt class="col-sm-5">Grade Level:</dt>
                                <dd class="col-sm-7">${student.level_name}</dd>
                                
                                <dt class="col-sm-5">Strand:</dt>
                                <dd class="col-sm-7">${student.strand_name}</dd>
                                
                                <dt class="col-sm-5">Section:</dt>
                                <dd class="col-sm-7">${student.section_name || 'N/A'}</dd>
                            </dl>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-info btn-sm btn-block manage-classes-btn" 
                                    data-id="${student.id}"
                                    data-name="${student.last_name}, ${student.first_name} ${student.middle_name}"
                                    data-number="${student.student_number}"
                                    data-level="${student.level_name}"
                                    data-strand="${student.strand_name}">
                                <i class="fas fa-book"></i> Manage Classes
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.append(card);
        });
    }

    // Open class management modal
    $(document).on('click', '.manage-classes-btn', function() {
        currentStudentId = $(this).data('id');
        $('#modalStudentName').text($(this).data('name'));
        $('#modalStudentNumber').text($(this).data('number'));
        $('#modalStudentLevel').text($(this).data('level'));
        $('#modalStudentStrand').text($(this).data('strand'));
        
        loadStudentClasses(currentStudentId);
        $('#studentClassModal').modal('show');
    });

    // Load student's classes
    function loadStudentClasses(studentId) {
        const url = API_ROUTES.getStudentClasses.replace(':id', studentId);
        
        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                availableClasses = response.available;
                enrolledClasses = response.enrolled;
                
                renderAvailableClasses();
                renderEnrolledClasses();
                updateEnrolledCount();
            },
            error: function() {
                showToast('error', 'Failed to load classes');
            }
        });
    }

    // Render available classes
    function renderAvailableClasses() {
        const tbody = $('#availableClassesBody');
        tbody.empty();

        if (availableClasses.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        <i class="fas fa-info-circle"></i> No available classes
                    </td>
                </tr>
            `);
            return;
        }

        availableClasses.forEach(cls => {
            tbody.append(`
                <tr class="available-class-row">
                    <td class="text-center">
                        <input type="checkbox" class="available-class-check" value="${cls.id}">
                    </td>
                    <td>${cls.class_code}</td>
                    <td>${cls.class_name}</td>
                    <td>${cls.teacher_name || 'Unassigned'}</td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-info enroll-single-btn" data-class-id="${cls.id}">
                            <i class="fas fa-plus"></i> Enroll
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    // Render enrolled classes
    function renderEnrolledClasses() {
        const tbody = $('#enrolledClassesBody');
        tbody.empty();

        if (enrolledClasses.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="4" class="text-center text-muted">
                        <i class="fas fa-info-circle"></i> No enrolled classes yet
                    </td>
                </tr>
            `);
            return;
        }

        enrolledClasses.forEach(cls => {
            tbody.append(`
                <tr>
                    <td>${cls.class_code}</td>
                    <td>${cls.class_name}</td>
                    <td>${cls.teacher_name || 'Unassigned'}</td>
                    <td class="text-center">
                        <button class="btn btn-xs btn-danger unenroll-btn" data-class-id="${cls.id}">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    // Update enrolled count badge
    function updateEnrolledCount() {
        $('#modalEnrolledCount').text(enrolledClasses.length);
    }

    // Enroll single class
    $(document).on('click', '.enroll-single-btn', function() {
        const classId = $(this).data('class-id');
        enrollClasses([classId]);
    });

    // Unenroll class
    $(document).on('click', '.unenroll-btn', function() {
        const classId = $(this).data('class-id');
        
        if (confirm('Are you sure you want to remove this class?')) {
            unenrollClass(classId);
        }
    });

    // Enroll multiple classes
    function enrollClasses(classIds) {
        $.ajax({
            url: API_ROUTES.enrollClass,
            method: 'POST',
            data: {
                student_id: currentStudentId,
                class_ids: classIds,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showToast('success', response.message);
                loadStudentClasses(currentStudentId);
                loadStudents(); // Refresh main list
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'Enrollment failed');
            }
        });
    }

    // Unenroll class
    function unenrollClass(classId) {
        $.ajax({
            url: API_ROUTES.unenrollClass,
            method: 'POST',
            data: {
                student_id: currentStudentId,
                class_id: classId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showToast('success', response.message);
                loadStudentClasses(currentStudentId);
                loadStudents(); // Refresh main list
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'Unenrollment failed');
            }
        });
    }

    // Search available classes
    $('#availableClassSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.available-class-row').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });

    // Filter students
    function applyFilters() {
        const searchTerm = $('#irregularSearchInput').val().toLowerCase();
        const levelFilter = $('#irregularGradeFilter').val();
        const strandFilter = $('#irregularStrandFilter').val();
        const statusFilter = $('#enrollmentStatusFilter').val();

        $('.student-card').each(function() {
            const card = $(this);
            const cardText = card.text().toLowerCase();
            const level = card.data('level');
            const strand = card.data('strand');
            const status = card.data('status');

            const matchSearch = !searchTerm || cardText.includes(searchTerm);
            const matchLevel = !levelFilter || level == levelFilter;
            const matchStrand = !strandFilter || strand == strandFilter;
            const matchStatus = !statusFilter || status === statusFilter;

            card.toggle(matchSearch && matchLevel && matchStrand && matchStatus);
        });
    }

    // Filter event listeners
    $('#irregularSearchInput, #irregularGradeFilter, #irregularStrandFilter, #enrollmentStatusFilter')
        .on('change keyup', applyFilters);

    // Reset filters
    $('#resetFiltersBtn').click(function() {
        $('#irregularSearchInput').val('');
        $('#irregularGradeFilter').val('');
        $('#irregularStrandFilter').val('');
        $('#enrollmentStatusFilter').val('');
        $('.student-card').show();
    });

    // Toast notification
    function showToast(type, message) {
        const icon = type === 'success' ? 'check' : 'exclamation-triangle';
        const bgColor = type === 'success' ? 'bg-success' : 'bg-danger';
        
        $(document).Toasts('create', {
            class: bgColor,
            title: type === 'success' ? 'Success' : 'Error',
            body: message,
            icon: `fas fa-${icon}`,
            autohide: true,
            delay: 3000
        });
    }
});