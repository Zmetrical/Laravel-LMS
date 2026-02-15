$(document).ready(function() {
    let loadedStudents = [];
    let allStudents = []; // Store all loaded students from semester
    let sourceStrandId = null;
    let sourceLevelId = null;
    let targetSectionCapacity = null;
    let targetSectionEnrolled = null;

    // =========================================================================
    // TOAST CONFIGURATION
    // =========================================================================
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    // =========================================================================
    // INITIALIZE QUICK ACCESS BUTTONS FOR SEMESTERS
    // =========================================================================
    function initializeQuickAccessButtons() {
        const semesters = SEMESTERS_DATA;
        
        console.log('=== SEMESTER ORDERING DEBUG ===');
        console.log('Total semesters:', semesters.length);
        semesters.forEach((sem, idx) => {
            console.log(`Index ${idx}: ${sem.year_code} - ${sem.semester_name} (ID: ${sem.id}, Status: ${sem.status})`);
        });
        
        // Find active semester
        const activeSemester = semesters.find(s => s.status === 'active');
        
        if (!activeSemester) {
            console.log('❌ No active semester found');
            return;
        }
        
        console.log('✓ Active semester:', activeSemester.year_code, '-', activeSemester.semester_name);
        
        const activeSemesterIndex = semesters.findIndex(s => s.id === activeSemester.id);
        console.log('✓ Active semester is at index:', activeSemesterIndex);
        
        // Find previous semester (could be from previous school year)
        // Semesters are ordered DESC by year and semester, so next in array is previous chronologically
        let previousSemester = null;
        if (activeSemesterIndex + 1 < semesters.length) {
            previousSemester = semesters[activeSemesterIndex + 1];
            console.log('✓ Previous semester found:', previousSemester.year_code, '-', previousSemester.semester_name);
        } else {
            console.log('⚠ No previous semester available (active is the oldest)');
        }
        
        // Find next semester (could be from next school year)
        // Previous in array is next chronologically
        let nextSemester = null;
        if (activeSemesterIndex - 1 >= 0) {
            nextSemester = semesters[activeSemesterIndex - 1];
            console.log('✓ Next semester found:', nextSemester.year_code, '-', nextSemester.semester_name);
        } else {
            console.log('⚠ No next semester available (active is the newest)');
        }
        
        console.log('=== END DEBUG ===');
        
        // SOURCE SEMESTER QUICK ACCESS - Show Previous and Active
        let sourceQuickHTML = '';
        
        // Previous semester button (one before active, could be from previous school year)
        if (previousSemester) {
            sourceQuickHTML += `
                <button type="button" class="btn btn-sm btn-secondary btn-block semester-quick-btn" 
                        data-semester-id="${previousSemester.id}" data-target="source">
                    <i class="fas fa-step-backward mr-1"></i> Previous (${previousSemester.semester_name})
                </button>
            `;
        }
        
        // Active semester button
        sourceQuickHTML += `
            <button type="button" class="btn btn-sm btn-secondary btn-block semester-quick-btn" 
                    data-semester-id="${activeSemester.id}" data-target="source">
                <i class="fas fa-check-circle mr-1"></i> Active (${activeSemester.semester_name})
            </button>
        `;
        
        $('#sourceSemesterQuick').html(sourceQuickHTML);
        
        // TARGET SEMESTER QUICK ACCESS - Show Active and Next
        let targetQuickHTML = `
            <button type="button" class="btn btn-sm btn-primary btn-block semester-quick-btn active" 
                    data-semester-id="${activeSemester.id}" data-target="target">
                <i class="fas fa-check-circle mr-1"></i> Active (${activeSemester.semester_name})
            </button>
        `;
        
        // Next semester button (could be from next school year)
        if (nextSemester) {
            targetQuickHTML += `
                <button type="button" class="btn btn-sm btn-secondary btn-block semester-quick-btn" 
                        data-semester-id="${nextSemester.id}" data-target="target">
                    <i class="fas fa-step-forward mr-1"></i> Next (${nextSemester.semester_name})
                </button>
            `;
        }
        
        $('#targetSemesterQuick').html(targetQuickHTML);
    }

    // Initialize quick access buttons
    initializeQuickAccessButtons();

    // Handle quick access button clicks
    $(document).on('click', '.semester-quick-btn', function() {
        const semesterId = $(this).data('semester-id');
        const target = $(this).data('target');
        
        if (target === 'source') {
            $('#source_semester').val(semesterId).trigger('change');
            // Update active state
            $('#sourceSemesterQuick .semester-quick-btn').removeClass('active btn-primary').addClass('btn-secondary');
            $(this).addClass('active btn-primary').removeClass('btn-secondary');
        } else {
            $('#target_semester').val(semesterId).trigger('change');
            // Update active state
            $('#targetSemesterQuick .semester-quick-btn').removeClass('active btn-primary').addClass('btn-secondary');
            $(this).addClass('active btn-primary').removeClass('btn-secondary');
        }
    });

    // =========================================================================
    // INITIALIZE SELECT2
    // =========================================================================
    $('#filter_section').select2({
        theme: 'bootstrap4',
        placeholder: 'All Sections',
        allowClear: true,
        width: '100%'
    });

    $('#target_section').select2({
        theme: 'bootstrap4',
        placeholder: 'Select section...',
        allowClear: true,
        width: '100%'
    });

    // =========================================================================
    // SOURCE SEMESTER CHANGE - LOAD STUDENTS
    // =========================================================================
    $('#source_semester').on('change', function() {
        const semesterId = $(this).val();
        
        if (!semesterId) {
            clearStudentTable();
            $('#filterOptions').hide();
            $('#filter_section').empty().append('<option value="">All Sections</option>');
            return;
        }

        loadStudentsBySemester(semesterId);
        loadSectionsBySemester(semesterId);
        $('#filterOptions').show();
    });

    // =========================================================================
    // LOAD STUDENTS BY SEMESTER
    // =========================================================================
    function loadStudentsBySemester(semesterId) {
        $('#assignmentTableBody').html(`
            <tr>
                <td colspan="6" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                    <p>Loading students...</p>
                </td>
            </tr>
        `);

        $.ajax({
            url: API_ROUTES.loadStudentsBySemester,
            type: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                semester_id: semesterId
            },
            success: function(response) {
                if (response.success) {
                    allStudents = response.students;
                    loadedStudents = response.students;
                    
                    // Auto-detect strand and level from first student
                    if (loadedStudents.length > 0) {
                        const firstStudent = loadedStudents[0];
                        if (firstStudent.strand_id && firstStudent.level_id) {
                            sourceStrandId = firstStudent.strand_id;
                            sourceLevelId = firstStudent.level_id;
                            $('#target_strand').val(sourceStrandId).trigger('change');
                        }
                    }
                    
                    populateStudentTable(loadedStudents);
                    
                    Toast.fire({
                        icon: 'success',
                        title: `${response.count} student(s) loaded`
                    });
                } else {
                    Swal.fire('Error', response.message || 'Failed to load students', 'error');
                    clearStudentTable();
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to load students';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                Swal.fire('Error', errorMsg, 'error');
                clearStudentTable();
            }
        });
    }

    // =========================================================================
    // LOAD SECTIONS BY SEMESTER (for filter dropdown)
    // =========================================================================
    function loadSectionsBySemester(semesterId) {
        $.ajax({
            url: API_ROUTES.getSectionsBySemester,
            type: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                semester_id: semesterId
            },
            success: function(response) {
                if (response.success) {
                    const $select = $('#filter_section');
                    $select.empty().append('<option value="">All Sections</option>');
                    
                    response.sections.forEach(function(section) {
                        $select.append(`<option value="${section.id}">${section.name} (${section.code})</option>`);
                    });
                }
            }
        });
    }

    // =========================================================================
    // FILTER BY SECTION
    // =========================================================================
    $('#filter_section').on('change', function() {
        const sectionId = $(this).val();
        
        if (!sectionId) {
            // Show all students
            loadedStudents = allStudents;
        } else {
            // Filter by section
            loadedStudents = allStudents.filter(s => s.current_section_id == sectionId);
        }
        
        populateStudentTable(loadedStudents);
    });

    // =========================================================================
    // FILTER BY STUDENT SEARCH
    // =========================================================================
    $('#filter_student').on('keyup', function() {
        const searchValue = $(this).val().toLowerCase();
        
        $('#assignmentTableBody tr').each(function() {
            const $row = $(this);
            if ($row.find('.student-checkbox').length === 0) return;
            
            const text = $row.text().toLowerCase();
            if (searchValue === '' || text.indexOf(searchValue) > -1) {
                $row.show();
            } else {
                $row.hide();
            }
        });
        
        updateStudentCount();
    });

    // =========================================================================
    // TARGET STRAND CHANGE
    // =========================================================================
    $('#target_strand').on('change', function() {
        const strandId = $(this).val();
        const semesterId = $('#target_semester').val();
        
        $('#target_section').empty().append('<option value="">Select section...</option>');
        $('#capacityInfo').hide();
        targetSectionCapacity = null;
        targetSectionEnrolled = null;
        
        if (!strandId || !sourceLevelId) {
            return;
        }
        
        $.ajax({
            url: API_ROUTES.getTargetSections,
            type: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                strand_id: strandId,
                current_level_id: sourceLevelId,
                semester_id: semesterId
            },
            success: function(response) {
                if (response.success) {
                    populateTargetSections(response.current_level, response.next_level);
                }
            },
            error: function() {
                Toast.fire({
                    icon: 'error',
                    title: 'Failed to load target sections'
                });
            }
        });
    });

    // =========================================================================
    // TARGET SECTION CHANGE
    // =========================================================================
    $('#target_section').on('select2:select', function(e) {
        const sectionId = e.params.data.id;
        loadSectionCapacity(sectionId);
    }).on('select2:clear', function() {
        $('#capacityInfo').hide();
        targetSectionCapacity = null;
        targetSectionEnrolled = null;
        validateSubmitButton();
    });

    // =========================================================================
    // LOAD SECTION CAPACITY
    // =========================================================================
    function loadSectionCapacity(sectionId) {
        const semesterId = $('#target_semester').val();
        
        $.ajax({
            url: API_ROUTES.getSectionCapacity,
            type: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                section_id: sectionId,
                semester_id: semesterId
            },
            success: function(response) {
                if (response.success) {
                    targetSectionCapacity = response.capacity;
                    targetSectionEnrolled = response.enrolled_count;
                    updateCapacityDisplay();
                    validateSubmitButton();
                }
            },
            error: function() {
                Toast.fire({
                    icon: 'error',
                    title: 'Failed to load section capacity'
                });
            }
        });
    }

    // =========================================================================
    // UPDATE CAPACITY DISPLAY
    // =========================================================================
    function updateCapacityDisplay() {
        const selectedCount = $('.student-checkbox:checked:visible').length;
        const availableSlots = targetSectionCapacity - targetSectionEnrolled;
        const afterAssignment = targetSectionEnrolled + selectedCount;
        
        const statusText = selectedCount > availableSlots ? 'Exceeds' : `${availableSlots} slots`;
        const targetSectionName = $('#target_section option:selected').text().split(' (')[0] || 'Section';
        
        $('#capacityInfo').html(`
            <div class="card section-capacity-card mb-0">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="mb-0">${targetSectionName}</h6>
                        <span class="badge badge-light border">${statusText}</span>
                    </div>
                    <small class="text-muted d-block mb-2">
                        ${afterAssignment} / ${targetSectionCapacity} enrolled
                    </small>
                </div>
            </div>
        `).show();
    }

    // =========================================================================
    // POPULATE TARGET SECTIONS
    // =========================================================================
    function populateTargetSections(currentLevel, nextLevel) {
        const $select = $('#target_section');
        $select.empty();
        $select.append('<option value="">Select section...</option>');
        
        if (currentLevel && currentLevel.sections.length > 0) {
            const currentGroup = $('<optgroup>').attr('label', `Current Level (${currentLevel.name})`);
            currentLevel.sections.forEach(function(section) {
                currentGroup.append(
                    $('<option>').val(section.id).text(`${section.name} (${section.code})`)
                );
            });
            $select.append(currentGroup);
        }
        
        if (nextLevel && nextLevel.sections.length > 0) {
            const nextGroup = $('<optgroup>').attr('label', `Next Level (${nextLevel.name})`);
            nextLevel.sections.forEach(function(section) {
                nextGroup.append(
                    $('<option>').val(section.id).text(`${section.name} (${section.code})`)
                );
            });
            $select.append(nextGroup);
        }
        
        if ((!currentLevel || currentLevel.sections.length === 0) && (!nextLevel || nextLevel.sections.length === 0)) {
            $select.append('<option value="" disabled>No sections available</option>');
        }
    }

    // =========================================================================
    // CLEAR STUDENT TABLE
    // =========================================================================
    function clearStudentTable() {
        $('#assignmentTableBody').html(`
            <tr>
                <td colspan="6" class="text-center text-muted py-5">
                    <i class="fas fa-arrow-left fa-2x mb-3"></i>
                    <p>Select a source semester to load students</p>
                </td>
            </tr>
        `);
        allStudents = [];
        loadedStudents = [];
        updateStudentCount();
        validateSubmitButton();
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
                        <p>No students found</p>
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
                    <td><strong>${student.student_number}</strong></td>
                    <td>${fullName}</td>
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
        if (targetSectionCapacity) {
            updateCapacityDisplay();
        }
        validateSubmitButton();
    }

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
        if (targetSectionCapacity) {
            updateCapacityDisplay();
        }
        validateSubmitButton();
    });

    $(document).on('change', '.student-checkbox', function() {
        const isChecked = $(this).is(':checked');
        $(this).closest('tr').attr('data-selected', isChecked);
        updateStudentCount();
        if (targetSectionCapacity) {
            updateCapacityDisplay();
        }
        validateSubmitButton();
        
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
                if (targetSectionCapacity) {
                    updateCapacityDisplay();
                }
                validateSubmitButton();
                
                if ($('#assignmentTableBody tr').length === 0) {
                    clearStudentTable();
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
        const visibleStudents = $('#assignmentTableBody tr:visible').filter(function() {
            return $(this).find('.student-checkbox').length > 0;
        }).length;
        const selectedStudents = $('.student-checkbox:checked:visible').length;
        
        if (totalStudents === 0) {
            $('#studentCount').text('0 Students');
            return;
        }
        
        if (visibleStudents < totalStudents) {
            $('#studentCount').text(`${selectedStudents} / ${visibleStudents} Selected (${totalStudents} total)`);
        } else {
            $('#studentCount').text(`${selectedStudents} / ${totalStudents} Selected`);
        }
    }

    function validateSubmitButton() {
        const hasSelectedStudents = $('.student-checkbox:checked').length > 0;
        const hasTargetSection = $('#target_section').val();
        const hasCapacityInfo = targetSectionCapacity !== null;
        
        let canSubmit = hasSelectedStudents && hasTargetSection;
        
        if (canSubmit && hasCapacityInfo) {
            const selectedCount = $('.student-checkbox:checked').length;
            const availableSlots = targetSectionCapacity - targetSectionEnrolled;
            canSubmit = selectedCount <= availableSlots;
        }
        
        $('#submitBtn').prop('disabled', !canSubmit);
    }

    // =========================================================================
    // FORM SUBMISSION (Same as before - keeping batch processing logic)
    // =========================================================================
    $('#assign_section_form').on('submit', function(e) {
        e.preventDefault();

        if (!$('#target_semester').val()) {
            Swal.fire('Missing Selection', 'Please select a target semester', 'warning');
            return;
        }

        if (!$('#target_strand').val()) {
            Swal.fire('Missing Selection', 'Please select a target strand', 'warning');
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

        // Final capacity check
        if (targetSectionCapacity) {
            const availableSlots = targetSectionCapacity - targetSectionEnrolled;
            if (students.length > availableSlots) {
                Swal.fire({
                    icon: 'error',
                    title: 'Capacity Exceeded',
                    html: `
                        <p>Cannot assign ${students.length} students.</p>
                        <p>Only <strong>${availableSlots}</strong> slot(s) available in the target section.</p>
                    `
                });
                return;
            }
        }

        const targetSectionText = $('#target_section option:selected').text();
        const targetSemesterName = $('#target_semester option:selected').text();
        const targetStrandName = $('#target_strand option:selected').text();

        Swal.fire({
            title: 'Confirm Assignment',
            html: `
                <div class="text-left">
                    <hr>
                    <p><strong>Assigning ${students.length} student(s)</strong></p>
                    <p><strong>Strand:</strong> ${targetStrandName}</p>
                    <p><strong>Section:</strong> ${targetSectionText}</p>
                    <p><strong>Semester:</strong> ${targetSemesterName}</p>
                    ${targetSectionCapacity ? `
                        <hr>
                        <p><strong>Section Capacity:</strong> ${targetSectionEnrolled + students.length} / ${targetSectionCapacity}</p>
                    ` : ''}
                    <hr>
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