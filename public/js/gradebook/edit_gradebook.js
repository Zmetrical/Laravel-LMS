console.log("gradebook edit");

$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let gradebookData = null;
    let classInfo = null;
    let quarterInfo = null;
    let currentQuarterId = null;
    let currentSectionId = null;
    let pendingChanges = {};
    let wwGrid, ptGrid, qaGrid;

    // Initialize with first quarter but don't load data yet
    if (QUARTERS.length > 0) {
        currentQuarterId = QUARTERS[0].id;
        $('.quarter-btn').first().addClass('btn-secondary active').removeClass('btn-outline-secondary');
    }

    // Show initial empty state
    showEmptyState();

    // Quarter button group click
    $('.quarter-btn').click(function () {
        const quarterId = $(this).data('quarter');
        
        if (!currentSectionId) {
            Swal.fire({
                icon: 'warning',
                title: 'Section Required',
                text: 'Please select a section first',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            return;
        }
        
        if (quarterId === currentQuarterId) return;

        $('.quarter-btn').removeClass('btn-secondary active').addClass('btn-outline-secondary');
        $(this).addClass('btn-secondary active').removeClass('btn-outline-secondary');
        
        currentQuarterId = quarterId;
        loadGradebook(currentQuarterId);
    });

    $('#viewBtn').on('click', function() {
        window.location.href = API_ROUTES.viewGradebook;
    });

    // Section filter
    $('#sectionFilter').change(function () {
        currentSectionId = $(this).val();
        
        if (!currentSectionId) {
            Swal.fire({
                icon: 'warning',
                title: 'Section Required',
                text: 'Please select a section',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            showEmptyState();
            return;
        }
        
        if (currentQuarterId) {
            loadGradebook(currentQuarterId);
        }
    });

    function showEmptyState() {
        const emptyHtml = `
            <div style="padding: 60px 20px; text-align: center;">
                <i class="fas fa-table" style="font-size: 48px; color: #6c757d; margin-bottom: 15px;"></i>
                <h5 style="color: #6c757d; margin-bottom: 10px;">No Section Selected</h5>
                <p style="color: #adb5bd;">Please select a section from the dropdown above to edit grades.</p>
            </div>
        `;
        $('#wwGrid, #ptGrid, #qaGrid').html(emptyHtml);
    }

    function loadGradebook(quarterId) {
        if (!currentSectionId) {
            Swal.fire({
                icon: 'warning',
                title: 'Section Required',
                text: 'Please select a section first',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
            return;
        }

        showTabLoading();
        
        // Destroy existing grids first
        destroyAllGrids();
        
        $.ajax({
            url: API_ROUTES.getGradebook,
            type: 'GET',
            data: { 
                quarter_id: quarterId,
                section_id: currentSectionId
            },
            success: function (response) {
                if (response.success) {
                    gradebookData = response.data;
                    classInfo = response.data.class;
                    quarterInfo = response.data.quarter;
                    currentQuarterId = quarterId;
                    pendingChanges = {};

                    if (!response.data.students || response.data.students.length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'No Students',
                            text: 'No students enrolled in this section',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                        clearAllGrids();
                    } else {
                        initializeGrids(gradebookData);
                    }
                    
                    updateSaveButton();
                    updateColumnCounts();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to load gradebook'
                    });
                    clearAllGrids();
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to load gradebook data';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
                console.error(xhr);
                clearAllGrids();
            }
        });
    }

    function showTabLoading() {
        const loadingHtml = '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        $('#wwGrid, #ptGrid, #qaGrid').html(loadingHtml);
    }

    function clearAllGrids() {
        const emptyHtml = '<div style="text-align:center;padding:40px;">No data available</div>';
        $('#wwGrid, #ptGrid, #qaGrid').html(emptyHtml);
    }

    function updateColumnCounts() {
        const wwActive = (gradebookData.columns['WW'] || []).filter(c => c.is_active).length;
        const ptActive = (gradebookData.columns['PT'] || []).filter(c => c.is_active).length;
        const qaActive = (gradebookData.columns['QA'] || []).filter(c => c.is_active).length;

        $('#wwColumnCount').text(`${wwActive}/${MAX_WW_COLUMNS} active`);
        $('#ptColumnCount').text(`${ptActive}/${MAX_PT_COLUMNS} active`);
        $('#qaColumnCount').text(`${qaActive}/${MAX_QA_COLUMNS} active`);
    }

    function initializeGrids(data) {
        initGrid('WW', '#wwGrid', data);
        initGrid('PT', '#ptGrid', data);
        initGrid('QA', '#qaGrid', data);
    }

    function initGrid(componentType, selector, data) {
        const columns = data.columns[componentType] || [];
        const students = data.students || [];

        // Group students by gender
        const maleStudents = students.filter(s => s.gender && s.gender.toLowerCase() === 'male');
        const femaleStudents = students.filter(s => s.gender && s.gender.toLowerCase() === 'female');
        const otherStudents = students.filter(s => !s.gender || (s.gender.toLowerCase() !== 'male' && s.gender.toLowerCase() !== 'female'));

        let fields = [
            {
                name: "student_number",
                title: "USN",
                type: "text",
                width: 100,
                editing: false,
                css: "student-info"
            },
            {
                name: "full_name",
                title: "Student Name",
                type: "text",
                width: 200,
                editing: false,
                css: "student-info"
            }
        ];

        let totalMaxPoints = 0;

        columns.forEach(col => {
            if (col.is_active) {
                totalMaxPoints += parseFloat(col.max_points);
            }

            const isOnline = col.source_type === 'online';
            const isDisabled = !col.is_active;

            fields.push({
                name: col.column_name,
                title: createColumnHeader(col),
                type: "number",
                width: 90,
                editing: !isOnline && col.is_active,
                itemTemplate: function (value, item) {
                    if (item._isGenderSeparator) {
                        return '';
                    }

                    if (isDisabled) {
                        return '<span class="text-muted">-</span>';
                    }

                    const cellId = `cell_${col.id}_${item.student_number}`;
                    const val = value !== null && value !== undefined ? value : '';
                    const key = `${col.id}_${item.student_number}`;
                    const isChanged = pendingChanges.hasOwnProperty(key);

                    if (isOnline) {
                        return `<span class="badge badge-primary">${val}</span>`;
                    }

                    const changedClass = isChanged ? 'changed-cell-value' : '';
                    return `<span id="${cellId}" class="${changedClass}">${val}</span>`;
                },
                editTemplate: function (value, item) {
                    if (item._isGenderSeparator || isDisabled || isOnline) {
                        return `<span class="text-muted">-</span>`;
                    }

                    const cellId = `cell_${col.id}_${item.student_number}`;
                    const input = $('<input>')
                        .attr('type', 'number')
                        .attr('min', '0')
                        .attr('max', col.max_points)
                        .attr('step', '0.01')
                        .attr('data-cell-id', cellId)
                        .attr('data-column-id', col.id)
                        .attr('data-student', item.student_number)
                        .addClass('form-control form-control-sm')
                        .val(value !== null && value !== undefined ? value : '')
                        .on('input', function () {
                            const val = parseFloat($(this).val());
                            if (val < 0) $(this).val(0);
                            if (val > col.max_points) $(this).val(col.max_points);
                        })
                        .on('blur', function () {
                            const newVal = $(this).val();
                            const oldVal = value !== null && value !== undefined ? value : '';

                            if (newVal !== oldVal.toString()) {
                                markChanged(col.id, item.student_number, newVal, cellId);
                                item[col.column_name] = newVal === '' ? null : parseFloat(newVal);
                                
                                // Don't refresh entire grid, just update the display
                                $(`#${cellId}`).text(newVal === '' ? '' : parseFloat(newVal)).addClass('changed-cell-value');
                            }
                        })
                        .on('keypress', function (e) {
                            if (e.which === 13) {
                                $(this).blur();
                            }
                        });

                    return input;
                },
                headerCss: isOnline ? 'online-column' : (isDisabled ? 'disabled-column' : '')
            });
        });

        fields.push(
            {
                name: "total",
                title: `<div class="column-header"><span class="column-title">Total</span><span class="column-points">${totalMaxPoints}pts</span></div>`,
                type: "text",
                width: 80,
                editing: false,
                css: "total-cell",
                itemTemplate: function (value, item) {
                    if (item._isGenderSeparator) {
                        return '';
                    }

                    let total = 0;
                    columns.forEach(col => {
                        if (col.is_active) {
                            const score = item[col.column_name];
                            if (score !== null && score !== undefined && score !== '') {
                                total += parseFloat(score);
                            }
                        }
                    });
                    return `${total.toFixed(2)}`;
                },
                headerCss: "component-header"
            },
            {
                name: "percentage",
                title: `<div class="column-header"><span class="column-title">Score</span><span class="column-points">%</span></div>`,
                type: "text",
                width: 80,
                editing: false,
                css: "total-cell",
                itemTemplate: function (value, item) {
                    if (item._isGenderSeparator) {
                        return '';
                    }

                    let total = 0;
                    columns.forEach(col => {
                        if (col.is_active) {
                            const score = item[col.column_name];
                            if (score !== null && score !== undefined && score !== '') {
                                total += parseFloat(score);
                            }
                        }
                    });
                    const perc = totalMaxPoints > 0 ? (total / totalMaxPoints * 100).toFixed(2) : '0.00';
                    return `${perc}%`;
                },
                headerCss: "component-header"
            }
        );

        // Build grid data with gender separators
        let gridData = [];

        if (maleStudents.length > 0) {
            gridData.push({
                _isGenderSeparator: true,
                student_number: '<i class="fas fa-mars"></i> MALE',
                full_name: ''
            });
            maleStudents.forEach(student => {
                gridData.push(buildStudentRow(student, columns, componentType));
            });
        }

        if (femaleStudents.length > 0) {
            gridData.push({
                _isGenderSeparator: true,
                student_number: '<i class="fas fa-venus"></i> FEMALE',
                full_name: ''
            });
            femaleStudents.forEach(student => {
                gridData.push(buildStudentRow(student, columns, componentType));
            });
        }

        if (otherStudents.length > 0) {
            gridData.push({
                _isGenderSeparator: true,
                student_number: '<i class="fas fa-user"></i> OTHER',
                full_name: ''
            });
            otherStudents.forEach(student => {
                gridData.push(buildStudentRow(student, columns, componentType));
            });
        }

        $(selector).jsGrid({
            width: "100%",
            height: "auto",
            editing: true,
            sorting: false,
            paging: false,
            autoload: true,
            data: gridData,
            fields: fields,
            rowClass: function(item) {
                return item._isGenderSeparator ? 'gender-separator' : '';
            }
        });

        if (componentType === 'WW') wwGrid = $(selector).data('JSGrid');
        if (componentType === 'PT') ptGrid = $(selector).data('JSGrid');
        if (componentType === 'QA') qaGrid = $(selector).data('JSGrid');
    }

    function buildStudentRow(student, columns, componentType) {
        const row = {
            student_number: student.student_number,
            full_name: student.full_name,
            _isGenderSeparator: false
        };

        columns.forEach(col => {
            const scoreData = student[componentType.toLowerCase()][col.column_name] || {};
            row[col.column_name] = scoreData.score !== null ? parseFloat(scoreData.score) : null;
        });

        return row;
    }

    function createColumnHeader(col) {
        const badge = col.source_type === 'online' ? '<span class="badge badge-primary online-badge">Online</span>' : '';
        const statusIcon = col.is_active ?
            '<i class="fas fa-toggle-on text-white toggle-column-btn" title="Disable column"></i>' :
            '<i class="fas fa-toggle-off text-secondary toggle-column-btn" title="Enable column"></i>';
        const editIcon = col.is_active && col.source_type === 'manual' ?
            `<i class="fas fa-edit edit-column-btn ml-1" data-column-id="${col.id}" title="Edit column"></i>` : '';

        return `<div class="column-header" data-column-id="${col.id}">
            <span class="column-title ${!col.is_active ? 'text-muted' : ''}">${col.column_name}</span>
            ${badge}
            <span class="column-points ${!col.is_active ? 'text-muted' : ''}">${col.max_points}pts</span>
            <div class="mt-1">${statusIcon}${editIcon}</div>
        </div>`;
    }

    function markChanged(columnId, studentNumber, score, cellId) {
        const key = `${columnId}_${studentNumber}`;
        pendingChanges[key] = {
            column_id: columnId,
            student_number: studentNumber,
            score: score === '' ? null : parseFloat(score)
        };

        updateSaveButton();
    }

    function updateSaveButton() {
        const count = Object.keys(pendingChanges).length;
        if (count > 0) {
            $('#saveChangesBtn').show();
            $('#saveChangesText').text(`Save ${count} Change${count > 1 ? 's' : ''}`);
        } else {
            $('#saveChangesBtn').hide();
        }
    }

    // Toggle column enable/disable
    $(document).on('click', '.toggle-column-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const columnHeader = $(this).closest('.column-header');
        const columnId = columnHeader.data('column-id');
        const column = findColumnById(columnId);

        if (!column) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Column not found'
            });
            return false;
        }

        if (!column.is_active) {
            openEnableColumnModal(column);
        } else {
            Swal.fire({
                title: 'Disable Column?',
                text: 'Scores will be hidden from calculations.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#6c757d',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, disable it'
            }).then((result) => {
                if (result.isConfirmed) {
                    toggleColumnStatus(columnId, false);
                }
            });
        }

        return false;
    });

function openEnableColumnModal(column) {
    $('#columnModalTitle').html('<i class="fas fa-toggle-on"></i> Enable Column: ' + column.column_name);
    $('#columnId').val(column.id);
    $('#columnName').text(column.column_name);
    $('#maxPoints').val(column.max_points);
    $('#columnModalBtn').html('<i class="fas fa-check"></i> Enable Column');
    
    // Reset form
    $('#gradeType').val('');
    $('#onlineQuizGroup').hide();
    $('#importFileGroup').hide();
    $('#quizId').html('<option value="">Select Quiz</option>');
    $('.custom-file-label[for="importFile"]').text('Choose file...');
    $('#importFile').val('');
    
    $('#columnModal').modal('show');
}


// File input change handler for enable modal - REPLACE existing handler
$('#enableImportFile').on('change', function() {
    const fileName = $(this).val().split('\\').pop();
    const label = $(this).siblings('.custom-file-label');
    if (fileName) {
        label.text(fileName);
    } else {
        label.text('Choose file...');
    }
});

// Grade source change handler
$('#enableGradeSource').change(function() {
    const source = $(this).val();
    
    $('#enableOnlineQuizGroup').hide();
    $('#enableImportGroup').hide();
    
    if (source === 'online') {
        loadQuizzesForEnable(null);
        $('#enableOnlineQuizGroup').show();
    } else if (source === 'import') {
        $('#enableImportGroup').show();
    }
});


function loadQuizzesForEnable(currentQuizId) {
    if (!currentQuarterId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please select a quarter first'
        });
        $('#enableQuizId').html('<option value="">Select Quiz</option>');
        return;
    }

    $('#enableQuizId').html('<option value="">Loading quizzes...</option>');

    $.ajax({
        url: API_ROUTES.getQuizzes,
        type: 'GET',
        data: { quarter_id: currentQuarterId },
        success: function (response) {
            let options = '<option value="">Select Quiz</option>';

            if (response.success) {
                if (response.data && response.data.length > 0) {
                    response.data.forEach(quiz => {
                        const selected = quiz.id == currentQuizId ? 'selected' : '';
                        const points = quiz.total_points || 0;
                        options += `<option value="${quiz.id}" ${selected} data-points="${points}">
                            ${escapeHtml(quiz.lesson_title)} - ${escapeHtml(quiz.title)} (${points} pts)
                        </option>`;
                    });
                    Swal.fire({
                        icon: 'info',
                        title: 'Quizzes Available',
                        text: `Found ${response.data.length} available quiz(zes)`,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Quizzes',
                        text: response.message || 'No quizzes available for this quarter',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Failed to load quizzes'
                });
            }

            $('#enableQuizId').html(options);
        },
        error: function (xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to load available quizzes';
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMsg
            });
            $('#enableQuizId').html('<option value="">Select Quiz</option>');
        }
    });
}


// Quiz selection change handler
$('#enableQuizId').change(function () {
    const selected = $(this).find('option:selected');
    const quizId = selected.val();

    if (quizId) {
        const points = selected.data('points');
        if (points && points > 0) {
            $('#enableMaxPoints').val(points);
            Swal.fire({
                icon: 'info',
                title: 'Max Points Updated',
                text: `Max points set to ${points} from quiz`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    }
});
$('#columnForm').submit(function (e) {
    e.preventDefault();

    const columnId = $('#columnId').val();
    const maxPoints = parseInt($('#maxPoints').val());
    const gradeType = $('#gradeType').val();
    const quizId = $('#quizId').val();
    const importFile = $('#importFile')[0].files[0];
    const isEnabling = $('#columnModalBtn').text().includes('Enable');

    // Validation
    if (!gradeType) {
        Swal.fire({
            icon: 'warning',
            title: 'Grade Type Required',
            text: 'Please select a grade type'
        });
        return;
    }

    if (gradeType === 'online' && !quizId) {
        Swal.fire({
            icon: 'warning',
            title: 'Quiz Required',
            text: 'Please select an online quiz'
        });
        return;
    }

    if (!currentSectionId) {
        Swal.fire({
            icon: 'warning',
            title: 'Section Required',
            text: 'Please select a section first'
        });
        return;
    }

    const btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

    // Prepare data for enable/update
    const columnData = {
        max_points: maxPoints,
        quiz_id: gradeType === 'online' ? quizId : null
    };

    if (isEnabling) {
        columnData.is_active = 1;
    }

    // Determine API endpoint
    const apiUrl = isEnabling 
        ? API_ROUTES.toggleColumn.replace('__COLUMN_ID__', columnId)
        : API_ROUTES.updateColumn.replace('__COLUMN_ID__', columnId);
    
    const method = isEnabling ? 'POST' : 'PUT';

    $.ajax({
        url: apiUrl,
        type: method,
        data: columnData,
        success: function (response) {
            if (response.success) {
                // If face-to-face with import file, import it
                if (gradeType === 'face-to-face' && importFile) {
                    importColumnFile(columnId, importFile, btn);
                } else {
                    // No import needed, show success and reload
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: isEnabling ? 'Column enabled successfully' : 'Column updated successfully',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    $('#columnModal').modal('hide');
                    destroyAllGrids();
                    loadGradebook(currentQuarterId);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Failed to process request'
                });
                btn.prop('disabled', false).html($('#columnModalBtn').html());
            }
        },
        error: function (xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to process request';
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMsg
            });
            btn.prop('disabled', false).html($('#columnModalBtn').html());
        }
    });
});
    // Enable column form submit - REPLACE existing handler
$('#enableColumnForm').submit(function (e) {
    e.preventDefault();

    const columnId = $('#enableColumnId').val();
    const maxPoints = parseInt($('#enableMaxPoints').val());
    const gradeSource = $('#enableGradeSource').val();
    const quizId = $('#enableQuizId').val();
    const importFile = $('#enableImportFile')[0].files[0];

    // Validation
    if (!gradeSource) {
        Swal.fire({
            icon: 'warning',
            title: 'Grade Source Required',
            text: 'Please select a grade source'
        });
        return;
    }

    if (gradeSource === 'online' && !quizId) {
        Swal.fire({
            icon: 'warning',
            title: 'Quiz Required',
            text: 'Please select an online quiz'
        });
        return;
    }

    if (gradeSource === 'import' && !importFile) {
        Swal.fire({
            icon: 'warning',
            title: 'File Required',
            text: 'Please select a file to import'
        });
        return;
    }

    if (!currentSectionId) {
        Swal.fire({
            icon: 'warning',
            title: 'Section Required',
            text: 'Please select a section first'
        });
        return;
    }

    const btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

    // Step 1: Enable the column
    const enableData = {
        is_active: 1,
        max_points: maxPoints,
        quiz_id: gradeSource === 'online' ? quizId : null
    };

    $.ajax({
        url: API_ROUTES.toggleColumn.replace('__COLUMN_ID__', columnId),
        type: 'POST',
        data: enableData,
        success: function (response) {
            if (response.success) {
                // Step 2: If import selected, import the file
                if (gradeSource === 'import') {
                    importColumnFile(columnId, importFile, btn);
                } else {
                    // No import needed, just show success and reload
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Column enabled successfully',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    $('#enableColumnModal').modal('hide');
                    destroyAllGrids();
                    loadGradebook(currentQuarterId);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Failed to enable column'
                });
                btn.prop('disabled', false).html('<i class="fas fa-check"></i> Enable Column');
            }
        },
        error: function (xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to enable column';
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMsg
            });
            btn.prop('disabled', false).html('<i class="fas fa-check"></i> Enable Column');
        }
    });
});
// Import file for enabled column
function importColumnFile(columnId, file, btn) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('section_id', currentSectionId);

    $.ajax({
        url: API_ROUTES.importColumn.replace('__COLUMN_ID__', columnId),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                let message = `Column processed and ${response.data.imported} scores imported`;
                
                if (response.data.skipped > 0) {
                    message += `, ${response.data.skipped} skipped`;
                }

                let icon = 'success';
                if (response.data.errors && response.data.errors.length > 0) {
                    icon = 'warning';
                    message += '\n\nWarnings:\n' + response.data.errors.slice(0, 3).join('\n');
                    if (response.data.errors.length > 3) {
                        message += `\n... and ${response.data.errors.length - 3} more`;
                    }
                }

                Swal.fire({
                    icon: icon,
                    title: 'Import Completed',
                    text: message,
                    confirmButtonText: 'OK'
                });

                $('#columnModal').modal('hide');
                destroyAllGrids();
                loadGradebook(currentQuarterId);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Import Failed',
                    text: response.message || 'Failed to import scores'
                });
                btn.prop('disabled', false).html($('#columnModalBtn').html());
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to import scores';
            Swal.fire({
                icon: 'error',
                title: 'Import Failed',
                text: errorMsg
            });
            btn.prop('disabled', false).html($('#columnModalBtn').html());
        }
    });
}
    function toggleColumnStatus(columnId, isActive, maxPoints = null, quizId = null) {
        const data = {
            is_active: isActive ? 1 : 0
        };

        if (isActive && maxPoints) {
            data.max_points = parseInt(maxPoints);
            data.quiz_id = quizId || null;
        }

        const btn = $('#enableColumnForm button[type="submit"], #disableColumnBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        $.ajax({
            url: API_ROUTES.toggleColumn.replace('__COLUMN_ID__', columnId),
            type: 'POST',
            data: data,
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    $('#enableColumnModal').modal('hide');
                    
                    // Destroy existing grids before reload
                    destroyAllGrids();
                    
                    // Reload gradebook data
                    loadGradebook(currentQuarterId);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to update column'
                    });
                    btn.prop('disabled', false).html('<i class="fas fa-check"></i> Enable Column');
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to update column';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
                btn.prop('disabled', false).html('<i class="fas fa-check"></i> Enable Column');
            }
        });
    }

    function destroyAllGrids() {
        try {
            if (wwGrid) $('#wwGrid').jsGrid('destroy');
            if (ptGrid) $('#ptGrid').jsGrid('destroy');
            if (qaGrid) $('#qaGrid').jsGrid('destroy');
        } catch (e) {
            console.log('Grid destroy error (safe to ignore):', e);
        }
        
        // Clear grid variables
        wwGrid = null;
        ptGrid = null;
        qaGrid = null;
        
        // Clear HTML
        $('#wwGrid, #ptGrid, #qaGrid').empty();
    }

    $(document).on('click', '.edit-column-btn', function (e) {
        e.stopPropagation();
        const columnId = $(this).data('column-id');
        openEditColumnModal(columnId);
    });

function openEditColumnModal(columnId) {
    const column = findColumnById(columnId);
    if (!column) return;

    $('#columnModalTitle').html('<i class="fas fa-edit"></i> Edit Column: ' + column.column_name);
    $('#columnId').val(column.id);
    $('#columnName').text(column.column_name);
    $('#maxPoints').val(column.max_points);
    $('#columnModalBtn').html('<i class="fas fa-save"></i> Update Column');

    // Set grade type based on current source
    if (column.source_type === 'online') {
        $('#gradeType').val('online');
        loadQuizzes(column.quiz_id);
        $('#onlineQuizGroup').show();
        $('#importFileGroup').hide();
    } else {
        $('#gradeType').val('face-to-face');
        $('#onlineQuizGroup').hide();
        $('#importFileGroup').show();
    }

    $('#columnModal').modal('show');
}
$('#gradeType').change(function() {
    const type = $(this).val();
    
    $('#onlineQuizGroup').hide();
    $('#importFileGroup').hide();
    
    if (type === 'online') {
        loadQuizzes(null);
        $('#onlineQuizGroup').show();
    } else if (type === 'face-to-face') {
        $('#importFileGroup').show();
    }
});
function loadQuizzes(currentQuizId) {
    if (!currentQuarterId) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please select a quarter first'
        });
        $('#quizId').html('<option value="">Select Quiz</option>');
        return;
    }

    $('#quizId').html('<option value="">Loading quizzes...</option>');

    $.ajax({
        url: API_ROUTES.getQuizzes,
        type: 'GET',
        data: { quarter_id: currentQuarterId },
        success: function (response) {
            let options = '<option value="">Select Quiz</option>';

            if (response.success) {
                if (response.data && response.data.length > 0) {
                    response.data.forEach(quiz => {
                        const selected = quiz.id == currentQuizId ? 'selected' : '';
                        const points = quiz.total_points || 0;
                        options += `<option value="${quiz.id}" ${selected} data-points="${points}">
                            ${escapeHtml(quiz.lesson_title)} - ${escapeHtml(quiz.title)} (${points} pts)
                        </option>`;
                    });
                    Swal.fire({
                        icon: 'info',
                        title: 'Quizzes Available',
                        text: `Found ${response.data.length} available quiz(zes)`,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No Quizzes',
                        text: response.message || 'No quizzes available for this quarter',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Failed to load quizzes'
                });
            }

            $('#quizId').html(options);
        },
        error: function (xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to load available quizzes';
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMsg
            });
            $('#quizId').html('<option value="">Select Quiz</option>');
        }
    });
}


    function findColumnById(columnId) {
        for (let type of ['WW', 'PT', 'QA']) {
            const cols = gradebookData.columns[type] || [];
            const found = cols.find(c => c.id == columnId);
            if (found) return found;
        }
        return null;
    }
$('#quizId').change(function () {
    const selected = $(this).find('option:selected');
    const quizId = selected.val();

    if (quizId) {
        const points = selected.data('points');
        if (points && points > 0) {
            $('#maxPoints').val(points);
            Swal.fire({
                icon: 'info',
                title: 'Max Points Updated',
                text: `Max points set to ${points} from quiz`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
    }
});
    function loadQuizzesForEdit(currentQuizId) {
        if (!currentQuarterId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please select a quarter first'
            });
            $('#editQuizId').html('<option value="">Manual Entry</option>');
            return;
        }

        $('#editQuizId').html('<option value="">Loading quizzes...</option>');

        $.ajax({
            url: API_ROUTES.getQuizzes,
            type: 'GET',
            data: { quarter_id: currentQuarterId },
            success: function (response) {
                let options = '<option value="">Manual Entry (No Quiz Link)</option>';

                if (response.success) {
                    if (response.data && response.data.length > 0) {
                        response.data.forEach(quiz => {
                            const selected = quiz.id == currentQuizId ? 'selected' : '';
                            const points = quiz.total_points || 0;
                            options += `<option value="${quiz.id}" ${selected} data-points="${points}">
                                ${escapeHtml(quiz.lesson_title)} - ${escapeHtml(quiz.title)} (${points} pts)
                            </option>`;
                        });
                    }
                }

                $('#editQuizId').html(options);
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load available quizzes'
                });
                $('#editQuizId').html('<option value="">Manual Entry (No Quiz Link)</option>');
            }
        });
    }

    $('#editQuizId').change(function () {
        const selected = $(this).find('option:selected');
        const quizId = selected.val();

        if (quizId) {
            const points = selected.data('points');
            if (points && points > 0) {
                $('#editMaxPoints').val(points);
                Swal.fire({
                    icon: 'info',
                    title: 'Max Points Updated',
                    text: `Max points updated to ${points} from quiz`,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        }
    });

    $('#editColumnForm').submit(function (e) {
        e.preventDefault();

        const columnId = $('#editColumnId').val();
        const maxPoints = parseInt($('#editMaxPoints').val());
        const quizId = $('#editQuizId').val();

        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

        $.ajax({
            url: API_ROUTES.updateColumn.replace('__COLUMN_ID__', columnId),
            type: 'PUT',
            data: {
                max_points: maxPoints,
                quiz_id: quizId || null
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Column updated successfully',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    $('#editColumnModal').modal('hide');
                    
                    // Destroy grids before reload
                    destroyAllGrids();
                    loadGradebook(currentQuarterId);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to update column'
                    });
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i> Update');
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update column'
                });
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Update');
            }
        });
    });

    $('#saveChangesBtn').click(function () {
        if (Object.keys(pendingChanges).length === 0) return;

        const scores = Object.values(pendingChanges);
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: API_ROUTES.batchUpdate,
            type: 'POST',
            data: { scores: scores },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'All changes saved successfully',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    
                    // Destroy grids before reload
                    destroyAllGrids();
                    loadGradebook(currentQuarterId);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to save changes'
                    });
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i> <span id="saveChangesText">Save Changes</span>');
                }
            },
            error: function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to save changes'
                });
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> <span id="saveChangesText">Save Changes</span>');
            }
        });
    });
// Import button click
$('#importBtn').click(function() {
    if (!currentQuarterId) {
        Swal.fire({
            icon: 'warning',
            title: 'Quarter Required',
            text: 'Please select a quarter first',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        return;
    }

    if (!currentSectionId) {
        Swal.fire({
            icon: 'warning',
            title: 'Section Required',
            text: 'Please select a section first',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        return;
    }

    $('#importForm')[0].reset();
    $('#importColumnGroup').hide();
    $('.custom-file-label').text('Choose file...');
    $('#importModal').modal('show');
});

// Component type change
$('#importComponentType').change(function() {
    const componentType = $(this).val();
    const columnSelect = $('#importColumnNumber');
    
    if (!componentType) {
        $('#importColumnGroup').hide();
        return;
    }

    columnSelect.empty().append('<option value="">Select Column</option>');
    
    if (componentType === 'WW') {
        for (let i = 1; i <= MAX_WW_COLUMNS; i++) {
            columnSelect.append(`<option value="${i}">WW${i}</option>`);
        }
        $('#importColumnGroup').show();
    } else if (componentType === 'PT') {
        for (let i = 1; i <= MAX_PT_COLUMNS; i++) {
            columnSelect.append(`<option value="${i}">PT${i}</option>`);
        }
        $('#importColumnGroup').show();
    } else if (componentType === 'QA') {
        columnSelect.append('<option value="1">QA</option>');
        $('#importColumnGroup').show();
        columnSelect.val('1');
    }
});

// File input change
$('#importFile').on('change', function() {
    const fileName = $(this).val().split('\\').pop();
    const label = $(this).siblings('.custom-file-label');
    if (fileName) {
        label.text(fileName);
    } else {
        label.text('Choose file (optional)...');
    }
});
// Import form submit
$('#importForm').submit(function(e) {
    e.preventDefault();

    const formData = new FormData();
    formData.append('file', $('#importFile')[0].files[0]);
    formData.append('quarter_id', currentQuarterId);
    formData.append('component_type', $('#importComponentType').val());
    formData.append('column_number', $('#importColumnNumber').val());
    formData.append('section_id', currentSectionId);

    const btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing...');

    $.ajax({
        url: API_ROUTES.import,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                let message = response.message;
                if (response.data.errors && response.data.errors.length > 0) {
                    message += '\n\nWarnings:\n' + response.data.errors.slice(0, 5).join('\n');
                    if (response.data.errors.length > 5) {
                        message += `\n... and ${response.data.errors.length - 5} more`;
                    }
                }

                Swal.fire({
                    icon: response.data.errors.length > 0 ? 'warning' : 'success',
                    title: 'Import Completed',
                    text: message,
                    confirmButtonText: 'OK'
                });

                $('#importModal').modal('hide');
                
                // Destroy grids and reload
                destroyAllGrids();
                loadGradebook(currentQuarterId);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Import Failed',
                    text: response.message
                });
                btn.prop('disabled', false).html('<i class="fas fa-upload"></i> Import');
            }
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Failed to import scores';
            Swal.fire({
                icon: 'error',
                title: 'Import Failed',
                text: errorMsg
            });
            btn.prop('disabled', false).html('<i class="fas fa-upload"></i> Import');
        }
    });
});
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
});