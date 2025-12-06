$(document).ready(function() {
    let loadedStudents = [];

    // =========================================================================
    // SOURCE SECTION - Load sections when strand or level changes
    // =========================================================================
    $('#source_strand, #source_level').on('change', function() {
        const strandId = $('#source_strand').val();
        const levelId = $('#source_level').val();

        if (strandId && levelId) {
            loadSections('source', strandId, levelId);
        }
    });

    // =========================================================================
    // TARGET SECTION - Load sections when strand or level changes
    // =========================================================================
    $('#target_strand, #target_level').on('change', function() {
        const strandId = $('#target_strand').val();
        const levelId = $('#target_level').val();

        if (strandId && levelId) {
            loadSections('target', strandId, levelId);
        }
    });

    function loadSections(type, strandId, levelId) {
        const sectionDropdown = type === 'source' ? '#source_section' : '#target_section';
        const route = type === 'source' ? API_ROUTES.getSourceSections : API_ROUTES.getTargetSections;

        $.ajax({
            url: route,
            type: 'GET',
            data: {
                strand_id: strandId,
                level_id: levelId
            },
            success: function(sections) {
                $(sectionDropdown).html('<option value="" selected disabled>Select Section</option>');
                sections.forEach(function(section) {
                    $(sectionDropdown).append(`<option value="${section.id}">${section.name}</option>`);
                });
            },
            error: function() {
                Swal.fire('Error', 'Failed to load sections', 'error');
            }
        });
    }

    // =========================================================================
    // LOAD STUDENTS FROM SOURCE SECTION
    // =========================================================================
    $('#loadStudentsBtn').on('click', function() {
        const sourceSectionId = $('#source_section').val();
        const sourceSemesterId = $('#source_semester').val();

        if (!sourceSectionId) {
            Swal.fire('Missing Selection', 'Please select a source section first', 'warning');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true);
        $btn.html('<i class="fas fa-spinner fa-spin mr-2"></i> Loading...');

        $.ajax({
            url: API_ROUTES.loadStudents,
            type: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                source_section_id: sourceSectionId,
                source_semester_id: sourceSemesterId
            },
            success: function(response) {
                if (response.success) {
                    loadedStudents = response.students;
                    populateStudentTable(response.students);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Students Loaded',
                        text: `${response.count} student(s) loaded successfully`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to load students', 'error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to load students';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire('Error', errorMsg, 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.html('<i class="fas fa-download mr-2"></i> Load Students');
            }
        });
    });

    // =========================================================================
    // POPULATE STUDENT TABLE
    // =========================================================================
    function populateStudentTable(students) {
        $('#assignmentTableBody').empty();

        if (students.length === 0) {
            $('#assignmentTableBody').html(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-3"></i>
                        <p>No students found in the selected section</p>
                    </td>
                </tr>
            `);
            updateStudentCount();
            return;
        }

        students.forEach(function(student, index) {
            const fullName = `${student.last_name}, ${student.first_name} ${student.middle_name || ''}`.trim();
            const currentSection = student.current_section || 'N/A';
            const currentInfo = student.current_strand && student.current_level 
                ? `${student.current_strand} - ${student.current_level}` 
                : '';

            const studentType = student.student_type || 'regular';
            const typeIcon = studentType === 'regular' ? 'fa-user-check' : 'fa-user-clock';
            const typeText = studentType === 'regular' ? 'Regular' : 'Irregular';

            const row = `
                <tr data-student-number="${student.student_number}" data-selected="true">
                    <td class="text-center align-middle">
                        <input type="checkbox" class="student-checkbox" checked>
                    </td>
                    <td class="text-center align-middle">${index + 1}</td>
                    <td>${student.student_number}</td>
                    <td><strong>${fullName}</strong></td>
                    <td>
                        <small>${currentSection}</small><br>
                        <small class="text-muted">${currentInfo}</small>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-default btn-sm btn-block type-toggle" data-type="${studentType}">
                            <i class="fas ${typeIcon}"></i> ${typeText}
                        </button>
                        <input type="hidden" class="type-input" value="${studentType}">
                    </td>
                </tr>
            `;

            $('#assignmentTableBody').append(row);
        });

        updateStudentCount();
        $('#submitBtn').prop('disabled', false);
    }

    // =========================================================================
    // CHECKBOX HANDLERS
    // =========================================================================
    $('#selectAllCheckbox').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.student-checkbox').prop('checked', isChecked);
        $('.student-checkbox').each(function() {
            $(this).closest('tr').attr('data-selected', isChecked);
        });
        updateStudentCount();
    });

    $(document).on('change', '.student-checkbox', function() {
        const isChecked = $(this).is(':checked');
        $(this).closest('tr').attr('data-selected', isChecked);
        updateStudentCount();
        
        // Update "select all" checkbox
        const totalCheckboxes = $('.student-checkbox').length;
        const checkedCheckboxes = $('.student-checkbox:checked').length;
        $('#selectAllCheckbox').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    $('#selectAllBtn').on('click', function() {
        $('#selectAllCheckbox').prop('checked', true).trigger('change');
    });

    $('#deselectAllBtn').on('click', function() {
        $('#selectAllCheckbox').prop('checked', false).trigger('change');
    });

    // =========================================================================
    // REMOVE SELECTED STUDENTS
    // =========================================================================
    $('#removeSelectedBtn').on('click', function() {
        const selectedCount = $('.student-checkbox:checked').length;
        
        if (selectedCount === 0) {
            Swal.fire('No Selection', 'Please select students to remove', 'warning');
            return;
        }

        Swal.fire({
            title: 'Remove Students?',
            text: `Remove ${selectedCount} selected student(s) from the list?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Remove',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $('.student-checkbox:checked').closest('tr').remove();
                renumberRows();
                updateStudentCount();
                $('#selectAllCheckbox').prop('checked', false);
                
                if ($('#assignmentTableBody tr').length === 0) {
                    $('#assignmentTableBody').html(`
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x mb-3"></i>
                                <p>No students in the list</p>
                            </td>
                        </tr>
                    `);
                    $('#submitBtn').prop('disabled', true);
                }
            }
        });
    });

    // =========================================================================
    // TYPE TOGGLE
    // =========================================================================
    $(document).on('click', '.type-toggle', function() {
        const $btn = $(this);
        const $row = $btn.closest('tr');
        const currentType = $btn.data('type');
        const newType = currentType === 'regular' ? 'irregular' : 'regular';
        const newIcon = newType === 'regular' ? 'fa-user-check' : 'fa-user-clock';
        const newText = newType === 'regular' ? 'Regular' : 'Irregular';
        
        $btn.data('type', newType);
        $btn.html(`<i class="fas ${newIcon}"></i> ${newText}`);
        $row.find('.type-input').val(newType);
    });

    // =========================================================================
    // UTILITY FUNCTIONS
    // =========================================================================
    function renumberRows() {
        $('#assignmentTableBody tr').each(function(index) {
            $(this).find('td:eq(1)').text(index + 1);
        });
    }

    function updateStudentCount() {
        const totalStudents = $('#assignmentTableBody tr').length;
        const selectedStudents = $('.student-checkbox:checked').length;
        
        if (totalStudents > 0 && !$('#assignmentTableBody tr').first().find('.student-checkbox').length) {
            $('#studentCount').text('0 Students');
            return;
        }
        
        $('#studentCount').text(`${selectedStudents} / ${totalStudents} Selected`);
    }

    // =========================================================================
    // FORM SUBMISSION
    // =========================================================================
    $('#assign_section_form').on('submit', function(e) {
        e.preventDefault();

        // Validate target semester and section
        if (!$('#target_semester').val()) {
            Swal.fire('Missing Selection', 'Please select a target semester', 'warning');
            return;
        }

        if (!$('#target_section').val()) {
            Swal.fire('Missing Selection', 'Please select target strand, level, and section', 'warning');
            return;
        }

        // Get selected students
        const students = [];
        $('.student-checkbox:checked').each(function() {
            const $row = $(this).closest('tr');
            students.push({
                student_number: $row.data('student-number'),
                new_section_id: $('#target_section').val(),
                student_type: $row.find('.type-input').val()
            });
        });

        if (students.length === 0) {
            Swal.fire('No Selection', 'Please select at least one student to assign', 'warning');
            return;
        }

        // Confirm assignment
        const sourceSectionName = $('#source_section option:selected').text();
        const targetSectionName = $('#target_section option:selected').text();
        const targetSemesterName = $('#target_semester option:selected').text();

        Swal.fire({
            title: 'Confirm Assignment',
            html: `
                <div class="text-left">
                    <p><strong>Assigning ${students.length} student(s)</strong></p>
                    <hr>
                    <p><strong>From:</strong> ${sourceSectionName}</p>
                    <p><strong>To:</strong> ${targetSectionName}</p>
                    <p><strong>Semester:</strong> ${targetSemesterName}</p>
                    <hr>
                    <p class="text-muted small">This will:</p>
                    <ul class="text-muted small">
                        <li>Update each student's section</li>
                        <li>Enroll them in the target semester</li>
                        <li>Enroll them in all classes for the target section</li>
                    </ul>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Assign Students',
            cancelButtonText: 'Cancel',
            width: '500px'
        }).then((result) => {
            if (result.isConfirmed) {
                processAssignments(students);
            }
        });
    });

    function processAssignments(students) {
        const semesterId = $('#target_semester').val();
        const batchSize = 25;
        const batches = [];
        
        for (let i = 0; i < students.length; i += batchSize) {
            batches.push(students.slice(i, i + batchSize));
        }

        showProcessingModal(batches.length, students.length);
        processBatches(batches, semesterId, 0, students.length, 0);
    }

    function showProcessingModal(totalBatches, totalStudents) {
        Swal.fire({
            title: '<i class="fas fa-spinner fa-spin"></i> Assigning Students',
            html: `
                <div class="mb-3">
                    <h5>Assigning ${totalStudents} student(s)</h5>
                    <p class="text-muted">Processing in ${totalBatches} batch(es)</p>
                </div>
                <div class="progress" style="height: 30px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                         role="progressbar" style="width: 0%">
                        <span id="progressText" class="font-weight-bold">0%</span>
                    </div>
                </div>
                <div class="mt-3">
                    <p id="statusText" class="mb-1">
                        <i class="fas fa-clock"></i> Starting...
                    </p>
                    <small id="detailText" class="text-muted">Batch 0 of ${totalBatches}</small>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });
    }

    function updateProgress(current, total, batchNum, totalBatches) {
        const percentage = Math.round((current / total) * 100);
        $('#progressBar').css('width', percentage + '%');
        $('#progressText').text(percentage + '%');
        $('#statusText').html(`<i class="fas fa-check-circle text-success"></i> Assigned ${current} of ${total} students`);
        $('#detailText').text(`Processing batch ${batchNum} of ${totalBatches}`);
    }

    function processBatches(batches, semesterId, currentBatch, totalStudents, processedCount) {
        if (currentBatch >= batches.length) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                html: `
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4>All ${totalStudents} students assigned successfully!</h4>
                        <p class="text-muted">Redirecting to student list...</p>
                    </div>
                `,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = API_ROUTES.redirectAfterSubmit;
            });
            return;
        }

        const batch = batches[currentBatch];
        const batchNumber = currentBatch + 1;
        
        $.ajax({
            url: API_ROUTES.assignStudents,
            type: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                semester_id: semesterId,
                section_id: $('#target_section').val(),
                students: batch
            },
            success: function(response) {
                processedCount += batch.length;
                const actualProcessed = Math.min(processedCount, totalStudents);
                updateProgress(actualProcessed, totalStudents, batchNumber, batches.length);
                
                setTimeout(function() {
                    processBatches(batches, semesterId, currentBatch + 1, totalStudents, processedCount);
                }, 300);
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while assigning students';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: `
                        <p>${errorMessage}</p>
                        <hr>
                        <p class="text-muted">
                            <strong>Progress:</strong> ${processedCount} of ${totalStudents} students assigned<br>
                            <strong>Failed at:</strong> Batch ${batchNumber} of ${batches.length}
                        </p>
                    `,
                    confirmButtonText: 'OK'
                });
            }
        });
    }
});