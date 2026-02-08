$(document).ready(function () {
    let schoolYearData = null;
    let semestersData = [];
    let semesterDetailsCache = {};
    let sourceSemesterData = SOURCE_SEMESTER; // From blade - active semester
    let targetSemesterData = TARGET_SEMESTER; // From blade

    // Enrollment wizard state
    let wizard = {
        currentStep: 1,
        sourceSection: null,
        sourceSemesterId: sourceSemesterData?.id || null,
        students: [],
        selectedStudents: [],
        targetSection: null,
        targetSectionData: null,
        targetSemesterId: targetSemesterData?.id || null,
        sourceStrandId: null,
        sourceLevelId: null
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
            }
        });
    }

    function displayArchiveInfo() {
        $('#contentLoading').hide();
        
        // Header
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

        // Load first semester
        loadSemesterDetails(semestersData[0].id);

        // Load on tab change
        $('#semesterTabs a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
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
        const canArchive = semester.status === 'upcoming';
        
        let html = `
            <!-- Actions -->
            ${canActivate || canArchive ? `
                <div class="mb-3 text-right">
                    ${canActivate ? `
                        <button class="btn btn-primary activate-sem" data-id="${semesterId}">
                            <i class="fas fa-play"></i> Activate
                        </button>
                    ` : ''}
                    ${canArchive ? `
                        <button class="btn btn-secondary archive-sem" data-id="${semesterId}">
                            <i class="fas fa-archive"></i> Archive
                        </button>
                    ` : ''}
                </div>
            ` : ''}

            <!-- Sections -->
            ${buildSectionsTable(semesterId, data.sections)}
        `;

        $(`#sem-${semesterId}-content`).html(html);

        // Attach events
        $('.activate-sem').click(function() {
            activateSemester($(this).data('id'));
        });

        $('.archive-sem').click(function() {
            archiveSemester($(this).data('id'));
        });

        $('.section-row').click(function() {
            toggleSectionDetails($(this));
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
                    <h6 class="text-muted mb-2">
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
                    <h6 class="text-muted mb-2">
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
    
    $('#quickEnrollBtn').click(function() {
        if (!wizard.targetSemesterId) {
            showToast('warning', 'No target semester available for enrollment');
            return;
        }
        if (!wizard.sourceSemesterId) {
            showToast('warning', 'No active semester found as source');
            return;
        }
        resetWizard();
        $('#quickEnrollModal').modal('show');
    });

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
            sourceLevelId: null
        };

        $('.enrollment-step').hide();
        $('#step1').show();
        updateStepIndicator(1);
        
        $('#qe_source_section').val(null).trigger('change');
        $('#studentSelectionArea').hide();
        $('#btnStep1Next').prop('disabled', true);
        
        // Display semester info
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
                <span class="text-muted"><i class="fas fa-exclamation-circle"></i> No active semester found</span>
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
                        text: `${s.name} (${s.code}) - ${s.level_name} ${s.strand_code}`
                    }))
                };
            }
        }
    }).on('select2:select', function(e) {
        wizard.sourceSection = e.params.data.id;
        loadWizardStudents();
    }).on('select2:clear', function() {
        wizard.sourceSection = null;
        $('#studentSelectionArea').hide();
        $('#btnStep1Next').prop('disabled', true);
    });

    function loadWizardStudents() {
        $('#studentSelectionArea').show();
        $('#qe_studentList').html(`
            <tr>
                <td colspan="5" class="text-center py-4">
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
                source_semester_id: wizard.sourceSemesterId
            },
            success: function(response) {
                if (response.success) {
                    wizard.students = response.students.map(s => ({...s, selected: true}));
                    wizard.selectedStudents = [...wizard.students];
                    displayWizardStudents();
                    loadSectionDetailsForWizard();
                }
            },
            error: function() {
                showToast('error', 'Failed to load students');
                $('#studentSelectionArea').hide();
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
                }
            }
        });
    }

    function displayWizardStudents() {
        if (wizard.students.length === 0) {
            $('#qe_studentList').html(`
                <tr><td colspan="5" class="text-center text-muted py-4">No students found</td></tr>
            `);
            $('#btnStep1Next').prop('disabled', true);
            return;
        }

        let html = '';
        wizard.students.forEach((s, i) => {
            const fullName = `${s.last_name}, ${s.first_name} ${s.middle_name || ''}`.trim();
            const genderIcon = s.gender === 'Male' ? 'fa-mars' : 'fa-venus';
            
            html += `
                <tr class="student-row" data-index="${i}" data-gender="${s.gender.toLowerCase()}">
                    <td>
                        <input type="checkbox" class="student-checkbox" ${s.selected ? 'checked' : ''}>
                    </td>
                    <td>${s.student_number}</td>
                    <td>${fullName}</td>
                    <td><i class="fas ${genderIcon}"></i> ${s.gender}</td>
                    <td><span class="badge badge-secondary">${s.student_type}</span></td>
                </tr>
            `;
        });

        $('#qe_studentList').html(html);
        updateStudentCount();
        $('#btnStep1Next').prop('disabled', false);
    }

    $(document).on('change', '.student-checkbox', function() {
        const index = $(this).closest('tr').data('index');
        wizard.students[index].selected = $(this).is(':checked');
        updateSelectedStudents();
    });

    $('#selectAllCheckbox').change(function() {
        const checked = $(this).is(':checked');
        $('.student-checkbox:visible').prop('checked', checked);
        $('.student-row:visible').each(function() {
            const index = $(this).data('index');
            wizard.students[index].selected = checked;
        });
        updateSelectedStudents();
    });

    $('#qe_selectAll').click(function() {
        $('.student-checkbox:visible').prop('checked', true);
        $('.student-row:visible').each(function() {
            const index = $(this).data('index');
            wizard.students[index].selected = true;
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
        const visibleCheckboxes = $('.student-checkbox:visible');
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
        const total = wizard.students.length;
        $('#qe_studentCount').text(`${count} / ${total} selected`);
    }

    $('#btnStep1Next').click(function() {
        loadTargetSectionsForWizard();
    });

    function loadTargetSectionsForWizard() {
        $('.enrollment-step').hide();
        $('#step2').show();
        updateStepIndicator(2);

        $.ajax({
            url: API_ROUTES.getTargetSections,
            type: 'POST',
            data: {
                _token: API_ROUTES.csrfToken,
                strand_id: wizard.sourceStrandId,
                current_level_id: wizard.sourceLevelId,
                semester_id: wizard.targetSemesterId,
                source_semester_id: wizard.sourceSemesterId
            },
            success: function(response) {
                if (response.success) {
                    // Show promotion message if applicable
                    if (response.is_promotion) {
                        $('#qe_targetSections').html(`
                            <div class="alert alert-default-secondary mb-3">
                                <i class="fas fa-level-up-alt"></i>
                                <strong>Promotion:</strong> Students will be promoted to the next level
                            </div>
                        `);
                    } else {
                        $('#qe_targetSections').html('');
                    }
                    
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

    function displayTargetSections(sections) {
        if (sections.length === 0) {
            $('#qe_targetSections').append(`
                <p class="text-muted text-center py-4">No available sections</p>
            `);
            return;
        }

        let html = '';
        sections.forEach(section => {
            const selectedCount = wizard.selectedStudents.length;
            const currentEnrolled = section.enrolled_count;
            const afterEnroll = currentEnrolled + selectedCount;
            const percentage = (afterEnroll / section.capacity) * 100;

            const willExceed = afterEnroll > section.capacity;

            // Show warning if section already has students
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

        $('.section-select-card').click(function() {
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

    $('#btnStep2Back').click(function() {
        $('.enrollment-step').hide();
        $('#step1').show();
        updateStepIndicator(1);
    });

    $('#btnStep2Next').click(function() {
        showConfirmationStep();
    });

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
    // ACTIVATE/ARCHIVE
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

    function archiveSemester(id) {
        const semester = semestersData.find(s => s.id == id);

        Swal.fire({
            title: 'Archive Semester?',
            html: `
                <p>Archive <strong>${semester.name}</strong>?</p>
                <p class="small text-muted">Data will be preserved but marked as completed</p>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, archive',
            confirmButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                const url = API_ROUTES.archiveSemester.replace(':id', id);

                $.ajax({
                    url: url,
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': API_ROUTES.csrfToken },
                    success: function (response) {
                        if (response.success) {
                            showToast('success', 'Semester archived');
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