$(document).ready(function() {
    let loadedStudents = [];

    // =========================================================================
    // SELECT2 INITIALIZATION
    // =========================================================================
    
// Source Section Select2
$('#source_section').select2({
    theme: 'bootstrap4',
    placeholder: 'Search for section...',
    allowClear: true,
    ajax: {
        url: API_ROUTES.searchSections,
        dataType: 'json',
        delay: 250,
        data: function(params) {
            return {
                search: params.term
            };
        },
        processResults: function(data) {
            return {
                results: data.map(function(section) {
                    return {
                        id: section.id,
                        text: section.name + ' (' + section.code + ')'
                    };
                })
            };
        },
        cache: true
    },
    minimumInputLength: 0
}).on('select2:select', function(e) {
    // Auto-load students when section is selected
    const sourceSectionId = e.params.data.id;
    const sourceSemesterId = $('#source_semester').val();
    
    loadStudentsFromSection(sourceSectionId, sourceSemesterId);
}).on('select2:clear', function() {
    // Clear table when section is cleared
    $('#assignmentTableBody').html(`
        <tr>
            <td colspan="6" class="text-center text-muted py-5">
                <i class="fas fa-arrow-left fa-2x mb-3"></i>
                <p>Select a source section or search for a student to begin</p>
            </td>
        </tr>
    `);
    updateStudentCount();
    $('#submitBtn').prop('disabled', true);
});

// Target Section Select2
$('#target_section').select2({
    theme: 'bootstrap4',
    placeholder: 'Search for section...',
    allowClear: true,
    ajax: {
        url: API_ROUTES.searchSections,
        dataType: 'json',
        delay: 250,
        data: function(params) {
            return {
                search: params.term
            };
        },
        processResults: function(data) {
            return {
                results: data.map(function(section) {
                    return {
                        id: section.id,
                        text: section.name + ' (' + section.code + ')'
                    };
                })
            };
        },
        cache: true
    },
    minimumInputLength: 0
});


$('#source_student').select2({
    theme: 'bootstrap4',
    placeholder: 'Type student number or name...',
    allowClear: true,
    ajax: {
        url: API_ROUTES.searchStudents,
        dataType: 'json',
        delay: 250,
        data: function(params) {
            return {
                search: params.term
            };
        },
        processResults: function(data) {
            if (!data.success) {
                return { results: [] };
            }
            return {
                results: data.students.map(function(student) {
                    const fullName = student.last_name + ', ' + student.first_name + ' ' + (student.middle_name || '');
                    return {
                        id: student.student_number,
                        text: student.student_number + ' - ' + fullName,
                        student: student
                    };
                })
            };
        },
        cache: true
    },
    minimumInputLength: 2
});

// =========================================================================
// LOAD STUDENTS FROM SECTION FUNCTION
// =========================================================================
function loadStudentsFromSection(sourceSectionId, sourceSemesterId) {
    // Show loading state
    $('#assignmentTableBody').html(`
        <tr>
            <td colspan="6" class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                <p>Loading students...</p>
            </td>
        </tr>
    `);

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
                
                if (response.count > 0) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Students Loaded',
                        text: `${response.count} student(s) loaded successfully`,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            } else {
                Swal.fire('Error', response.message || 'Failed to load students', 'error');
                $('#assignmentTableBody').html(`
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <p>Failed to load students</p>
                        </td>
                    </tr>
                `);
            }
        },
        error: function(xhr) {
            let errorMsg = 'Failed to load students';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            Swal.fire('Error', errorMsg, 'error');
            $('#assignmentTableBody').html(`
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                        <p>Failed to load students</p>
                    </td>
                </tr>
            `);
        }
    });
}
    // =========================================================================
    // SOURCE TYPE TOGGLE
    // =========================================================================
    $('#sourceSectionBtn').on('click', function() {
        $(this).addClass('active btn-secondary').removeClass('btn-default');
        $('#sourceStudentBtn').removeClass('active btn-secondary').addClass('btn-default');
        $('#sourceSectionGroup').show();
        $('#sourceStudentGroup').hide();
    });

    $('#sourceStudentBtn').on('click', function() {
        $(this).addClass('active btn-secondary').removeClass('btn-default');
        $('#sourceSectionBtn').removeClass('active btn-secondary').addClass('btn-default');
        $('#sourceStudentGroup').show();
        $('#sourceSectionGroup').hide();
    });

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
    // ADD INDIVIDUAL STUDENT
    // =========================================================================
    $('#addStudentBtn').on('click', function() {
        const selectedData = $('#source_student').select2('data');
        
        if (!selectedData || selectedData.length === 0 || !selectedData[0].id) {
            Swal.fire('Missing Selection', 'Please search and select a student first', 'warning');
            return;
        }

        const student = selectedData[0].student;

        // Check if student already exists in table
        const exists = $(`#assignmentTableBody tr[data-student-number="${student.student_number}"]`).length > 0;
        if (exists) {
            Swal.fire('Already Added', 'This student is already in the list', 'info');
            return;
        }

        // Add student to loaded students array
        loadedStudents.push(student);
        
        // Add row to table
        addStudentRow(student);
        
        // Clear selection
        $('#source_student').val(null).trigger('change');
        
        Swal.fire({
            icon: 'success',
            title: 'Student Added',
            text: 'Student added to the list',
            timer: 1500,
            showConfirmButton: false
        });

        updateStudentCount();
        $('#submitBtn').prop('disabled', false);
    });

    // =========================================================================
    // ADD STUDENT ROW
    // =========================================================================
    function addStudentRow(student) {
        const fullName = `${student.last_name}, ${student.first_name} ${student.middle_name || ''}`.trim();
        const currentSection = student.current_section || 'N/A';
        const currentInfo = student.current_strand && student.current_level 
            ? `${student.current_strand} - ${student.current_level}` 
            : '';

        const studentType = student.student_type || 'regular';
        const typeIcon = studentType === 'regular' ? 'fa-user-check' : 'fa-user-clock';
        const typeText = studentType === 'regular' ? 'Regular' : 'Irregular';

        // Remove empty state if exists
        if ($('#assignmentTableBody tr').first().find('.student-checkbox').length === 0) {
            $('#assignmentTableBody').empty();
        }

        const rowCount = $('#assignmentTableBody tr').length + 1;

        const row = `
            <tr data-student-number="${student.student_number}" data-selected="true">
                <td class="text-center align-middle">
                    <input type="checkbox" class="student-checkbox" checked>
                </td>
                <td class="text-center align-middle">${rowCount}</td>
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
    }

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
    // TABLE SEARCH
    // =========================================================================
    $('#tableSearchInput').on('keyup', function() {
        const searchValue = $(this).val().toLowerCase();
        
        $('#assignmentTableBody tr').each(function() {
            const $row = $(this);
            if ($row.find('.student-checkbox').length === 0) return; // Skip empty state row
            
            const text = $row.text().toLowerCase();
            if (text.indexOf(searchValue) > -1) {
                $row.show();
            } else {
                $row.hide();
            }
        });
    });

    $('#clearTableSearchBtn').on('click', function() {
        $('#tableSearchInput').val('');
        $('#assignmentTableBody tr').show();
    });

    // =========================================================================
    // CHECKBOX HANDLERS
    // =========================================================================
    $('#selectAllCheckbox').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.student-checkbox:visible').prop('checked', isChecked);
        $('.student-checkbox:visible').each(function() {
            $(this).closest('tr').attr('data-selected', isChecked);
        });
        updateStudentCount();
    });

    $(document).on('change', '.student-checkbox', function() {
        const isChecked = $(this).is(':checked');
        $(this).closest('tr').attr('data-selected', isChecked);
        updateStudentCount();
        
        const totalCheckboxes = $('.student-checkbox:visible').length;
        const checkedCheckboxes = $('.student-checkbox:visible:checked').length;
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
            if ($(this).find('.student-checkbox').length > 0) {
                $(this).find('td:eq(1)').text(index + 1);
            }
        });
    }

    function updateStudentCount() {
        const totalStudents = $('#assignmentTableBody tr').filter(function() {
            return $(this).find('.student-checkbox').length > 0;
        }).length;
        const selectedStudents = $('.student-checkbox:checked').length;
        
        if (totalStudents === 0) {
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

        if (!$('#target_semester').val()) {
            Swal.fire('Missing Selection', 'Please select a target semester', 'warning');
            return;
        }

        if (!$('#target_section').val()) {
            Swal.fire('Missing Selection', 'Please select a target section', 'warning');
            return;
        }

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

        const targetSectionText = $('#target_section option:selected').text() || $('#target_section').select2('data')[0].text;
        const targetSemesterName = $('#target_semester option:selected').text();

        Swal.fire({
            title: 'Confirm Assignment',
            html: `
                <div class="text-left">
                    <p><strong>Assigning ${students.length} student(s)</strong></p>
                    <hr>
                    <p><strong>To:</strong> ${targetSectionText}</p>
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