$(document).ready(function() {
    let selectedStudents = [];
    let studentsData = [];

    // =========================================================================
    // MODE SWITCHING
    // =========================================================================
    
    $('#individualTab').on('click', function() {
        $(this).addClass('active');
        $('#bulkTab').removeClass('active');
        $('#individualMode').show();
        $('#bulkMode').hide();
    });

    $('#bulkTab').on('click', function() {
        $(this).addClass('active');
        $('#individualTab').removeClass('active');
        $('#bulkMode').show();
        $('#individualMode').hide();
    });

    // =========================================================================
    // INDIVIDUAL MODE - LOAD SECTIONS
    // =========================================================================

    $('#sourceStrand, #sourceLevel').on('change', function() {
        loadSourceSections();
    });

    $('#targetStrand, #targetLevel').on('change', function() {
        loadTargetSections();
    });

    function loadSourceSections() {
        const strandId = $('#sourceStrand').val();
        const levelId = $('#sourceLevel').val();

        if (!strandId && !levelId) {
            $('#sourceSection').html('<option value="">All Sections</option>');
            return;
        }

        $.ajax({
            url: API_ROUTES.getSections,
            type: 'GET',
            data: {
                strand_id: strandId,
                level_id: levelId
            },
            success: function(sections) {
                $('#sourceSection').html('<option value="">All Sections</option>');
                sections.forEach(function(section) {
                    $('#sourceSection').append(`<option value="${section.id}">${section.name}</option>`);
                });
            },
            error: function() {
                Swal.fire('Error', 'Failed to load sections', 'error');
            }
        });
    }

    function loadTargetSections() {
        const strandId = $('#targetStrand').val();
        const levelId = $('#targetLevel').val();

        if (!strandId || !levelId) {
            $('#targetSection').html('<option value="">Select Section</option>');
            return;
        }

        $.ajax({
            url: API_ROUTES.getAvailableSections,
            type: 'GET',
            data: {
                strand_id: strandId,
                level_id: levelId
            },
            success: function(sections) {
                $('#targetSection').html('<option value="">Select Section</option>');
                sections.forEach(function(section) {
                    $('#targetSection').append(
                        `<option value="${section.id}">${section.name} (${section.strand_code} - ${section.level_name})</option>`
                    );
                });
            },
            error: function() {
                Swal.fire('Error', 'Failed to load target sections', 'error');
            }
        });
    }

    // =========================================================================
    // INDIVIDUAL MODE - LOAD STUDENTS
    // =========================================================================

    $('#loadStudents').on('click', function() {
        const filters = {
            semester_id: $('#sourceSemester').val(),
            student_type: $('#studentType').val(),
            strand_id: $('#sourceStrand').val(),
            level_id: $('#sourceLevel').val(),
            section_id: $('#sourceSection').val()
        };

        $.ajax({
            url: API_ROUTES.getStudentsByFilter,
            type: 'GET',
            data: filters,
            beforeSend: function() {
                $('#studentsList').html(`
                    <div class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                        <p>Loading students...</p>
                    </div>
                `);
            },
            success: function(students) {
                studentsData = students;
                selectedStudents = [];
                renderStudents(students);
                updateSelectedCount();
            },
            error: function() {
                $('#studentsList').html(`
                    <div class="text-center text-danger py-5">
                        <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                        <p>Failed to load students</p>
                    </div>
                `);
            }
        });
    });

    function renderStudents(students) {
        if (students.length === 0) {
            $('#studentsList').html(`
                <div class="text-center text-muted py-5">
                    <i class="fas fa-user-slash fa-3x mb-3"></i>
                    <p>No students found matching the filters</p>
                </div>
            `);
            return;
        }

        let html = '<div class="row">';
        
        students.forEach(function(student) {
            const fullName = `${student.last_name}, ${student.first_name} ${student.middle_name || ''}`.trim();
            const typeClass = student.student_type === 'regular' ? 'badge-primary' : 'badge-secondary';
            
            html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card student-card" data-student-id="${student.id}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="mb-0">${fullName}</h6>
                                <span class="badge ${typeClass} section-badge">${student.student_type}</span>
                            </div>
                            <p class="text-muted small mb-1">
                                <i class="fas fa-id-card mr-1"></i>${student.student_number}
                            </p>
                            <p class="text-muted small mb-1">
                                <i class="fas fa-bookmark mr-1"></i>${student.strand_code || 'N/A'} - ${student.level_name || 'N/A'}
                            </p>
                            <p class="text-muted small mb-0">
                                <i class="fas fa-users mr-1"></i>${student.section_name || 'No Section'}
                            </p>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        $('#studentsList').html(html);
    }

    // =========================================================================
    // STUDENT SELECTION
    // =========================================================================

    $(document).on('click', '.student-card', function() {
        const studentId = $(this).data('student-id');
        
        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
            selectedStudents = selectedStudents.filter(id => id !== studentId);
        } else {
            $(this).addClass('selected');
            selectedStudents.push(studentId);
        }
        
        updateSelectedCount();
    });

    $('#selectAll').on('click', function() {
        $('.student-card').addClass('selected');
        selectedStudents = studentsData.map(s => s.id);
        updateSelectedCount();
    });

    $('#deselectAll').on('click', function() {
        $('.student-card').removeClass('selected');
        selectedStudents = [];
        updateSelectedCount();
    });

    function updateSelectedCount() {
        $('#selectedCount').text(selectedStudents.length);
    }

    // =========================================================================
    // ASSIGN STUDENTS
    // =========================================================================

    $('#assignStudents').on('click', function() {
        if (selectedStudents.length === 0) {
            Swal.fire('No Selection', 'Please select at least one student', 'warning');
            return;
        }

        const targetSection = $('#targetSection').val();
        const targetSemester = $('#targetSemester').val();

        if (!targetSection || !targetSemester) {
            Swal.fire('Incomplete', 'Please select target semester and section', 'warning');
            return;
        }

        const targetSectionText = $('#targetSection option:selected').text();

        Swal.fire({
            title: 'Confirm Assignment',
            html: `Assign <strong>${selectedStudents.length}</strong> student(s) to:<br><strong>${targetSectionText}</strong>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Assign',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                executeAssignment(selectedStudents, targetSection, targetSemester);
            }
        });
    });

    function executeAssignment(studentIds, sectionId, semesterId) {
        $.ajax({
            url: API_ROUTES.assignStudents,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                student_ids: studentIds,
                new_section_id: sectionId,
                new_semester_id: semesterId
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'Processing',
                    html: '<i class="fas fa-spinner fa-spin fa-3x"></i><br>Assigning students...',
                    allowOutsideClick: false,
                    showConfirmButton: false
                });
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                    timer: 2000
                }).then(() => {
                    $('#loadStudents').trigger('click');
                });
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Failed to assign students'
                });
            }
        });
    }

    // =========================================================================
    // CLEAR FILTERS
    // =========================================================================

    $('#clearFilters').on('click', function() {
        $('#sourceSemester').val($('#sourceSemester option:first').val());
        $('#studentType').val('');
        $('#sourceStrand').val('');
        $('#sourceLevel').val('');
        $('#sourceSection').html('<option value="">All Sections</option>');
        
        $('#studentsList').html(`
            <div class="text-center text-muted py-5">
                <i class="fas fa-users fa-3x mb-3"></i>
                <p>Use filters and click "Load Students" to begin</p>
            </div>
        `);
        
        selectedStudents = [];
        studentsData = [];
        updateSelectedCount();
    });

    // =========================================================================
    // BULK PROMOTION MODE
    // =========================================================================

    $('#loadPromotionSummary').on('click', function() {
        const sourceSemester = $('#bulkSourceSemester').val();
        const sourceStrand = $('#bulkSourceStrand').val();
        const sourceLevel = $('#bulkSourceLevel').val();

        if (!sourceSemester || !sourceStrand || !sourceLevel) {
            Swal.fire('Incomplete', 'Please select source semester, strand, and level', 'warning');
            return;
        }

        $.ajax({
            url: API_ROUTES.getPromotionSummary,
            type: 'GET',
            data: {
                source_semester_id: sourceSemester,
                source_strand_id: sourceStrand,
                source_level_id: sourceLevel
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'Loading',
                    html: '<i class="fas fa-spinner fa-spin fa-3x"></i>',
                    allowOutsideClick: false,
                    showConfirmButton: false
                });
            },
            success: function(summary) {
                Swal.close();
                renderPromotionMapping(summary);
                $('#mappingCard').show();
            },
            error: function() {
                Swal.fire('Error', 'Failed to load promotion summary', 'error');
            }
        });
    });

    function renderPromotionMapping(summary) {
        if (summary.length === 0) {
            $('#mappingTableBody').html('<tr><td colspan="3" class="text-center">No sections found</td></tr>');
            return;
        }

        const targetLevel = $('#bulkTargetLevel').val();
        
        if (!targetLevel) {
            Swal.fire('Select Target Level', 'Please select a target level first', 'warning');
            return;
        }

        // Load target sections
        const sourceStrand = $('#bulkSourceStrand').val();
        
        $.ajax({
            url: API_ROUTES.getAvailableSections,
            type: 'GET',
            data: {
                strand_id: sourceStrand,
                level_id: targetLevel
            },
            success: function(targetSections) {
                let html = '';
                
                summary.forEach(function(item) {
                    html += `
                        <tr>
                            <td>
                                <strong>${item.section_name}</strong>
                                <br><small class="text-muted">${item.section_code}</small>
                            </td>
                            <td>
                                <span class="badge badge-primary">${item.student_count} students</span>
                            </td>
                            <td>
                                <select class="form-control target-section-select" data-source-section="${item.section_id}">
                                    <option value="">-- Skip Section --</option>
                                    ${targetSections.map(ts => 
                                        `<option value="${ts.id}">${ts.name}</option>`
                                    ).join('')}
                                </select>
                            </td>
                        </tr>
                    `;
                });
                
                $('#mappingTableBody').html(html);
            },
            error: function() {
                Swal.fire('Error', 'Failed to load target sections', 'error');
            }
        });
    }

    // =========================================================================
    // EXECUTE BULK PROMOTION
    // =========================================================================

    $('#executeBulkPromotion').on('click', function() {
        const mapping = {};
        let totalStudents = 0;
        
        $('.target-section-select').each(function() {
            const sourceSection = $(this).data('source-section');
            const targetSection = $(this).val();
            
            if (targetSection) {
                mapping[sourceSection] = targetSection;
                const row = $(this).closest('tr');
                const studentCount = parseInt(row.find('.badge-primary').text());
                totalStudents += studentCount;
            }
        });

        if (Object.keys(mapping).length === 0) {
            Swal.fire('No Mapping', 'Please map at least one section', 'warning');
            return;
        }

        Swal.fire({
            title: 'Confirm Bulk Promotion',
            html: `
                Promote <strong>${totalStudents}</strong> regular student(s)<br>
                from <strong>${Object.keys(mapping).length}</strong> section(s)?
                <br><br>
                <small class="text-muted">Irregular students will not be affected</small>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Promote All',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#007bff'
        }).then((result) => {
            if (result.isConfirmed) {
                executeBulkPromotion(mapping);
            }
        });
    });

    function executeBulkPromotion(mapping) {
        $.ajax({
            url: API_ROUTES.bulkPromote,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                source_semester_id: $('#bulkSourceSemester').val(),
                source_strand_id: $('#bulkSourceStrand').val(),
                source_level_id: $('#bulkSourceLevel').val(),
                target_level_id: $('#bulkTargetLevel').val(),
                target_semester_id: $('#bulkTargetSemester').val(),
                section_mapping: mapping
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'Promoting Students',
                    html: '<i class="fas fa-spinner fa-spin fa-3x text-primary"></i><br><br>Please wait...',
                    allowOutsideClick: false,
                    showConfirmButton: false
                });
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Promotion Complete',
                    text: response.message,
                    confirmButtonText: 'OK'
                }).then(() => {
                    $('#mappingCard').hide();
                    $('#bulkSourceStrand').val('');
                    $('#bulkSourceLevel').val('');
                    $('#bulkTargetLevel').val('');
                });
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Promotion Failed',
                    text: xhr.responseJSON?.message || 'An error occurred'
                });
            }
        });
    }
});