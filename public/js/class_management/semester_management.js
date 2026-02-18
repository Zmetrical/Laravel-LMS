$(document).ready(function () {
    let schoolYearData = null;
    let semestersData = [];
    let semesterDetailsCache = {};
    let sourceSemesterData = null;
    let targetSemesterData = null;

    // Enrollment wizard state
    let wizard = {
        currentStep: 1,
        sourceSection: null,
        sourceSemesterId: null,
        students: [],
        selectedStudents: [],
        targetSection: null,
        targetSectionData: null,
        targetSemesterId: null,
        sourceStrandId: null,
        sourceLevelId: null,
        sourceStrandName: null,
        sourceLevelName: null,
        enrollmentAction: 'promote', // promote, retain, transfer
        targetStrandId: null,
        targetLevelId: null,
        isPromotionScenario: false,
        allStrands: [],
        allLevels: []
    };

    // =========================================================================
    // VERIFICATION
    // =========================================================================
    
    $('#verificationForm').submit(function (e) {
        e.preventDefault();
        const password = $('#adminPassword').val();
        
        if (!password) {
            showToast('warning', 'Please enter your password');
            return;
        }

        $.ajax({
            url: API_ROUTES.verifyAccess,
            method: 'POST',
            data: { admin_password: password },
            headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
            success: function (response) {
                if (response.success) {
                    $('#verificationCard').fadeOut(300, function () {
                        $('#archiveContent').fadeIn(300);
                        loadArchiveInfo();
                    });
                    showToast('success', 'Access granted');
                }
            },
            error: function (xhr) {
                showToast('error', xhr.responseJSON?.message || 'Invalid password');
                $('#adminPassword').val('').focus();
            }
        });
    });

    // =========================================================================
    // LOAD ARCHIVE DATA
    // =========================================================================

    function loadArchiveInfo() {
        $('#contentLoading').show();
        $('#mainContent').hide();
        
        semesterDetailsCache = {};

        const url = API_ROUTES.getArchiveInfo.replace(':id', SCHOOL_YEAR_ID);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    schoolYearData = response.data.school_year;
                    semestersData = response.data.semesters;
                    
                    displayArchiveInfo();
                }
            },
            error: function () {
                showToast('error', 'Failed to load data');
                $('#contentLoading').hide();
                $('#mainContent').show();
            }
        });
    }

    function displayArchiveInfo() {
        $('#contentLoading').hide();
        
        $('#syDisplay').text(`SY ${schoolYearData.year_start}-${schoolYearData.year_end}`);
        const statusClass = {
            'active': 'bg-primary',
            'completed': 'bg-dark',
            'upcoming': 'bg-secondary'
        }[schoolYearData.status] || 'bg-light';
        
        $('#syStatusBadge').attr('class', `semester-status-badge ${statusClass}`)
            .text(schoolYearData.status.toUpperCase());

        if (semestersData.length > 0) {
            buildSemesterTabs();
            $('#mainContent').show();
        } else {
            $('#noSemesters').show();
        }
    }

    function buildSemesterTabs() {
        const tabsHtml = [];
        const contentHtml = [];

        semestersData.forEach((sem, index) => {
            const active = index === 0 ? 'active' : '';
            const statusBadge = getStatusBadge(sem.status);
            
            tabsHtml.push(`
                <li class="nav-item">
                    <a class="nav-link ${active}" data-toggle="tab" href="#tab-${sem.id}">
                        ${sem.name} ${statusBadge}
                    </a>
                </li>
            `);

            contentHtml.push(`
                <div class="tab-pane fade ${active ? 'show active' : ''}" id="tab-${sem.id}">
                    <div id="sem-${sem.id}-content">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Loading...</p>
                        </div>
                    </div>
                </div>
            `);
        });

        $('#semesterTabs').html(tabsHtml.join(''));
        $('#semesterTabContent').html(contentHtml.join(''));

        loadSemesterDetails(semestersData[0].id);

        $('#semesterTabs a[data-toggle="tab"]').off('shown.bs.tab').on('shown.bs.tab', function (e) {
            const semId = $(e.target).attr('href').replace('#tab-', '');
            if (!semesterDetailsCache[semId]) {
                loadSemesterDetails(semId);
            }
        });
    }

    function loadSemesterDetails(semesterId) {
        const url = API_ROUTES.getSemesterDetails.replace(':id', semesterId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    semesterDetailsCache[semesterId] = response.data;
                    displaySemesterContent(semesterId, response.data);
                }
            },
            error: function () {
                $(`#sem-${semesterId}-content`).html(`
                    <div class="alert alert-primary">Failed to load details</div>
                `);
            }
        });
    }

    function displaySemesterContent(semesterId, data) {
        const semester = semestersData.find(s => s.id == semesterId);
        const canActivate = semester.status === 'upcoming';
        const canComplete = semester.status === 'active';
        
        let html = `
            <div class="mb-3 text-right">
                ${canActivate ? `
                    <button class="btn btn-primary activate-sem" data-id="${semesterId}">
                        <i class="fas fa-play"></i> Activate
                    </button>
                ` : ''}
                ${canComplete ? `
                    <button class="btn btn-secondary complete-sem" data-id="${semesterId}">
                        <i class="fas fa-check-circle"></i> Complete
                    </button>
                ` : ''}
                <button class="btn btn-primary enroll-to-sem" data-semester-id="${semesterId}">
                    <i class="fas fa-user-plus"></i> Enroll Existing Students
                </button>
            </div>

            ${buildSectionsTable(semesterId, data.sections)}
        `;

        $(`#sem-${semesterId}-content`).html(html);

        $('.activate-sem').click(function() {
            activateSemester($(this).data('id'));
        });

        $('.complete-sem').click(function() {
            completeSemester($(this).data('id'));
        });

        $('.section-row').click(function() {
            toggleSectionDetails($(this));
        });

        $('.enroll-to-sem').click(function() {
            const targetSemId = $(this).data('semester-id');
            openEnrollmentWizard(targetSemId);
        });
    }

    function openEnrollmentWizard(targetSemesterId) {
        const targetSemester = semestersData.find(s => s.id == targetSemesterId);
        
        if (!targetSemester) {
            showToast('error', 'Target semester not found');
            return;
        }

        $.ajax({
            url: API_ROUTES.getPreviousSemester.replace(':id', targetSemesterId),
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const sourceSem = response.source_semester;
                    const targetSem = response.target_semester;

                    if (!sourceSem) {
                        showToast('warning', 'No source semester found for enrollment');
                        return;
                    }

                    wizard.sourceSemesterId = sourceSem.id;
                    wizard.targetSemesterId = targetSem.id;

                    sourceSemesterData = {
                        id: sourceSem.id,
                        semester_name: sourceSem.name,
                        year_code: sourceSem.year_code,
                        status: sourceSem.status
                    };

                    targetSemesterData = {
                        id: targetSem.id,
                        semester_name: targetSem.name,
                        year_code: targetSem.year_code,
                        status: targetSem.status
                    };

                    resetWizard();
                    $('#quickEnrollModal').modal('show');
                }
            },
            error: function(xhr) {
                showToast('error', xhr.responseJSON?.message || 'Failed to get semester info');
            }
        });
    }

    function completeSemester(id) {
        const semester = semestersData.find(s => s.id == id);

        Swal.fire({
            title: 'Complete Semester?',
            html: `
                <p>Mark <strong>${semester.name}</strong> as completed?</p>
                <p class="small text-muted">This will mark the semester as finished and preserve all data</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, complete',
            confirmButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.completeSemester.replace(':id', id);

                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
                    success: function (response) {
                        if (response.success) {
                            showToast('success', 'Semester completed');
                            loadArchiveInfo();
                        }
                    },
                    error: function (xhr) {
                        showToast('error', xhr.responseJSON?.message || 'Failed');
                    }
                });
            }
        });
    }

    function buildSectionsTable(semesterId, sections) {
        if (sections.length === 0) {
            return '<p class="text-muted text-center py-4">No sections found</p>';
        }

        let html = `
            <div class="mb-2">
                <button class="btn btn-sm btn-default" id="expandAllSections">
                    <i class="fas fa-plus-square"></i> Expand All
                </button>
                <button class="btn btn-sm btn-default" id="collapseAllSections">
                    <i class="fas fa-minus-square"></i> Collapse All
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th width="30"></th>
                            <th>Section</th>
                            <th>Level</th>
                            <th>Strand</th>
                            <th>Students</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        sections.forEach(section => {
            html += `
                <tr class="section-row" data-semester="${semesterId}" data-section="${section.id}">
                    <td>
                        <i class="fas fa-chevron-right collapse-icon"></i>
                    </td>
                    <td><strong>${section.section_name}</strong><br><small class="text-muted">${section.section_code}</small></td>
                    <td>${section.level_name}</td>
                    <td>${section.strand_code}</td>
                    <td>
                        <span class="badge badge-primary">${section.student_count}</span>
                    </td>
                </tr>
                <tr class="student-details-row" id="details-${semesterId}-${section.id}" style="display: none;">
                    <td colspan="5">
                        <div class="p-3">
                            <div class="text-center py-2">
                                <i class="fas fa-spinner fa-spin"></i> Loading...
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        return html;
    }

    $(document).on('click', '#expandAllSections', function() {
        $('.section-row').each(function() {
            if (!$(this).hasClass('expanded')) {
                toggleSectionDetails($(this));
            }
        });
    });

    $(document).on('click', '#collapseAllSections', function() {
        $('.section-row.expanded').each(function() {
            toggleSectionDetails($(this));
        });
    });

    function toggleSectionDetails($row) {
        const semesterId = $row.data('semester');
        const sectionId = $row.data('section');
        const $details = $(`#details-${semesterId}-${sectionId}`);
        
        if ($row.hasClass('expanded')) {
            $row.removeClass('expanded');
            $details.slideUp(200);
        } else {
            $row.addClass('expanded');
            $details.slideDown(200);
            
            if (!$details.data('loaded')) {
                loadSectionStudents(semesterId, sectionId, $details);
            }
        }
    }

    function loadSectionStudents(semesterId, sectionId, $container) {
        const url = API_ROUTES.getSectionStudents
            .replace(':semesterId', semesterId)
            .replace(':sectionId', sectionId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    displayStudentList($container, response.data);
                    $container.data('loaded', true);
                }
            },
            error: function () {
                $container.find('td').html('<div class="alert alert-primary">Failed to load students</div>');
            }
        });
    }

    function displayStudentList($container, students) {
        if (students.length === 0) {
            $container.find('td').html('<p class="text-muted text-center">No students</p>');
            return;
        }

        const sortedStudents = students.sort((a, b) => {
            if (a.gender === b.gender) {
                return a.full_name.localeCompare(b.full_name);
            }
            return a.gender === 'Male' ? -1 : 1;
        });

        const maleStudents = sortedStudents.filter(s => s.gender === 'Male');
        const femaleStudents = sortedStudents.filter(s => s.gender === 'Female');

        let html = '';
        
        if (maleStudents.length > 0) {
            html += `
                <div class="mb-3">
                    <h6 class="bg-primary text-white mb-2 p-2 rounded">
                        <i class="fas fa-mars"></i> Male (${maleStudents.length})
                    </h6>
                    <div class="row">
            `;
            maleStudents.forEach((student) => {
                html += `
                    <div class="col-md-6 mb-2">
                        <strong>${student.student_number}</strong> - ${student.full_name}
                    </div>
                `;
            });
            html += '</div></div>';
        }

        if (femaleStudents.length > 0) {
            html += `
                <div class="mb-3">
                    <h6 class="bg-primary text-white mb-2 p-2 rounded">
                        <i class="fas fa-venus"></i> Female (${femaleStudents.length})
                    </h6>
                    <div class="row">
            `;
            femaleStudents.forEach((student) => {
                html += `
                    <div class="col-md-6 mb-2">
                        <strong>${student.student_number}</strong> - ${student.full_name}
                    </div>
                `;
            });
            html += '</div></div>';
        }

        $container.find('td').html(html);
    }

    // =========================================================================
    // ENROLLMENT WIZARD
    // =========================================================================

    function resetWizard() {
        wizard = {
            currentStep: 1,
            sourceSection: null,
            sourceSemesterId: sourceSemesterData?.id || null,
            students: [],
            selectedStudents: [],
            targetSection: null,
            targetSectionData: null,
            targetSemesterId: targetSemesterData?.id || null,
            sourceStrandId: null,
            sourceLevelId: null,
            sourceStrandName: null,
            sourceLevelName: null,
            enrollmentAction: 'promote',
            targetStrandId: null,
            targetLevelId: null,
            isPromotionScenario: false,
            allStrands: [],
            allLevels: []
        };

        $('.enrollment-step').hide();
        $('#step1').show();
        updateStepIndicator(1);
        
        $('#qe_source_section').val(null).trigger('change');
        $('#studentSelectionArea').hide();
        $('#btnStep1Next').prop('disabled', true);
        
        displaySourceSemesterInfo();
        displayTargetSemesterInfo();
    }

    function updateStepIndicator(step) {
        $('.step').removeClass('active completed');
        
        for (let i = 1; i < step; i++) {
            $(`.step[data-step="${i}"]`).addClass('completed');
        }
        $(`.step[data-step="${step}"]`).addClass('active');
    }

    function displaySourceSemesterInfo() {
        if (!sourceSemesterData) {
            $('#sourceSemesterDisplay').html(`
                <span class="text-muted"><i class="fas fa-exclamation-circle"></i> No source semester found</span>
            `);
            return;
        }
        
        const semesterDisplay = `${sourceSemesterData.year_code} - ${sourceSemesterData.semester_name}`;
        const statusBadge = sourceSemesterData.status === 'active' ? 'badge-primary' : 'badge-dark';
        
        $('#sourceSemesterDisplay').html(`
            ${semesterDisplay} <span class="badge ${statusBadge} ml-2">${sourceSemesterData.status.toUpperCase()}</span>
        `);
    }

    function displayTargetSemesterInfo() {
        if (!targetSemesterData) {
            $('#targetSemesterDisplay').html(`
                <span class="text-muted"><i class="fas fa-exclamation-circle"></i> No target semester available</span>
            `);
            return;
        }
        
        const semesterDisplay = `${targetSemesterData.year_code} - ${targetSemesterData.semester_name}`;
        const statusBadge = targetSemesterData.status === 'active' ? 'badge-primary' : 'badge-secondary';
        
        $('#targetSemesterDisplay').html(`
            ${semesterDisplay} <span class="badge ${statusBadge} ml-2">${targetSemesterData.status.toUpperCase()}</span>
        `);
    }

    // Step 1: Section & Student Selection
    $('#qe_source_section').select2({
        theme: 'bootstrap4',
        placeholder: 'Type to search...',
        dropdownParent: $('#quickEnrollModal'),
        allowClear: true,
        ajax: {
            url: API_ROUTES.searchSections,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { search: params.term };
            },
            processResults: function(data) {
                return {
                    results: data.map(s => ({
                        id: s.id,
                        text: `${s.name} - ${s.level_name} ${s.strand_code}`
                    }))
                };
            }
        }
    }).on('select2:select', function(e) {
        wizard.sourceSection = e.params.data.id;
        
        $(this).select2('close');
        
        setTimeout(function() {
            loadWizardStudents();
        }, 150);
        
    }).on('select2:clear', function() {
        wizard.sourceSection = null;
        $('#studentSelectionArea').slideUp(200);
        $('#btnStep1Next').prop('disabled', true);
    });

    function loadWizardStudents() {
        $('#studentSelectionArea').slideDown(300);
        
        $('#qe_studentList').html(`
            <tr>
                <td colspan="6" class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Loading students...</p>
                </td>
            </tr>
        `);

        $.ajax({
            url: API_ROUTES.loadStudents,
            type: 'POST',
            data: {
                _token: API_ROUTES.csrfToken,
                source_section_id: wizard.sourceSection,
                source_semester_id: wizard.sourceSemesterId,
                target_semester_id: wizard.targetSemesterId
            },
            success: function(response) {
                if (response.success) {
                    wizard.students = response.students.map(s => ({
                        ...s, 
                        selected: !s.already_enrolled
                    }));
                    wizard.selectedStudents = wizard.students.filter(s => s.selected);
                    displayWizardStudents();
                    loadSectionDetailsForWizard();
                }
            },
            error: function() {
                showToast('error', 'Failed to load students');
                $('#studentSelectionArea').slideUp(200);
            }
        });
    }

    function loadSectionDetailsForWizard() {
        $.ajax({
            url: API_ROUTES.getSectionDetails,
            type: 'POST',
            data: {
                _token: API_ROUTES.csrfToken,
                section_id: wizard.sourceSection
            },
            success: function(response) {
                if (response.success) {
                    wizard.sourceStrandId = response.section.strand_id;
                    wizard.sourceLevelId = response.section.level_id;
                    wizard.sourceStrandName = response.section.strand_name;
                    wizard.sourceLevelName = response.section.level_name;
                }
            }
        });
    }

    function displayWizardStudents() {
        if (wizard.students.length === 0) {
            $('#qe_studentList').html(`
                <tr><td colspan="6" class="text-center text-muted py-4">No students found</td></tr>
            `);
            $('#btnStep1Next').prop('disabled', true);
            return;
        }

        $('#enrollmentWarning').remove();
        
        const alreadyEnrolledCount = wizard.students.filter(s => s.already_enrolled).length;
        
        if (alreadyEnrolledCount > 0) {
            $('#studentSelectionArea').prepend(`
                <div class="alert alert-warning alert-dismissible fade show" id="enrollmentWarning">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> ${alreadyEnrolledCount} student(s) are already enrolled in the target semester.
                    They are automatically deselected.
                </div>
            `);
        }

        let html = '';
        wizard.students.forEach((s, i) => {
            const fullName = `${s.last_name}, ${s.first_name} ${s.middle_name || ''}`.trim();
            const genderIcon = s.gender === 'Male' ? 'fa-mars' : 'fa-venus';
            const isEnrolled = s.already_enrolled;
            const rowClass = isEnrolled ? 'table-warning' : '';
            
            html += `
                <tr class="student-row ${rowClass}" data-index="${i}" data-gender="${s.gender.toLowerCase()}">
                    <td>
                        <input type="checkbox" class="student-checkbox" 
                               ${s.selected ? 'checked' : ''} 
                               ${isEnrolled ? 'disabled' : ''}>
                    </td>
                    <td>${s.student_number}</td>
                    <td>${fullName}</td>
                    <td><i class="fas ${genderIcon}"></i> ${s.gender}</td>
                    <td><span class="badge badge-secondary">${s.student_type}</span></td>
                    <td>
                        ${isEnrolled ? '<span class="badge badge-warning"><i class="fas fa-check"></i> Already Enrolled</span>' : ''}
                    </td>
                </tr>
            `;
        });

        $('#qe_studentList').html(html);
        updateStudentCount();
        $('#btnStep1Next').prop('disabled', wizard.selectedStudents.length === 0);
    }

    $(document).on('change', '.student-checkbox', function() {
        const index = $(this).closest('tr').data('index');
        wizard.students[index].selected = $(this).is(':checked');
        updateSelectedStudents();
    });

    $('#selectAllCheckbox').change(function() {
        const checked = $(this).is(':checked');
        $('.student-checkbox:visible:not(:disabled)').prop('checked', checked);
        $('.student-row:visible').each(function() {
            const index = $(this).data('index');
            if (!wizard.students[index].already_enrolled) {
                wizard.students[index].selected = checked;
            }
        });
        updateSelectedStudents();
    });

    $('#qe_selectAll').click(function() {
        $('.student-checkbox:visible:not(:disabled)').prop('checked', true);
        $('.student-row:visible').each(function() {
            const index = $(this).data('index');
            if (!wizard.students[index].already_enrolled) {
                wizard.students[index].selected = true;
            }
        });
        updateSelectedStudents();
    });

    $('#qe_deselectAll').click(function() {
        $('.student-checkbox').prop('checked', false);
        wizard.students.forEach(s => s.selected = false);
        updateSelectedStudents();
    });

    $('#studentSearch').on('input', function() {
        const search = $(this).val().toLowerCase();
        
        $('.student-row').each(function() {
            const text = $(this).text().toLowerCase();
            $(this).toggle(text.includes(search));
        });

        updateSelectAllCheckbox();
    });

    $('.filter-pill').click(function() {
        $('.filter-pill').removeClass('active');
        $(this).addClass('active');
        
        const filter = $(this).data('filter');
        
        if (filter === 'all') {
            $('.student-row').show();
        } else {
            $('.student-row').each(function() {
                const gender = $(this).data('gender');
                $(this).toggle(gender === filter);
            });
        }

        updateSelectAllCheckbox();
    });

    function updateSelectAllCheckbox() {
        const visibleCheckboxes = $('.student-checkbox:visible:not(:disabled)');
        const checkedCheckboxes = $('.student-checkbox:visible:checked');
        $('#selectAllCheckbox').prop('checked', visibleCheckboxes.length > 0 && visibleCheckboxes.length === checkedCheckboxes.length);
    }

    function updateSelectedStudents() {
        wizard.selectedStudents = wizard.students.filter(s => s.selected);
        updateStudentCount();
        updateSelectAllCheckbox();
        $('#btnStep1Next').prop('disabled', wizard.selectedStudents.length === 0);
    }

    function updateStudentCount() {
        const count = wizard.selectedStudents.length;
        const total = wizard.students.filter(s => !s.already_enrolled).length;
        $('#qe_studentCount').text(`${count} / ${total} selected`);
    }

    $('#btnStep1Next').click(function() {
        showEnrollmentActionStep();
    });

    // Step 2: Enrollment Action Selection
    function showEnrollmentActionStep() {
        // First, determine if this is a promotion scenario
        $.ajax({
            url: API_ROUTES.getTargetSections,
            type: 'POST',
            data: {
                _token: API_ROUTES.csrfToken,
                strand_id: wizard.sourceStrandId,
                current_level_id: wizard.sourceLevelId,
                semester_id: wizard.targetSemesterId,
                source_semester_id: wizard.sourceSemesterId,
                enrollment_action: 'promote' // Initial check
            },
            success: function(response) {
                if (response.success) {
                    wizard.isPromotionScenario = response.is_promotion_scenario;
                    wizard.allStrands = response.all_strands;
                    wizard.allLevels = response.all_levels;
                    
                    // Now build the UI based on scenario
                    buildEnrollmentActionUI();
                }
            },
            error: function() {
                showToast('error', 'Failed to load enrollment options');
            }
        });
    }

    function buildEnrollmentActionUI() {
        $('.enrollment-step').hide();
        $('#step2').show();
        updateStepIndicator(2);

        // Determine default action and available options
        let defaultAction = 'retain';
        let showPromote = wizard.isPromotionScenario;
        
        if (wizard.isPromotionScenario) {
            defaultAction = 'promote';
        }
        
        wizard.enrollmentAction = defaultAction;

        // Build enrollment action selector with horizontal layout
        let html = `
            <div class="alert alert-default-secondary mb-3">
                <i class="fas fa-info-circle"></i>
                <strong>Current Section:</strong> ${wizard.sourceLevelName} - ${wizard.sourceStrandName}
            </div>
            
            <h6 class="mb-3">Choose enrollment action:</h6>
            
            <div class="btn-group btn-group-toggle d-flex mb-3" data-toggle="buttons">
                ${showPromote ? `
                <label class="btn btn-outline-primary flex-fill ${defaultAction === 'promote' ? 'active' : ''}">
                    <input type="radio" name="enrollmentAction" value="promote" ${defaultAction === 'promote' ? 'checked' : ''}>
                    <i class="fas fa-level-up-alt"></i> Promote
                </label>
                ` : ''}
                <label class="btn btn-outline-primary flex-fill ${defaultAction === 'retain' ? 'active' : ''}">
                    <input type="radio" name="enrollmentAction" value="retain" ${defaultAction === 'retain' ? 'checked' : ''}>
                    <i class="fas fa-redo"></i> Retain
                </label>
                <label class="btn btn-outline-primary flex-fill">
                    <input type="radio" name="enrollmentAction" value="transfer">
                    <i class="fas fa-exchange-alt"></i> Transfer
                </label>
            </div>
            
            <div id="transferOptions" style="display: none;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Target Level</label>
                            <select class="form-control" id="transfer_level">
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Target Strand</label>
                            <select class="form-control" id="transfer_strand">
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="qe_targetSections"></div>
            
            <div class="d-flex justify-content-between mt-3">
                <button type="button" class="btn btn-default" id="btnStep2Back">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button type="button" class="btn btn-primary" id="btnStep2Next" disabled>
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        `;

        $('#step2').html(html);

        // Event handlers
        $('input[name="enrollmentAction"]').change(function() {
            wizard.enrollmentAction = $(this).val();
            
            if (wizard.enrollmentAction === 'transfer') {
                $('#transferOptions').slideDown(200);
                loadTransferOptions();
            } else {
                $('#transferOptions').slideUp(200);
                loadTargetSectionsForWizard();
            }
        });

        // Attach button handlers
        $('#btnStep2Back').off('click').on('click', function() {
            $('.enrollment-step').hide();
            $('#step1').show();
            updateStepIndicator(1);
        });

        $('#btnStep2Next').off('click').on('click', function() {
            showConfirmationStep();
        });

        // Load initial sections based on default action
        loadTargetSectionsForWizard();
    }

    function loadTransferOptions() {
        // This will be populated when we get target sections
        // Just trigger the load
        loadTargetSectionsForWizard();
    }

    $(document).on('change', '#transfer_level, #transfer_strand', function() {
        wizard.targetLevelId = parseInt($('#transfer_level').val());
        wizard.targetStrandId = parseInt($('#transfer_strand').val());
        loadTargetSectionsForWizard();
    });

    function loadTargetSectionsForWizard() {
        $('#qe_targetSections').html(`
            <div class="text-center py-4" id="sectionsLoader">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p class="mt-2">Loading sections...</p>
            </div>
        `);

        const requestData = {
            _token: API_ROUTES.csrfToken,
            strand_id: wizard.sourceStrandId,
            current_level_id: wizard.sourceLevelId,
            semester_id: wizard.targetSemesterId,
            source_semester_id: wizard.sourceSemesterId,
            enrollment_action: wizard.enrollmentAction
        };

        if (wizard.enrollmentAction === 'transfer') {
            requestData.target_strand_id = wizard.targetStrandId || wizard.sourceStrandId;
            requestData.target_level_id = wizard.targetLevelId || wizard.sourceLevelId;
        }

        $.ajax({
            url: API_ROUTES.getTargetSections,
            type: 'POST',
            data: requestData,
            success: function(response) {
                if (response.success) {
                    wizard.isPromotionScenario = response.is_promotion_scenario;
                    wizard.allStrands = response.all_strands;
                    wizard.allLevels = response.all_levels;
                    
                    // Populate transfer dropdowns if transfer mode
                    if (wizard.enrollmentAction === 'transfer') {
                        populateTransferDropdowns(response);
                    }
                    
                    // Clear loading and show sections
                    $('#sectionsLoader').remove();
                    
                    // Show action-specific info
                    let infoHtml = '';
                    if (wizard.enrollmentAction === 'promote' && wizard.isPromotionScenario) {
                        infoHtml = `
                            <div class="alert alert-default-secondary mb-3">
                                <i class="fas fa-level-up-alt"></i>
                                <strong>Promotion:</strong> Students will be promoted to the next level
                            </div>
                        `;
                    } else if (wizard.enrollmentAction === 'retain') {
                        infoHtml = `
                            <div class="alert alert-default-secondary mb-3">
                                <i class="fas fa-redo"></i>
                                <strong>Retention:</strong> Students will stay at the same level and strand
                            </div>
                        `;
                    } else if (wizard.enrollmentAction === 'transfer') {
                        infoHtml = `
                            <div class="alert alert-default-secondary mb-3">
                                <i class="fas fa-exchange-alt"></i>
                                <strong>Transfer:</strong> Students will be transferred to a different level or strand
                            </div>
                        `;
                    }
                    
                    $('#qe_targetSections').html(infoHtml);
                    displayTargetSections(response.sections);
                }
            },
            error: function(xhr) {
                $('#qe_targetSections').html(`
                    <div class="alert alert-primary">Failed to load sections</div>
                `);
            }
        });
    }

    function populateTransferDropdowns(response) {
        // Populate levels
        let levelHtml = '';
        response.all_levels.forEach(level => {
            const selected = level.id == response.target_level_id ? 'selected' : '';
            levelHtml += `<option value="${level.id}" ${selected}>${level.name}</option>`;
        });
        $('#transfer_level').html(levelHtml);

        // Populate strands
        let strandHtml = '';
        response.all_strands.forEach(strand => {
            const selected = strand.id == response.target_strand_id ? 'selected' : '';
            strandHtml += `<option value="${strand.id}" ${selected}>${strand.code} - ${strand.name}</option>`;
        });
        $('#transfer_strand').html(strandHtml);
    }

    function displayTargetSections(sections) {
        if (sections.length === 0) {
            $('#qe_targetSections').append(`
                <p class="text-muted text-center py-4">No available sections</p>
            `);
            $('#btnStep2Next').prop('disabled', true);
            return;
        }

        // Add header
        $('#qe_targetSections').append(`
            <h6 class="mb-3"><i class="fas fa-bullseye"></i> Select Target Section</h6>
        `);

        let html = '';
        sections.forEach(section => {
            const selectedCount = wizard.selectedStudents.length;
            const currentEnrolled = section.enrolled_count;
            const afterEnroll = currentEnrolled + selectedCount;
            const percentage = (afterEnroll / section.capacity) * 100;
            const willExceed = afterEnroll > section.capacity;

            let warningText = '';
            if (currentEnrolled > 0) {
                warningText = `
                    <div class="alert alert-default-secondary mb-2 py-2">
                        <i class="fas fa-info-circle"></i>
                        <strong>${currentEnrolled}</strong> student(s) already enrolled in this section
                    </div>
                `;
            }

            html += `
                <div class="section-select-card ${willExceed ? 'border-primary' : ''}" 
                     data-section-id="${section.id}" 
                     data-section-name="${section.name}"
                     data-capacity="${section.capacity}" 
                     data-enrolled="${currentEnrolled}">
                    ${warningText}
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="mb-1">${section.name}</h6>
                            <small class="text-muted">${section.code}</small>
                            ${section.strand_code ? `<br><small class="text-muted">${section.level_name} - ${section.strand_code}</small>` : ''}
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <small class="text-muted">
                                    <strong>Current:</strong> ${currentEnrolled} / ${section.capacity}
                                    <br>
                                    <strong>Adding:</strong> ${selectedCount} student(s)
                                    <br>
                                    <strong>After:</strong> <strong class="${willExceed ? 'text-primary' : ''}">
                                        ${afterEnroll} / ${section.capacity}
                                    </strong>
                                    ${willExceed ? '<br><span class="badge badge-primary">Exceeds capacity</span>' : ''}
                                </small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar ${willExceed ? 'bg-primary' : 'bg-secondary'}" 
                                     style="width: ${Math.min(percentage, 100)}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#qe_targetSections').append(html);

        $('.section-select-card').off('click').on('click', function() {
            $('.section-select-card').removeClass('selected');
            $(this).addClass('selected');
            
            wizard.targetSection = $(this).data('section-id');
            wizard.targetSectionData = {
                name: $(this).data('section-name'),
                capacity: $(this).data('capacity'),
                enrolled: $(this).data('enrolled')
            };
            
            $('#btnStep2Next').prop('disabled', false);
        });

        $('#btnStep2Next').prop('disabled', true);
    }

    function showConfirmationStep() {
        $('.enrollment-step').hide();
        $('#step3').show();
        updateStepIndicator(3);

        const afterEnroll = wizard.targetSectionData.enrolled + wizard.selectedStudents.length;

        $('#confirm_studentCount').text(wizard.selectedStudents.length);
        $('#confirm_targetSection').text(wizard.targetSectionData.name);
        $('#confirm_capacity').text(`${afterEnroll} / ${wizard.targetSectionData.capacity}`);

        let studentListHtml = '<div class="row">';
        wizard.selectedStudents.forEach((s, i) => {
            const fullName = `${s.last_name}, ${s.first_name} ${s.middle_name || ''}`.trim();
            const icon = s.gender === 'Male' ? 'fa-mars' : 'fa-venus';
            
            if (i % 2 === 0 && i > 0) studentListHtml += '</div><div class="row">';
            
            studentListHtml += `
                <div class="col-md-6 mb-2">
                    <i class="fas ${icon}"></i>
                    <strong>${s.student_number}</strong> - ${fullName}
                </div>
            `;
        });
        studentListHtml += '</div>';

        $('#confirm_studentList').html(studentListHtml);
    }

    $('#btnStep3Back').click(function() {
        $('.enrollment-step').hide();
        $('#step2').show();
        updateStepIndicator(2);
    });

    $('#btnEnrollConfirm').click(function() {
        processEnrollment();
    });

    function processEnrollment() {
        const studentData = wizard.selectedStudents.map(s => ({
            student_number: s.student_number,
            new_section_id: wizard.targetSection,
            student_type: s.student_type || 'regular'
        }));

        Swal.fire({
            title: 'Enrolling Students',
            html: `
                <div class="progress" style="height: 30px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                         style="width: 100%">Processing...</div>
                </div>
            `,
            allowOutsideClick: false,
            showConfirmButton: false
        });

        $.ajax({
            url: API_ROUTES.enrollStudents,
            type: 'POST',
            data: {
                _token: API_ROUTES.csrfToken,
                semester_id: wizard.targetSemesterId,
                section_id: wizard.targetSection,
                students: studentData
            },
            success: function(response) {
                if (response.success) {
                    if (response.skipped > 0) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Enrollment Complete',
                            html: `
                                <p><strong>${response.enrolled}</strong> students enrolled successfully</p>
                                <p class="text-warning"><strong>${response.skipped}</strong> students were already enrolled (skipped)</p>
                            `,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            $('#quickEnrollModal').modal('hide');
                            loadArchiveInfo();
                        });
                    } else {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: `${response.enrolled} students enrolled`,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            $('#quickEnrollModal').modal('hide');
                            loadArchiveInfo();
                        });
                    }
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Failed to enroll'
                });
            }
        });
    }

    // =========================================================================
    // ACTIVATE/COMPLETE
    // =========================================================================
    
    function activateSemester(id) {
        const semester = semestersData.find(s => s.id == id);

        Swal.fire({
            title: 'Activate Semester?',
            html: `
                <p>Activate <strong>${semester.name}</strong>?</p>
                <p class="small text-muted">This will deactivate all other semesters</p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, activate',
            confirmButtonColor: '#007bff'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.activateSemester.replace(':id', id);

                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
                    success: function (response) {
                        if (response.success) {
                            showToast('success', 'Semester activated');
                            loadArchiveInfo();
                        }
                    },
                    error: function (xhr) {
                        showToast('error', xhr.responseJSON?.message || 'Failed');
                    }
                });
            }
        });
    }

    // =========================================================================
    // HELPERS
    // =========================================================================
    
    function getStatusBadge(status) {
        const badges = {
            'active': '<span class="badge badge-primary">ACTIVE</span>',
            'completed': '<span class="badge badge-dark">COMPLETED</span>',
            'upcoming': '<span class="badge badge-secondary">UPCOMING</span>'
        };
        return badges[status] || '';
    }

    function showToast(type, message) {
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

        const iconMap = {
            'success': 'success',
            'error': 'error',
            'warning': 'warning',
            'info': 'info'
        };

        Toast.fire({
            icon: iconMap[type] || 'info',
            title: message
        });
    }
});