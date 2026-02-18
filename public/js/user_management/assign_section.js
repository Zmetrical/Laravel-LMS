$(document).ready(function() {
    let loadedStudents = [];
    let allStudents = [];
    let targetSectionCapacity = null;
    let targetSectionEnrolled = null;
    let currentTargetStrandId = null;
    let currentTargetLevelId = null;
    let sectionStrandMap = {}; // Maps section_id to strand_id
    let sectionLevelMap = {}; // Maps section_id to level_id
    let isProgrammaticChange = false; // Flag to prevent warning popups on auto-updates

    // Check if there's an active semester
    if (!HAS_ACTIVE_SEMESTER) {
        Swal.fire({
            icon: 'warning',
            title: 'No Active Semester',
            text: 'Please activate a semester before assigning students to sections.',
            confirmButtonText: 'OK'
        });
        return;
    }

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
    // INITIALIZE SELECT2
    // =========================================================================
    $('#filter_section').select2({
        theme: 'bootstrap4',
        allowClear: true,
        width: '100%'
    });

    $('#target_strand, #target_level').select2({
        theme: 'bootstrap4',
        allowClear: true,
        width: '100%'
    });

    // =========================================================================
    // AUTO-LOAD STUDENTS ON PAGE LOAD
    // =========================================================================
    loadStudents();
    loadFilterOptions();

    // =========================================================================
    // LOAD STUDENTS FROM CURRENT SEMESTER
    // =========================================================================
    function loadStudents() {
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
                _token: $('input[name="_token"]').val()
            },
            success: function(response) {
                if (response.success) {
                    allStudents = response.students;
                    loadedStudents = response.students;
                    
                    // Build section-strand and section-level maps
                    response.students.forEach(function(student) {
                        if (student.current_section_id) {
                            if (student.strand_id) {
                                sectionStrandMap[student.current_section_id] = student.strand_id;
                            }
                            if (student.level_id) {
                                sectionLevelMap[student.current_section_id] = student.level_id;
                            }
                        }
                    });
                    
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
    // LOAD FILTER OPTIONS (Sections only)
    // =========================================================================
    function loadFilterOptions() {
        $.ajax({
            url: API_ROUTES.getFilterOptions,
            type: 'POST',
            data: {
                _token: $('input[name="_token"]').val()
            },
            success: function(response) {
                if (response.success) {
                    // Populate section filter without strand name
                    const $sectionFilter = $('#filter_section');
                    $sectionFilter.empty().append('<option value="">All Sections</option>');
                    response.sections.forEach(function(section) {
                        const $option = $('<option>')
                            .val(section.id)
                            .text(`${section.name} (${section.code}) - ${section.level_name}`)
                            .attr('data-strand-id', section.strand_id)
                            .attr('data-level-id', section.level_id);
                        
                        $sectionFilter.append($option);
                        
                        // Update maps
                        if (section.strand_id) {
                            sectionStrandMap[section.id] = section.strand_id;
                        }
                        if (section.level_id) {
                            sectionLevelMap[section.id] = section.level_id;
                        }
                    });
                }
            }
        });
    }

    // =========================================================================
    // FILTER BY SECTION - Auto-update target strand and level
    // =========================================================================
    $('#filter_section').on('change', function() {
        const sectionId = $(this).val();
        
        if (sectionId) {
            // Get strand and level from selected option or maps
            let strandId = $(this).find('option:selected').data('strand-id');
            let levelId = $(this).find('option:selected').data('level-id');
            
            if (!strandId) {
                strandId = sectionStrandMap[sectionId];
            }
            if (!levelId) {
                levelId = sectionLevelMap[sectionId];
            }
            
            // Set flag to prevent warning popups
            isProgrammaticChange = true;
            
            // Update target strand
            if (strandId && $('#target_strand').val() !== strandId) {
                currentTargetStrandId = strandId;
                $('#target_strand').val(strandId).trigger('change.select2');
            }
            
            // Update target level
            if (levelId && $('#target_level').val() !== levelId) {
                currentTargetLevelId = levelId;
                $('#target_level').val(levelId).trigger('change.select2');
            }
            
            // Reset flag after a brief delay
            setTimeout(function() {
                isProgrammaticChange = false;
            }, 100);
        }
        
        applyFilters();
    });

    // =========================================================================
    // APPLY ALL FILTERS
    // =========================================================================
    function applyFilters() {
        const sectionId = $('#filter_section').val();
        
        loadedStudents = allStudents.filter(function(student) {
            let matchesSection = !sectionId || student.current_section_id == sectionId;
            return matchesSection;
        });
        
        populateStudentTable(loadedStudents);
    }

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
    // TARGET STRAND CHANGE - Show warning and update current section filter
    // =========================================================================
    $('#target_strand').on('change', function() {
        const newStrandId = $(this).val();
        
        // If empty, clear everything
        if (!newStrandId) {
            $('#target_section').val('');
            $('#target_level').val('').trigger('change.select2');
            $('.section-card').removeClass('selected');
            $('#targetSectionCards').html(`
                <div class="text-center text-muted py-3">
                    <i class="fas fa-arrow-up"></i>
                    <p class="mb-0"><small>Select strand and level first</small></p>
                </div>
            `);
            targetSectionCapacity = null;
            targetSectionEnrolled = null;
            currentTargetStrandId = null;
            filterCurrentSections(null, null);
            validateSubmitButton();
            return;
        }
        
        // Check if changing to different strand (and not programmatic)
        if (!isProgrammaticChange && currentTargetStrandId && currentTargetStrandId !== newStrandId) {
            const strandName = $('#target_strand option:selected').text();
            
            Swal.fire({
                title: 'Transferring to Different Strand',
                html: `
                    <div class="text-left">
                        <p>You are changing the target strand to:</p>
                        <p class="mb-0"><strong>${strandName}</strong></p>
                        <hr>
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle"></i> 
                            Students will be transferred to a different strand
                        </p>
                    </div>
                `,
                icon: 'warning',
                confirmButtonText: 'Continue',
                showCancelButton: true,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // User confirmed - proceed with clearing and updating
                    currentTargetStrandId = newStrandId;
                    
                    // Clear target section and level
                    $('#target_section').val('');
                    $('#target_level').val('').trigger('change.select2');
                    $('.section-card').removeClass('selected');
                    $('#targetSectionCards').html(`
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-arrow-up"></i>
                            <p class="mb-0"><small>Select strand and level first</small></p>
                        </div>
                    `);
                    targetSectionCapacity = null;
                    targetSectionEnrolled = null;
                    
                    filterCurrentSections(newStrandId, currentTargetLevelId);
                    validateSubmitButton();
                } else {
                    // User cancelled - revert to previous strand without clearing anything
                    isProgrammaticChange = true;
                    $('#target_strand').val(currentTargetStrandId).trigger('change.select2');
                    setTimeout(function() {
                        isProgrammaticChange = false;
                    }, 100);
                }
            });
        } else {
            // First time selection or programmatic change
            currentTargetStrandId = newStrandId;
            
            // Clear target section
            $('#target_section').val('');
            $('#target_level').val('').trigger('change.select2');
            $('.section-card').removeClass('selected');
            $('#targetSectionCards').html(`
                <div class="text-center text-muted py-3">
                    <i class="fas fa-arrow-up"></i>
                    <p class="mb-0"><small>Select strand and level first</small></p>
                </div>
            `);
            targetSectionCapacity = null;
            targetSectionEnrolled = null;
            
            filterCurrentSections(newStrandId, currentTargetLevelId);
            validateSubmitButton();
        }
    });

    // =========================================================================
    // TARGET LEVEL CHANGE - Show warning and load sections
    // =========================================================================
    $('#target_level').on('change', function() {
        const newLevelId = $(this).val();
        
        // If empty, clear everything
        if (!newLevelId) {
            $('#target_section').val('');
            $('.section-card').removeClass('selected');
            targetSectionCapacity = null;
            targetSectionEnrolled = null;
            $('#targetSectionCards').html(`
                <div class="text-center text-muted py-3">
                    <i class="fas fa-arrow-up"></i>
                    <p class="mb-0"><small>Select strand and level first</small></p>
                </div>
            `);
            currentTargetLevelId = null;
            filterCurrentSections(currentTargetStrandId, null);
            validateSubmitButton();
            return;
        }
        
        // Check if changing to different level (and not programmatic)
        if (!isProgrammaticChange && currentTargetLevelId && currentTargetLevelId !== newLevelId) {
            const levelName = $('#target_level option:selected').text();
            
            Swal.fire({
                title: 'Transferring to Different Level',
                html: `
                    <div class="text-left">
                        <p>You are changing the target level to:</p>
                        <p class="mb-0"><strong>${levelName}</strong></p>
                        <hr>
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle"></i> 
                            Students will be transferred to a different grade level
                        </p>
                    </div>
                `,
                icon: 'warning',
                confirmButtonText: 'Continue',
                showCancelButton: true,
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // User confirmed - proceed with clearing and loading sections
                    currentTargetLevelId = newLevelId;
                    
                    // Clear target section
                    $('#target_section').val('');
                    $('.section-card').removeClass('selected');
                    targetSectionCapacity = null;
                    targetSectionEnrolled = null;
                    
                    // Show loading state
                    $('#targetSectionCards').html(`
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-spinner fa-spin"></i>
                            <p class="mb-0"><small>Loading sections...</small></p>
                        </div>
                    `);
                    
                    filterCurrentSections(currentTargetStrandId, newLevelId);
                    loadTargetSections();
                    validateSubmitButton();
                } else {
                    // User cancelled - revert to previous level without clearing anything
                    isProgrammaticChange = true;
                    if (currentTargetLevelId) {
                        $('#target_level').val(currentTargetLevelId).trigger('change.select2');
                    } else {
                        $('#target_level').val('').trigger('change.select2');
                    }
                    setTimeout(function() {
                        isProgrammaticChange = false;
                    }, 100);
                }
            });
        } else {
            // First time selection or programmatic change
            currentTargetLevelId = newLevelId;
            
            // Clear target section
            $('#target_section').val('');
            $('.section-card').removeClass('selected');
            targetSectionCapacity = null;
            targetSectionEnrolled = null;
            
            filterCurrentSections(currentTargetStrandId, newLevelId);
            
            // Always load sections when level changes
            if ($('#target_strand').val()) {
                // Show loading state
                $('#targetSectionCards').html(`
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p class="mb-0"><small>Loading sections...</small></p>
                    </div>
                `);
                loadTargetSections();
            } else {
                $('#targetSectionCards').html(`
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-arrow-up"></i>
                        <p class="mb-0"><small>Select strand first</small></p>
                    </div>
                `);
            }
            
            validateSubmitButton();
        }
    });

    // =========================================================================
    // FILTER CURRENT SECTION OPTIONS based on target strand and level
    // =========================================================================
    function filterCurrentSections(strandId, levelId) {
        const $sectionFilter = $('#filter_section');
        
        $sectionFilter.find('option').each(function() {
            const $option = $(this);
            if ($option.val() === '') {
                $option.show(); // Keep "All Sections" option
                return;
            }
            
            const optionStrandId = $option.data('strand-id');
            const optionLevelId = $option.data('level-id');
            
            let showOption = true;
            
            if (strandId && optionStrandId != strandId) {
                showOption = false;
            }
            
            if (levelId && optionLevelId != levelId) {
                showOption = false;
            }
            
            if (showOption) {
                $option.show();
            } else {
                $option.hide();
            }
        });
        
        // If current selection is not valid, reset
        const currentSelection = $sectionFilter.val();
        if (currentSelection) {
            const $selectedOption = $sectionFilter.find('option:selected');
            const selectedStrandId = $selectedOption.data('strand-id');
            const selectedLevelId = $selectedOption.data('level-id');
            
            let isValid = true;
            if (strandId && selectedStrandId != strandId) {
                isValid = false;
            }
            if (levelId && selectedLevelId != levelId) {
                isValid = false;
            }
            
            if (!isValid) {
                $sectionFilter.val('').trigger('change.select2');
            }
        }
        
        $sectionFilter.trigger('change.select2');
    }

    // =========================================================================
    // LOAD TARGET SECTIONS (requires both strand and level)
    // =========================================================================
    function loadTargetSections() {
        const strandId = $('#target_strand').val();
        const levelId = $('#target_level').val();
        
        if (!strandId || !levelId) {
            $('#targetSectionCards').html(`
                <div class="text-center text-muted py-3">
                    <i class="fas fa-arrow-up"></i>
                    <p class="mb-0"><small>Select strand and level first</small></p>
                </div>
            `);
            return;
        }
        
        $.ajax({
            url: API_ROUTES.getTargetSections,
            type: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                strand_id: strandId,
                level_id: levelId
            },
            success: function(response) {
                if (response.success) {
                    displayTargetSectionCards(response.sections);
                } else {
                    $('#targetSectionCards').html(`
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle"></i>
                            <small>No sections available</small>
                        </div>
                    `);
                }
            },
            error: function() {
                Toast.fire({
                    icon: 'error',
                    title: 'Failed to load target sections'
                });
                $('#targetSectionCards').html(`
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-times-circle"></i>
                        <small>Error loading sections</small>
                    </div>
                `);
            }
        });
    }

    // =========================================================================
    // DISPLAY TARGET SECTION CARDS
    // =========================================================================
    function displayTargetSectionCards(sections) {
        const $container = $('#targetSectionCards');
        const previouslySelectedId = $('#target_section').val();
        $container.empty();
        
        if (!sections || sections.length === 0) {
            $container.html(`
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle"></i>
                    <small>No sections available for this strand and level</small>
                </div>
            `);
            // Clear selection if no sections available
            $('#target_section').val('');
            targetSectionCapacity = null;
            targetSectionEnrolled = null;
            return;
        }
        
        // Get current sections of ALL checked (selected) students
        const currentSectionIds = new Set();
        
        $('.student-checkbox:checked').each(function() {
            const $row = $(this).closest('tr');
            const studentNumber = $row.data('student-number');
            const studentData = allStudents.find(s => s.student_number === studentNumber);
            if (studentData && studentData.current_section_id) {
                currentSectionIds.add(studentData.current_section_id.toString());
            }
        });
        
        let shouldClearSelection = false;
        let hasAvailableSections = false;
        
        sections.forEach(function(section) {
            const sectionIdStr = section.id.toString();
            
            // Skip if this section is a current section of any selected student
            if (currentSectionIds.has(sectionIdStr)) {
                // If this was the selected target section, mark for clearing
                if (previouslySelectedId == section.id) {
                    shouldClearSelection = true;
                }
                return; // Don't display this section
            }
            
            hasAvailableSections = true;
            
            const available = section.capacity - section.enrolled_count;
            const percentage = (section.enrolled_count / section.capacity) * 100;
            const isFull = available <= 0;
            const wasSelected = previouslySelectedId == section.id;
            
            let statusBadge = '';
            let disabledClass = '';
            let selectedClass = '';
            let enrolledText = '';
            let progressBar = '';
            
            if (isFull) {
                statusBadge = '<span class="badge badge-primary capacity-badge">Full</span>';
                disabledClass = 'disabled';
                enrolledText = `<small class="text-muted">${section.enrolled_count} / ${section.capacity} enrolled</small>`;
                progressBar = `
                    <div class="progress capacity-progress">
                        <div class="progress-bar bg-primary" style="width: ${percentage}%"></div>
                    </div>
                `;
                
                // If this was selected but is now full, clear the selection
                if (wasSelected) {
                    shouldClearSelection = true;
                }
            } else {
                statusBadge = `<span class="badge badge-primary capacity-badge">${available} slots</span>`;
                enrolledText = `<small class="text-muted">${section.enrolled_count} / ${section.capacity} enrolled</small>`;
                progressBar = `
                    <div class="progress capacity-progress">
                        <div class="progress-bar bg-primary" style="width: ${percentage}%"></div>
                    </div>
                `;
                
                // Restore selection if this was previously selected and still valid
                if (wasSelected) {
                    selectedClass = 'selected';
                    targetSectionCapacity = section.capacity;
                    targetSectionEnrolled = section.enrolled_count;
                }
            }
            
            const card = $(`
                <div class="section-card card mb-2 ${disabledClass} ${selectedClass}" 
                     data-section-id="${section.id}" 
                     data-capacity="${section.capacity}" 
                     data-enrolled="${section.enrolled_count}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-0">${section.name}</h6>
                                <small class="text-muted">${section.code} - ${section.level_name}</small>
                            </div>
                            ${statusBadge}
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            ${enrolledText}
                        </div>
                        ${progressBar}
                    </div>
                </div>
            `);
            
            $container.append(card);
        });
        
        // If no sections available after filtering out current sections
        if (!hasAvailableSections) {
            $container.html(`
                <div class="alert alert-info mb-0">
                    <i class="fas fa-info-circle"></i>
                    <small>No other sections available - selected students are already in all sections for this strand and level</small>
                </div>
            `);
            shouldClearSelection = true;
        }
        
        // Clear selection if it became invalid
        if (shouldClearSelection) {
            $('#target_section').val('');
            targetSectionCapacity = null;
            targetSectionEnrolled = null;
            Toast.fire({
                icon: 'info',
                title: 'Selection cleared - section no longer available'
            });
        }
        
        // Update capacity display if there's a valid selection
        if ($('#target_section').val() && targetSectionCapacity) {
            updateCapacityDisplay();
        }
    }

    // =========================================================================
    // HANDLE SECTION CARD CLICK - SINGLE SELECTION ONLY (TOGGLE)
    // =========================================================================
    $(document).on('click', '.section-card', function(e) {
        // Prevent action if card is disabled
        if ($(this).hasClass('disabled')) {
            e.preventDefault();
            e.stopPropagation();
            
            Toast.fire({
                icon: 'warning',
                title: 'This section is full'
            });
            
            return false;
        }
        
        const $clickedCard = $(this);
        const isAlreadySelected = $clickedCard.hasClass('selected');
        const sectionId = $clickedCard.data('section-id');
        const capacity = $clickedCard.data('capacity');
        const enrolled = $clickedCard.data('enrolled');
        
        if (isAlreadySelected) {
            // Toggle OFF - deselect the card and reload sections to reset display
            $('#target_section').val('');
            targetSectionCapacity = null;
            targetSectionEnrolled = null;
            loadTargetSections();
        } else {
            // Selecting a NEW card - store the selection temporarily
            const newSectionId = sectionId;
            const newCapacity = capacity;
            const newEnrolled = enrolled;
            
            // Clear current selection
            $('#target_section').val('');
            targetSectionCapacity = null;
            targetSectionEnrolled = null;
            
            // Reload all cards to reset displays, then select the new card
            $.ajax({
                url: API_ROUTES.getTargetSections,
                type: 'POST',
                data: {
                    _token: $('input[name="_token"]').val(),
                    strand_id: $('#target_strand').val(),
                    level_id: $('#target_level').val()
                },
                success: function(response) {
                    if (response.success) {
                        displayTargetSectionCards(response.sections);
                        
                        // Now select the new card after reload
                        const $newCard = $(`.section-card[data-section-id="${newSectionId}"]`);
                        if ($newCard.length && !$newCard.hasClass('disabled')) {
                            $newCard.addClass('selected');
                            $('#target_section').val(newSectionId);
                            targetSectionCapacity = newCapacity;
                            targetSectionEnrolled = newEnrolled;
                            updateCapacityDisplay();
                        }
                        
                        validateSubmitButton();
                    }
                }
            });
        }
        
        validateSubmitButton();
    });

    // =========================================================================
    // UPDATE CAPACITY DISPLAY (for selected students count)
    // =========================================================================
    function updateCapacityDisplay() {
        if (!targetSectionCapacity) return;
        
        const selectedCount = $('.student-checkbox:checked:visible').length;
        const availableSlots = targetSectionCapacity - targetSectionEnrolled;
        const afterAssignment = targetSectionEnrolled + selectedCount;
        
        // Update the selected card's appearance
        const $selectedCard = $('.section-card.selected');
        if ($selectedCard.length > 0) {
            const percentage = (afterAssignment / targetSectionCapacity) * 100;
            const wouldExceed = afterAssignment > targetSectionCapacity;
            const remainingAfter = availableSlots - selectedCount;
            
            // Always use primary color
            $selectedCard.find('.capacity-badge')
                .removeClass('badge-secondary')
                .addClass('badge-primary')
                .text(wouldExceed ? 'Exceeds!' : `${remainingAfter} after`);
            
            $selectedCard.find('.text-muted:last')
                .text(`${afterAssignment} / ${targetSectionCapacity} after assignment`);
            
            $selectedCard.find('.progress-bar')
                .removeClass('bg-secondary')
                .addClass('bg-primary')
                .css('width', `${Math.min(percentage, 100)}%`);
        }
    }

    // =========================================================================
    // CLEAR STUDENT TABLE
    // =========================================================================
    function clearStudentTable() {
        $('#assignmentTableBody').html(`
            <tr>
                <td colspan="6" class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-2x mb-3"></i>
                    <p>No students found</p>
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
        // Refresh section cards to update after loading students
        if ($('#target_strand').val() && $('#target_level').val()) {
            loadTargetSections();
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
        // Refresh section cards when selection changes
        if ($('#target_strand').val() && $('#target_level').val()) {
            loadTargetSections();
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
        // Refresh section cards when selection changes
        if ($('#target_strand').val() && $('#target_level').val()) {
            loadTargetSections();
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
                // Refresh section cards after removing students
                if ($('#target_strand').val() && $('#target_level').val()) {
                    loadTargetSections();
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
    // FORM SUBMISSION
    // =========================================================================
    $('#assign_section_form').on('submit', function(e) {
        e.preventDefault();

        if (!$('#target_strand').val()) {
            Swal.fire('Missing Selection', 'Please select a target strand', 'warning');
            return;
        }

        if (!$('#target_level').val()) {
            Swal.fire('Missing Selection', 'Please select a target level', 'warning');
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

        const targetSectionName = $('.section-card.selected h6').text();
        const targetStrandName = $('#target_strand option:selected').text();
        const targetLevelName = $('#target_level option:selected').text();

        Swal.fire({
            title: 'Confirm Assignment',
            html: `
                <div class="text-left">
                    <hr>
                    <p><strong>Assigning ${students.length} student(s)</strong></p>
                    <p><strong>Strand:</strong> ${targetStrandName}</p>
                    <p><strong>Level:</strong> ${targetLevelName}</p>
                    <p><strong>Section:</strong> ${targetSectionName}</p>
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
        const batchSize = 25;
        const batches = [];
        
        for (let i = 0; i < students.length; i += batchSize) {
            batches.push(students.slice(i, i + batchSize));
        }

        showProcessingModal(batches.length, students.length);
        processBatches(batches, 0, students.length, 0);
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

    function processBatches(batches, currentBatch, totalStudents, processedCount) {
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
                section_id: $('#target_section').val(),
                students: batch
            },
            success: function(response) {
                processedCount += batch.length;
                const actualProcessed = Math.min(processedCount, totalStudents);
                updateProgress(actualProcessed, totalStudents, batchNumber, batches.length);
                
                setTimeout(function() {
                    processBatches(batches, currentBatch + 1, totalStudents, processedCount);
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