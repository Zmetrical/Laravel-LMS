$(document).ready(function() {
    let currentClassId = null;
    let currentTeacher = null;
    let allStudents = [];
    let allSections = [];
    let allTeachers = [];

    // Configure toastr
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 3000
    };

    // Load classes on page load
    loadClasses();

    // ========================================================================
    // LOAD CLASSES
    // ========================================================================
    function loadClasses() {
        showLoader('#classListGroup', 'Loading classes...');
        
        $.ajax({
            url: API_ROUTES.getClasses,
            method: 'GET',
            success: function(response) {
                if (response.success && response.data) {
                    renderClassList(response.data);
                } else {
                    showError('#classListGroup', 'No classes found');
                }
            },
            error: function(xhr) {
                showError('#classListGroup', 'Failed to load classes');
                toastr.error('Could not load classes. Please refresh the page.');
            }
        });
    }

    // ========================================================================
    // RENDER CLASS LIST
    // ========================================================================
    function renderClassList(classes) {
        const container = $('#classListGroup');
        container.empty();

        if (classes.length === 0) {
            container.html(`
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p class="mb-0">No classes found</p>
                </div>
            `);
            return;
        }

        classes.forEach(cls => {
            const teacherInfo = cls.teacher_name && cls.teacher_name.trim() !== ''
                ? `<small class="text-muted d-block"><i class="fas fa-user-tie"></i> ${cls.teacher_name}</small>`
                : `<small class="text-muted d-block"><i class="fas fa-exclamation-circle"></i> No teacher</small>`;
            
            const item = `
                <a href="#" class="list-group-item list-group-item-action class-item" 
                   data-class-id="${cls.id}"
                   data-class-code="${cls.class_code}"
                   data-class-name="${cls.class_name}">
                    <div class="d-flex w-100 justify-content-between align-items-start">
                        <div style="flex: 1;">
                            <h6 class="mb-1"><strong>${cls.class_code}</strong></h6>
                            <p class="mb-1 text-muted small">${cls.class_name}</p>
                            ${teacherInfo}
                        </div>
                        <span class="badge badge-primary badge-pill ml-2">${cls.section_count}</span>
                    </div>
                </a>
            `;
            container.append(item);
        });
    }

    // ========================================================================
    // CLASS ITEM CLICK
    // ========================================================================
    $(document).on('click', '.class-item', function(e) {
        e.preventDefault();
        
        $('.class-item').removeClass('active');
        $(this).addClass('active');
        
        currentClassId = $(this).data('class-id');
        const classCode = $(this).data('class-code');
        const className = $(this).data('class-name');
        
        $('#selectedClassName').html(`<i class="fas fa-book-open"></i> ${className}`);
        $('#selectedClassCode').text(`Code: ${classCode}`);
        
        $('#noClassSelected').hide();
        $('#enrollmentSection').show();
        
        // Reset stats
        $('#teacherNameDisplay').text('None');
        $('#sectionsCountDisplay').text('0');
        $('#totalStudentsDisplay').text('0');
        
        // Load data
        loadClassDetails(currentClassId);
        loadClassStudents(currentClassId);
    });

    // ========================================================================
    // LOAD CLASS DETAILS (Teacher & Sections)
    // ========================================================================
    function loadClassDetails(classId) {
        showLoader('#enrolledSectionsContainer', 'Loading sections...');
        
        const url = API_ROUTES.getClassDetails.replace(':id', classId);
        
        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    currentTeacher = response.teacher;
                    renderTeacherInfo(response.teacher);
                    renderEnrolledSections(response.sections);
                    populateSectionFilter(response.sections);
                }
            },
            error: function(xhr) {
                showError('#enrolledSectionsContainer', 'Failed to load sections');
                toastr.error('Could not load class details');
            }
        });
    }

    // ========================================================================
    // RENDER TEACHER INFO
    // ========================================================================
    function renderTeacherInfo(teacher) {
        if (!teacher || !teacher.first_name) {
            $('#teacherNameDisplay').text('None');
            return;
        }

        const fullName = `${teacher.first_name} ${teacher.last_name}`;
        $('#teacherNameDisplay').text(fullName);
    }

    // ========================================================================
    // RENDER ENROLLED SECTIONS
    // ========================================================================
    function renderEnrolledSections(sections) {
        const container = $('#enrolledSectionsContainer');
        $('#sectionsCountDisplay').text(sections.length);
        
        if (sections.length === 0) {
            container.html(`
                <div class="mt-2">
                    <span class="text-muted"><i class="fas fa-inbox"></i> No sections enrolled</span>
                </div>
            `);
            return;
        }

        let html = '<div class="mt-2"><strong><i class="fas fa-layer-group"></i> Sections:</strong> ';
        sections.forEach((section, index) => {
            html += `<span class="badge badge-secondary">${section.name} (${section.student_count})</span> `;
        });
        html += '</div>';
        
        container.html(html);
    }

    // ========================================================================
    // POPULATE SECTION FILTER
    // ========================================================================
    function populateSectionFilter(sections) {
        const select = $('#sectionFilter');
        select.find('option:not(:first)').remove();
        
        sections.forEach(section => {
            select.append(`<option value="${section.id}">${section.name}</option>`);
        });
    }

    // ========================================================================
    // LOAD CLASS STUDENTS
    // ========================================================================
    function loadClassStudents(classId) {
        showLoader('#enrolledStudentsBody', 'Loading students...', 6);
        
        const url = API_ROUTES.getClassStudents.replace(':id', classId);
        
        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    allStudents = response.data || [];
                    allSections = response.sections || [];
                    renderStudents(allStudents);
                    $('#totalStudentsDisplay').text(allStudents.length);
                } else {
                    showError('#enrolledStudentsBody', response.message || 'Failed to load students', 6);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to load students';
                showError('#enrolledStudentsBody', message, 6);
                toastr.error(message);
            }
        });
    }

    // ========================================================================
    // RENDER STUDENTS
    // ========================================================================
    function renderStudents(students) {
        const tbody = $('#enrolledStudentsBody');
        tbody.empty();
        
        $('#studentsCount').text(`${students.length} ${students.length === 1 ? 'Student' : 'Students'}`);

        if (students.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">No students found</p>
                    </td>
                </tr>
            `);
            return;
        }

        students.forEach(student => {
            const typeBadge = student.student_type === 'irregular' 
                ? '<span class="badge badge-secondary">Irregular</span>'
                : '<span class="badge badge-primary">Regular</span>';
            
            const row = `
                <tr>
                    <td>${student.student_number}</td>
                    <td><strong>${student.last_name}, ${student.first_name}</strong></td>
                    <td>${student.level_name || 'N/A'}</td>
                    <td>${student.strand_name || 'N/A'}</td>
                    <td>${student.section_name || 'N/A'}</td>
                    <td class="text-center">${typeBadge}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // ========================================================================
    // APPLY FILTERS
    // ========================================================================
    function applyFilters() {
        const searchTerm = $('#studentSearch').val().toLowerCase();
        const sectionFilter = $('#sectionFilter').val();
        const typeFilter = $('#studentTypeFilter').val();

        const filtered = allStudents.filter(student => {
            const matchSearch = !searchTerm || 
                student.student_number.toLowerCase().includes(searchTerm) ||
                student.first_name.toLowerCase().includes(searchTerm) ||
                student.last_name.toLowerCase().includes(searchTerm);
            
            const matchSection = !sectionFilter || student.section_id == sectionFilter;
            const matchType = !typeFilter || student.student_type === typeFilter;

            return matchSearch && matchSection && matchType;
        });

        renderStudents(filtered);
    }

    // Filter events
    $('#studentSearch, #sectionFilter, #studentTypeFilter').on('input change', applyFilters);

    // Reset filters
    $('#resetFiltersBtn').click(function() {
        $('#studentSearch').val('');
        $('#sectionFilter').val('');
        $('#studentTypeFilter').val('');
        renderStudents(allStudents);
    });

    // ========================================================================
    // SEARCH CLASSES
    // ========================================================================
    $('#classSearch').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.class-item').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(searchTerm));
        });
    });

    // ========================================================================
    // ASSIGN TEACHER MODAL
    // ========================================================================
    $('#assignTeacherBtn').click(function() {
        loadTeachersForModal();
        $('#assignTeacherModal').modal('show');
    });

    function loadTeachersForModal() {
        const select = $('#teacherSelect');
        select.html('<option value="">Loading teachers...</option>').prop('disabled', true);
        
        // Show current teacher info
        if (currentTeacher && currentTeacher.first_name) {
            $('#currentTeacherNameModal').text(`${currentTeacher.first_name} ${currentTeacher.last_name}`);
            $('#currentTeacherEmail').text(currentTeacher.email);
            $('#currentTeacherPhone').text(currentTeacher.phone);
            $('#currentTeacherSection').show();
        } else {
            $('#currentTeacherSection').hide();
        }
        
        $.ajax({
            url: API_ROUTES.getTeachers,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    allTeachers = response.data;
                    select.empty().prop('disabled', false);
                    select.append('<option value="">-- Select Teacher --</option>');
                    
                    response.data.forEach(teacher => {
                        select.append(`
                            <option value="${teacher.id}">
                                ${teacher.first_name} ${teacher.last_name} - ${teacher.email}
                            </option>
                        `);
                    });
                }
            },
            error: function() {
                select.html('<option value="">Failed to load teachers</option>');
                toastr.error('Could not load teachers list');
            }
        });
    }

    // ========================================================================
    // CONFIRM ASSIGN TEACHER
    // ========================================================================
    $('#confirmAssignBtn').click(function() {
        const teacherId = $('#teacherSelect').val();
        
        if (!teacherId) {
            toastr.warning('Please select a teacher');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Assigning...');

        $.ajax({
            url: API_ROUTES.assignTeacher,
            method: 'POST',
            data: {
                class_id: currentClassId,
                teacher_id: teacherId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#assignTeacherModal').modal('hide');
                    loadClassDetails(currentClassId);
                    loadClasses(); // Refresh class list
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to assign teacher');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-check"></i> Assign Teacher');
            }
        });
    });

    // ========================================================================
    // REMOVE TEACHER
    // ========================================================================
    $(document).on('click', '#removeTeacherBtn', function() {
        if (!confirm('Are you sure you want to remove this teacher from the class?')) {
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Removing...');

        $.ajax({
            url: API_ROUTES.removeTeacher,
            method: 'POST',
            data: {
                class_id: currentClassId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#assignTeacherModal').modal('hide');
                    loadClassDetails(currentClassId);
                    loadClasses(); // Refresh class list
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to remove teacher');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-times"></i> Remove Teacher');
            }
        });
    });

    // ========================================================================
    // UTILITY FUNCTIONS
    // ========================================================================
    function showLoader(selector, message = 'Loading...', colspan = 1) {
        const loader = colspan > 1 
            ? `<tr><td colspan="${colspan}" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 mb-0">${message}</p>
               </td></tr>`
            : `<div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 mb-0">${message}</p>
               </div>`;
        
        $(selector).html(loader);
    }

    function showError(selector, message, colspan = 1) {
        const error = colspan > 1
            ? `<tr><td colspan="${colspan}" class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p class="mb-0">${message}</p>
               </td></tr>`
            : `<div class="text-center py-3 text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p class="mb-0">${message}</p>
               </div>`;
        
        $(selector).html(error);
    }
});