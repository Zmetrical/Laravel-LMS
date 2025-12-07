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
                        return `<span class="badge badge-info">${val}</span>`;
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
                    return `<strong>${total.toFixed(2)}</strong>`;
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
                    return `<strong>${perc}%</strong>`;
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
        const badge = col.source_type === 'online' ? '<span class="badge badge-info online-badge">Online</span>' : '';
        const statusIcon = col.is_active ?
            '<i class="fas fa-toggle-on text-success toggle-column-btn" title="Disable column"></i>' :
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
        $('#enableColumnId').val(column.id);
        $('#enableColumnName').text(column.column_name);
        $('#enableMaxPoints').val(column.max_points);

        loadQuizzesForEnable(column.quiz_id);
        $('#enableColumnModal').modal('show');
    }

    function loadQuizzesForEnable(currentQuizId) {
        if (!currentQuarterId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please select a quarter first'
            });
            $('#enableQuizId').html('<option value="">Manual Entry</option>');
            return;
        }

        $('#enableQuizId').html('<option value="">Loading quizzes...</option>');

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
                        Swal.fire({
                            icon: 'info',
                            title: 'Quizzes Found',
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
                $('#enableQuizId').html('<option value="">Manual Entry (No Quiz Link)</option>');
            }
        });
    }

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

    $('#enableColumnForm').submit(function (e) {
        e.preventDefault();

        const columnId = $('#enableColumnId').val();
        const maxPoints = parseInt($('#enableMaxPoints').val());
        const quizId = $('#enableQuizId').val();

        toggleColumnStatus(columnId, true, maxPoints, quizId);
    });

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

        $('#editColumnId').val(column.id);
        $('#editColumnName').val(column.column_name);
        $('#editMaxPoints').val(column.max_points);

        loadQuizzesForEdit(column.quiz_id);
        $('#editColumnModal').modal('show');
    }

    function findColumnById(columnId) {
        for (let type of ['WW', 'PT', 'QA']) {
            const cols = gradebookData.columns[type] || [];
            const found = cols.find(c => c.id == columnId);
            if (found) return found;
        }
        return null;
    }

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