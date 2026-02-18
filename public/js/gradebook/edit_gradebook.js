console.log("gradebook edit");

$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let gradebookData  = null;
    let classInfo      = null;
    let quarterInfo    = null;
    let currentQuarterId  = null;
    let currentSectionId  = null;
    let currentViewType   = 'quarter';
    let pendingChanges    = {};
    let submissionStatus  = null;
    let pendingAction     = null;
    let wwGrid, ptGrid, qaGrid;

    // =========================================================================
    // INIT
    // =========================================================================

    if (QUARTERS.length > 0) {
        currentQuarterId = QUARTERS[0].id;
        $('.quarter-btn[data-type="quarter"]').first()
            .addClass('btn-secondary active').removeClass('btn-outline-secondary');
    }

    showEmptyState();

    // =========================================================================
    // QUARTER / SECTION FILTERS
    // =========================================================================

    $('.quarter-btn').click(function () {
        const type = $(this).data('type');

        if (!currentSectionId) {
            toastWarn('Please select a section first');
            return;
        }

        $('.quarter-btn').removeClass('btn-secondary active').addClass('btn-outline-secondary');
        $(this).addClass('btn-secondary active').removeClass('btn-outline-secondary');

        if (type === 'final') {
            currentViewType = 'final';
            loadFinalGrade();
            $('#custom-tabs li:not(:last)').hide();
            $('#summary-tab').click();
        } else {
            const quarterId = $(this).data('quarter');
            if (quarterId === currentQuarterId && currentViewType === 'quarter') return;
            currentViewType  = 'quarter';
            currentQuarterId = quarterId;
            $('#custom-tabs li').show();
            loadGradebook(currentQuarterId);
        }
    });

    $('#viewBtn').on('click', function () {
        window.location.href = API_ROUTES.viewGradebook;
    });

    $('#sectionFilter').change(function () {
        currentSectionId = $(this).val();

        if (!currentSectionId) {
            toastWarn('Please select a section');
            showEmptyState();
            return;
        }

        if (currentViewType === 'quarter') {
            if (currentQuarterId) loadGradebook(currentQuarterId);
        } else {
            loadFinalGrade();
        }
    });

    // =========================================================================
    // HELPERS — toast / state
    // =========================================================================

    function toastWarn(msg) {
        Swal.fire({ icon: 'warning', title: msg, toast: true, position: 'top-end',
            showConfirmButton: false, timer: 3000 });
    }
    function toastSuccess(msg) {
        Swal.fire({ icon: 'success', title: msg, toast: true, position: 'top-end',
            showConfirmButton: false, timer: 3000 });
    }
    function toastError(msg) {
        Swal.fire({ icon: 'error', title: msg, toast: true, position: 'top-end',
            showConfirmButton: false, timer: 3000 });
    }

    function showEmptyState() {
        const emptyHtml = `
            <div style="padding:60px 20px;text-align:center;">
                <i class="fas fa-table" style="font-size:48px;color:#6c757d;margin-bottom:15px;"></i>
                <h5 style="color:#6c757d;margin-bottom:10px;">No Section Selected</h5>
                <p style="color:#adb5bd;">Please select a section from the dropdown above to edit grades.</p>
            </div>`;
        $('#wwGrid, #ptGrid, #qaGrid').html(emptyHtml);
        const emptyRow = `<tr class="empty-state-row"><td colspan="100">
            <div style="padding:60px 20px;text-align:center;">
                <i class="fas fa-table" style="font-size:48px;color:#6c757d;margin-bottom:15px;"></i>
                <h5 style="color:#6c757d;">No Section Selected</h5>
                <p style="color:#adb5bd;">Please select a section from the dropdown above.</p>
            </div></td></tr>`;
        $('#summaryTableBody, #finalGradeTableBody').html(emptyRow);
    }

    function showTabLoading() {
        const html = '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        $('#wwGrid, #ptGrid, #qaGrid').html(html);
        $('#summaryTableBody').html('<tr class="loading-row"><td colspan="7"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
    }

    function clearAllGrids() {
        $('#wwGrid, #ptGrid, #qaGrid').html('<div style="text-align:center;padding:40px;">No data available</div>');
        $('#summaryTableBody').html('<tr class="loading-row"><td colspan="7">No data available</td></tr>');
    }

    function destroyAllGrids() {
        ['#wwGrid', '#ptGrid', '#qaGrid'].forEach(function (sel) {
            try { $(sel).jsGrid('destroy'); } catch (e) { /* safe */ }
            $(sel).empty();
        });
        wwGrid = ptGrid = qaGrid = null;
    }

    function updateColumnCounts() {
        if (!gradebookData) return;
        ['WW', 'PT', 'QA'].forEach(function (type) {
            const active = (gradebookData.columns[type] || []).filter(c => c.is_active).length;
            const max    = type === 'WW' ? MAX_WW_COLUMNS : (type === 'PT' ? MAX_PT_COLUMNS : MAX_QA_COLUMNS);
            $(`#${type.toLowerCase()}ColumnCount`).text(`${active}/${max} active`);
        });
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

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    function findColumnById(columnId) {
        for (const type of ['WW', 'PT', 'QA']) {
            const found = (gradebookData?.columns[type] || []).find(c => c.id == columnId);
            if (found) return found;
        }
        return null;
    }

    // =========================================================================
    // LOAD GRADEBOOK (quarter view)
    // =========================================================================

    function loadGradebook(quarterId) {
        if (!currentSectionId) { toastWarn('Please select a section first'); return; }

        showTabLoading();
        destroyAllGrids();
        $('#finalGradeTable').hide();
        $('.summary-table-wrapper').first().show();

        $.ajax({
            url: API_ROUTES.getGradebook,
            type: 'GET',
            data: { quarter_id: quarterId, section_id: currentSectionId },
            success: function (response) {
                if (!response.success) {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message || 'Failed to load gradebook' });
                    clearAllGrids();
                    return;
                }

                gradebookData    = response.data;
                classInfo        = response.data.class;
                quarterInfo      = response.data.quarter;
                currentQuarterId = quarterId;
                pendingChanges   = {};

                if (!response.data.students || response.data.students.length === 0) {
                    toastWarn('No students enrolled in this section');
                    clearAllGrids();
                } else {
                    initializeGrids(gradebookData);
                    renderSummaryTable(gradebookData);
                }

                updateSaveButton();
                updateColumnCounts();
            },
            error: function (xhr) {
                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to load gradebook data' });
                clearAllGrids();
            }
        });
    }

    // =========================================================================
    // LOAD FINAL GRADE
    // =========================================================================

    function loadFinalGrade() {
        if (!currentSectionId) { toastWarn('Please select a section'); return; }

        $('#finalGradeTableBody').html('<tr class="loading-row"><td colspan="7"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
        $('.summary-table-wrapper').first().hide();
        $('#finalGradeTable').show();
        $('#submitFinalGradeBtn').show();

        $.ajax({
            url: API_ROUTES.checkFinalGradesStatus,
            type: 'GET',
            data: { semester_id: ACTIVE_SEMESTER_ID, section_id: currentSectionId },
            success: function (statusResp) {
                if (statusResp.success) {
                    submissionStatus = statusResp.data;
                    updateSubmitButton(submissionStatus);
                }

                $.ajax({
                    url: API_ROUTES.getFinalGrade,
                    type: 'GET',
                    data: { section_id: currentSectionId },
                    success: function (response) {
                        if (response.success) {
                            renderFinalGradeTable(response.data, submissionStatus);
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: response.message || 'Failed to load final grades' });
                            $('#finalGradeTableBody').html('<tr class="loading-row"><td colspan="7">No data available</td></tr>');
                        }
                    },
                    error: function (xhr) {
                        Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to load final grades' });
                        $('#finalGradeTableBody').html('<tr class="loading-row"><td colspan="7">Error loading data</td></tr>');
                    }
                });
            },
            error: function () {
                toastError('Failed to check submission status');
            }
        });
    }

    function updateSubmitButton(status) {
        const btn = $('#submitFinalGradeBtn');
        btn.removeClass('btn-primary btn-success');

        if (status.status === 'complete') {
            btn.html('<i class="fas fa-check-circle"></i> All Grades Submitted')
               .prop('disabled', true).addClass('btn-secondary');
        } else if (status.status === 'partial') {
            btn.html(`<i class="fas fa-save"></i> Submit Remaining Grades (${status.pending_count} student${status.pending_count > 1 ? 's' : ''})`)
               .prop('disabled', false).addClass('btn-primary');
        } else {
            btn.html(`<i class="fas fa-save"></i> Submit Final Grades (${status.total_enrolled} student${status.total_enrolled > 1 ? 's' : ''})`)
               .prop('disabled', false).addClass('btn-primary');
        }
    }

    // =========================================================================
    // RENDER — FINAL GRADE TABLE
    // =========================================================================

    function renderFinalGradeTable(data, status) {
        const students = data.students;
        if (!students || students.length === 0) {
            $('#finalGradeTableBody').html('<tr class="loading-row"><td colspan="7">No students found</td></tr>');
            return;
        }

        const male   = students.filter(s => s.gender?.toLowerCase() === 'male');
        const female = students.filter(s => s.gender?.toLowerCase() === 'female');
        const other  = students.filter(s => !s.gender || !['male','female'].includes(s.gender.toLowerCase()));

        let html = '';
        if (male.length)   { html += genderSep('mars','MALE',7);   male.forEach(s => { html += renderFinalGradeRow(s, status); }); }
        if (female.length) { html += genderSep('venus','FEMALE',7); female.forEach(s => { html += renderFinalGradeRow(s, status); }); }
        if (other.length)  { html += genderSep('user','OTHER',7);   other.forEach(s => { html += renderFinalGradeRow(s, status); }); }

        $('#finalGradeTableBody').html(html);
    }

    function renderFinalGradeRow(student, status) {
        const remarksClass   = student.remarks === 'PASSED' ? 'text-success' : 'text-danger';
        const studentStatus  = status?.student_status?.[student.student_number];
        const isSubmitted    = studentStatus?.submitted || false;
        const statusBadge    = isSubmitted
            ? `<span class="text-primary" title="Submitted on ${new Date(studentStatus.submitted_at).toLocaleDateString()}">SUBMITTED</span>`
            : `<span class="text-secondary">PENDING</span>`;

        return `<tr data-student="${escapeHtml(student.student_number)}" class="${isSubmitted ? 'submitted-row' : 'pending-row'}">
            <td>${escapeHtml(student.student_number)}</td>
            <td>${escapeHtml(student.full_name)}</td>
            <td class="text-center">${student.q1_grade || '-'}</td>
            <td class="text-center">${student.q2_grade || '-'}</td>
            <td class="text-center grade-cell"><strong>${student.final_grade || '-'}</strong></td>
            <td class="text-center"><span class="${remarksClass}">${student.remarks}</span></td>
            <td class="text-center">${statusBadge}</td>
        </tr>`;
    }

    // =========================================================================
    // RENDER — SUMMARY TABLE
    // =========================================================================

    function renderSummaryTable(data) {
        const { class: ci, students } = data;
        const male   = students.filter(s => s.gender?.toLowerCase() === 'male');
        const female = students.filter(s => s.gender?.toLowerCase() === 'female');
        const other  = students.filter(s => !s.gender || !['male','female'].includes(s.gender.toLowerCase()));

        let html = '';
        if (male.length)   { html += genderSep('mars','MALE',7);   male.forEach(s => { html += renderSummaryRow(s, ci); }); }
        if (female.length) { html += genderSep('venus','FEMALE',7); female.forEach(s => { html += renderSummaryRow(s, ci); }); }
        if (other.length)  { html += genderSep('user','OTHER',7);   other.forEach(s => { html += renderSummaryRow(s, ci); }); }

        $('#summaryTableBody').html(html || '<tr class="loading-row"><td colspan="7">No data available</td></tr>');
    }

    function renderSummaryRow(student, ci) {
        let hasMissing = false;

        function calc(key) {
            let total = 0, max = 0;
            Object.values(student[key] || {}).forEach(item => {
                if (!item.is_active) return;
                if (item.score !== null && item.score !== undefined) total += parseFloat(item.score);
                else hasMissing = true;
                max += parseFloat(item.max_points);
            });
            return { total, max, perc: max > 0 ? (total / max * 100) : 0 };
        }

        const ww = calc('ww'); const wwW = ww.perc * (ci.ww_perc / 100);
        const pt = calc('pt'); const ptW = pt.perc * (ci.pt_perc / 100);
        const qa = calc('qa'); const qaW = qa.perc * (ci.qa_perce / 100);
        const initial = wwW + ptW + qaW;
        const quarterly = transmuteGrade(initial);

        return `<tr${hasMissing ? ' class="row-missing-grade"' : ''}>
            <td>${escapeHtml(student.student_number)}</td>
            <td>${escapeHtml(student.full_name)}</td>
            <td class="text-center">${wwW.toFixed(2)}</td>
            <td class="text-center">${ptW.toFixed(2)}</td>
            <td class="text-center">${qaW.toFixed(2)}</td>
            <td class="text-center">${initial.toFixed(2)}</td>
            <td class="text-center grade-cell"><strong>${quarterly}</strong></td>
        </tr>`;
    }

    function genderSep(icon, label, colspan) {
        return `<tr class="gender-separator"><td colspan="${colspan}"><i class="fas fa-${icon}"></i> ${label}</td></tr>`;
    }

    // =========================================================================
    // JSGRID — INIT
    // =========================================================================

    function initializeGrids(data) {
        initGrid('WW', '#wwGrid', data);
        initGrid('PT', '#ptGrid', data);
        initGrid('QA', '#qaGrid', data);
    }

    function initGrid(componentType, selector, data) {
        const columns  = data.columns[componentType] || [];
        const students = data.students || [];

        const male   = students.filter(s => s.gender?.toLowerCase() === 'male');
        const female = students.filter(s => s.gender?.toLowerCase() === 'female');
        const other  = students.filter(s => !s.gender || !['male','female'].includes(s.gender.toLowerCase()));

        let totalMaxPoints = columns.filter(c => c.is_active).reduce((sum, c) => sum + parseFloat(c.max_points), 0);

        let fields = [
            { name: 'student_number', title: 'USN',          type: 'text', width: 100, editing: false, css: 'student-info' },
            { name: 'full_name',      title: 'Student Name',  type: 'text', width: 200, editing: false, css: 'student-info' }
        ];

        columns.forEach(function (col) {
            const isOnline   = col.source_type === 'online';
            const isDisabled = !col.is_active;

            fields.push({
                name:       col.column_name,
                title:      createColumnHeader(col),
                type:       'number',
                width:      90,
                editing:    !isOnline && col.is_active,
                headerCss:  isOnline ? 'online-column' : (isDisabled ? 'disabled-column' : ''),

                itemTemplate: function (value, item) {
                    if (item._isGenderSeparator) return '';
                    if (isDisabled) return '<span class="text-muted">-</span>';
                    if (isOnline)   return `<span class="badge badge-primary">${value !== null && value !== undefined ? value : '-'}</span>`;

                    const key       = `${col.id}_${item.student_number}`;
                    const isChanged = pendingChanges.hasOwnProperty(key);
                    const display   = value !== null && value !== undefined ? value : '';
                    return `<span id="cell_${col.id}_${item.student_number}" class="${isChanged ? 'changed-cell-value' : ''}">${display}</span>`;
                },

                editTemplate: function (value, item) {
                    if (item._isGenderSeparator || isDisabled || isOnline) return '<span class="text-muted">-</span>';

                    const input = $('<input>')
                        .attr({ type: 'number', min: 0, max: col.max_points, step: '0.01' })
                        .attr('data-column-id', col.id)
                        .attr('data-student', item.student_number)
                        .addClass('form-control form-control-sm')
                        .val(value !== null && value !== undefined ? value : '')
                        .on('input', function () {
                            const v = parseFloat($(this).val());
                            if (v < 0) $(this).val(0);
                            if (v > col.max_points) $(this).val(col.max_points);
                        })
                        .on('blur', function () {
                            const newVal = $(this).val();
                            const oldVal = value !== null && value !== undefined ? String(value) : '';
                            if (newVal !== oldVal) {
                                const cellId = `cell_${col.id}_${item.student_number}`;
                                markChanged(col.id, item.student_number, newVal, cellId);
                                item[col.column_name] = newVal === '' ? null : parseFloat(newVal);
                                $(`#${cellId}`).text(newVal === '' ? '' : parseFloat(newVal)).addClass('changed-cell-value');
                            }
                        })
                        .on('keypress', function (e) { if (e.which === 13) $(this).blur(); });

                    return input;
                }
            });
        });

        fields.push(
            {
                name: 'total', title: `<div class="column-header"><span class="column-title">Total</span><span class="column-points">${totalMaxPoints}pts</span></div>`,
                type: 'text', width: 80, editing: false, css: 'total-cell', headerCss: 'component-header',
                itemTemplate: function (value, item) {
                    if (item._isGenderSeparator) return '';
                    let t = 0;
                    columns.filter(c => c.is_active).forEach(c => {
                        const s = item[c.column_name];
                        if (s !== null && s !== undefined && s !== '') t += parseFloat(s);
                    });
                    return t.toFixed(2);
                }
            },
            {
                name: 'percentage', title: `<div class="column-header"><span class="column-title">Score</span><span class="column-points">%</span></div>`,
                type: 'text', width: 80, editing: false, css: 'total-cell', headerCss: 'component-header',
                itemTemplate: function (value, item) {
                    if (item._isGenderSeparator) return '';
                    let t = 0;
                    columns.filter(c => c.is_active).forEach(c => {
                        const s = item[c.column_name];
                        if (s !== null && s !== undefined && s !== '') t += parseFloat(s);
                    });
                    return (totalMaxPoints > 0 ? (t / totalMaxPoints * 100).toFixed(2) : '0.00') + '%';
                }
            }
        );

        // Build rows with gender separators
        let gridData = [];

        function addGroup(icon, label, group) {
            if (!group.length) return;
            gridData.push({ _isGenderSeparator: true, student_number: `<i class="fas fa-${icon}"></i> ${label}`, full_name: '' });
            group.forEach(function (student) {
                const row = { student_number: student.student_number, full_name: student.full_name, _isGenderSeparator: false };
                columns.forEach(function (col) {
                    const sd = student[componentType.toLowerCase()][col.column_name] || {};
                    row[col.column_name] = sd.score !== null && sd.score !== undefined ? parseFloat(sd.score) : null;
                });
                gridData.push(row);
            });
        }

        addGroup('mars',  'MALE',   male);
        addGroup('venus', 'FEMALE', female);
        addGroup('user',  'OTHER',  other);

        $(selector).jsGrid({
            width:    '100%',
            height:   'auto',
            editing:  true,
            sorting:  false,
            paging:   false,
            autoload: true,
            data:     gridData,
            fields:   fields,
            rowClass: function (item) { return item._isGenderSeparator ? 'gender-separator' : ''; }
        });

        if (componentType === 'WW') wwGrid = $(selector).data('JSGrid');
        if (componentType === 'PT') ptGrid = $(selector).data('JSGrid');
        if (componentType === 'QA') qaGrid = $(selector).data('JSGrid');
    }

    // =========================================================================
    // COLUMN HEADER HTML
    // =========================================================================

    function createColumnHeader(col) {
        const badge      = col.source_type === 'online' ? '<span class="badge badge-primary online-badge">Online</span>' : '';
        const toggleIcon = col.is_active
            ? '<i class="fas fa-toggle-on toggle-column-btn" title="Disable column" style="font-size:16px;"></i>'
            : '<i class="fas fa-toggle-off text-secondary toggle-column-btn" title="Enable column" style="font-size:16px;"></i>';
        const editIcon = col.is_active && col.source_type !== 'online'
            ? `<i class="fas fa-edit edit-column-btn ml-1" data-column-id="${col.id}" title="Edit column" style="font-size:12px;"></i>`
            : '';

        return `<div class="column-header" data-column-id="${col.id}">
            <span class="column-title${!col.is_active ? ' text-muted' : ''}">${col.column_name}</span>
            ${badge}
            <span class="column-points${!col.is_active ? ' text-muted' : ''}">${col.max_points}pts</span>
            <div class="mt-1">${toggleIcon}${editIcon}</div>
        </div>`;
    }

    // =========================================================================
    // PENDING CHANGES
    // =========================================================================

    function markChanged(columnId, studentNumber, score, cellId) {
        const key = `${columnId}_${studentNumber}`;
        pendingChanges[key] = {
            column_id:      columnId,
            student_number: studentNumber,
            score:          score === '' ? null : parseFloat(score)
        };
        updateSaveButton();
    }

    // =========================================================================
    // SAVE CHANGES (batch scores)
    // =========================================================================

    $('#saveChangesBtn').click(function () {
        if (!Object.keys(pendingChanges).length) return;

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: API_ROUTES.batchUpdate,
            type: 'POST',
            data: { scores: Object.values(pendingChanges) },
            success: function (response) {
                if (response.success) {
                    toastSuccess('All changes saved successfully');
                    pendingChanges = {};
                    destroyAllGrids();
                    loadGradebook(currentQuarterId);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save changes' });
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i> <span id="saveChangesText">Save Changes</span>');
                }
            },
            error: function () {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to save changes' });
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> <span id="saveChangesText">Save Changes</span>');
            }
        });
    });

    // =========================================================================
    // TOGGLE COLUMN (enable / disable)
    // =========================================================================

    $(document).on('click', '.toggle-column-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const columnId = $(this).closest('.column-header').data('column-id');
        const column   = findColumnById(columnId);
        if (!column) { toastError('Column not found'); return false; }

        if (!column.is_active) {
            openColumnModal(column, 'enable');
        } else {
            Swal.fire({
                title: 'Disable Column?',
                text:  'Scores will be hidden from calculations.',
                icon:  'warning',
                showCancelButton:   true,
                confirmButtonColor: '#6c757d',
                confirmButtonText:  'Yes, disable it'
            }).then(function (result) {
                if (result.isConfirmed) doToggleColumn(columnId, false);
            });
        }

        return false;
    });

    $(document).on('click', '.edit-column-btn', function (e) {
        e.stopPropagation();
        const columnId = $(this).data('column-id');
        const column   = findColumnById(columnId);
        if (column) openColumnModal(column, 'edit');
    });

    // =========================================================================
    // COLUMN MODAL — unified for enable + edit
    // =========================================================================

    /**
     * mode: 'enable' | 'edit'
     */
    function openColumnModal(column, mode) {
        // Reset everything
        $('#columnForm')[0].reset();
        $('#quizId').html('<option value="">Select Quiz</option>');
        $('#onlineQuizGroup').hide();
        $('#importFileGroup').hide();
        $('.custom-file-label[for="importFile"]').text('Choose file (optional)...');

        $('#columnId').val(column.id);
        $('#columnName').text(column.column_name);
        $('#maxPoints').val(column.max_points);

        if (mode === 'enable') {
            $('#columnModalTitle').html('<i class="fas fa-toggle-on"></i> Enable Column: ' + column.column_name);
            $('#columnModalBtn').html('<i class="fas fa-check"></i> Enable Column').data('mode', 'enable');
        } else {
            $('#columnModalTitle').html('<i class="fas fa-edit"></i> Edit Column: ' + column.column_name);
            $('#columnModalBtn').html('<i class="fas fa-save"></i> Update Column').data('mode', 'edit');
            // Pre-fill grade type
            if (column.source_type === 'online') {
                $('#gradeType').val('online');
                loadQuizzes(column.quiz_id);
                $('#onlineQuizGroup').show();
            } else {
                $('#gradeType').val('face-to-face');
                $('#importFileGroup').show();
            }
        }

        $('#columnModal').modal('show');
    }

    // Grade type change
    $('#gradeType').change(function () {
        const type = $(this).val();
        $('#onlineQuizGroup').hide();
        $('#importFileGroup').hide();
        if (type === 'online')        { loadQuizzes(null); $('#onlineQuizGroup').show(); }
        else if (type === 'face-to-face') { $('#importFileGroup').show(); }
    });

    // Quiz select -> auto-fill max points
    $('#quizId').change(function () {
        const selected = $(this).find('option:selected');
        if (selected.val()) {
            const pts = selected.data('points');
            if (pts && pts > 0) {
                $('#maxPoints').val(pts);
                toastWarn(`Max points set to ${pts} from quiz`);
            }
        }
    });

    // File input label
    $('#importFile').on('change', function () {
        const name = $(this).val().split('\\').pop();
        $(this).siblings('.custom-file-label').text(name || 'Choose file (optional)...');
    });

    // Column form submit — handles BOTH enable and edit
    $('#columnForm').submit(function (e) {
        e.preventDefault();

        const columnId  = $('#columnId').val();
        const maxPoints = parseInt($('#maxPoints').val());
        const gradeType = $('#gradeType').val();
        const quizId    = $('#quizId').val();
        const importFile = $('#importFile')[0]?.files[0];
        const mode      = $('#columnModalBtn').data('mode'); // 'enable' | 'edit'

        // Validation
        if (!gradeType) { Swal.fire({ icon: 'warning', title: 'Grade Type Required', text: 'Please select a grade type' }); return; }
        if (gradeType === 'online' && !quizId) { Swal.fire({ icon: 'warning', title: 'Quiz Required', text: 'Please select an online quiz' }); return; }
        if (!currentSectionId) { Swal.fire({ icon: 'warning', title: 'Section Required', text: 'Please select a section first' }); return; }
        if (!maxPoints || maxPoints < 1) { Swal.fire({ icon: 'warning', title: 'Invalid Points', text: 'Maximum points must be at least 1' }); return; }

        const btn = $('#columnModalBtn');
        const origHtml = btn.html();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Processing...');

        const payload = {
            max_points: maxPoints,
            quiz_id:    gradeType === 'online' ? quizId : null
        };

        let url, method;

        if (mode === 'enable') {
            payload.is_active = 1;
            url    = API_ROUTES.toggleColumn.replace('__COLUMN_ID__', columnId);
            method = 'POST';
        } else {
            url    = API_ROUTES.updateColumn.replace('__COLUMN_ID__', columnId);
            method = 'PUT';
        }

        $.ajax({
            url:  url,
            type: method,
            data: payload,
            success: function (response) {
                if (!response.success) {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message || 'Failed to process request' });
                    btn.prop('disabled', false).html(origHtml);
                    return;
                }

                // If face-to-face + import file chosen, import after enabling/updating
                if (gradeType === 'face-to-face' && importFile) {
                    importColumnFile(columnId, importFile, btn, origHtml);
                } else {
                    toastSuccess(mode === 'enable' ? 'Column enabled successfully' : 'Column updated successfully');
                    $('#columnModal').modal('hide');
                    destroyAllGrids();
                    loadGradebook(currentQuarterId);
                }
            },
            error: function (xhr) {
                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to process request' });
                btn.prop('disabled', false).html(origHtml);
            }
        });
    });

    function doToggleColumn(columnId, isActive) {
        $.ajax({
            url:  API_ROUTES.toggleColumn.replace('__COLUMN_ID__', columnId),
            type: 'POST',
            data: { is_active: isActive ? 1 : 0 },
            success: function (response) {
                if (response.success) {
                    toastSuccess(response.message);
                    destroyAllGrids();
                    loadGradebook(currentQuarterId);
                } else {
                    toastError(response.message || 'Failed to update column');
                }
            },
            error: function (xhr) {
                toastError(xhr.responseJSON?.message || 'Failed to update column');
            }
        });
    }

    function importColumnFile(columnId, file, btn, origHtml) {
        const formData = new FormData();
        formData.append('file',       file);
        formData.append('section_id', currentSectionId);

        $.ajax({
            url:         API_ROUTES.importColumn.replace('__COLUMN_ID__', columnId),
            type:        'POST',
            data:        formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    let msg  = `Column processed and ${response.data.imported} scores imported`;
                    let icon = 'success';
                    if (response.data.skipped > 0) msg += `, ${response.data.skipped} skipped`;
                    if (response.data.errors?.length > 0) {
                        icon  = 'warning';
                        msg  += '\n\nWarnings:\n' + response.data.errors.slice(0, 3).join('\n');
                        if (response.data.errors.length > 3) msg += `\n... and ${response.data.errors.length - 3} more`;
                    }
                    Swal.fire({ icon, title: 'Import Completed', text: msg });
                    $('#columnModal').modal('hide');
                    destroyAllGrids();
                    loadGradebook(currentQuarterId);
                } else {
                    Swal.fire({ icon: 'error', title: 'Import Failed', text: response.message || 'Failed to import scores' });
                    btn.prop('disabled', false).html(origHtml);
                }
            },
            error: function (xhr) {
                Swal.fire({ icon: 'error', title: 'Import Failed', text: xhr.responseJSON?.message || 'Failed to import scores' });
                btn.prop('disabled', false).html(origHtml);
            }
        });
    }

    // Reset modal state on close
    $('#columnModal').on('hidden.bs.modal', function () {
        $('#columnForm')[0].reset();
        $('#onlineQuizGroup').hide();
        $('#importFileGroup').hide();
        $('#quizId').html('<option value="">Select Quiz</option>');
        $('.custom-file-label[for="importFile"]').text('Choose file (optional)...');
        $('#columnModalBtn').prop('disabled', false);
    });

    // =========================================================================
    // LOAD QUIZZES
    // =========================================================================

    function loadQuizzes(currentQuizId) {
        if (!currentQuarterId) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a quarter first' });
            $('#quizId').html('<option value="">Select Quiz</option>');
            return;
        }

        $('#quizId').html('<option value="">Loading quizzes...</option>');

        $.ajax({
            url:  API_ROUTES.getQuizzes,
            type: 'GET',
            data: { quarter_id: currentQuarterId },
            success: function (response) {
                let options = '<option value="">Select Quiz</option>';

                if (response.success && response.data?.length > 0) {
                    response.data.forEach(function (quiz) {
                        const pts      = quiz.total_points || 0;
                        const selected = quiz.id == currentQuizId ? 'selected' : '';
                        options += `<option value="${quiz.id}" ${selected} data-points="${pts}">
                            ${escapeHtml(quiz.lesson_title)} — ${escapeHtml(quiz.title)} (${pts} pts)
                        </option>`;
                    });
                } else {
                    toastWarn(response.message || 'No quizzes available for this quarter');
                }

                $('#quizId').html(options);
            },
            error: function (xhr) {
                toastError(xhr.responseJSON?.message || 'Failed to load quizzes');
                $('#quizId').html('<option value="">Select Quiz</option>');
            }
        });
    }

    // =========================================================================
    // IMPORT MODAL (batch import from Excel)
    // =========================================================================

    $('#importBtn').click(function () {
        if (!currentQuarterId) { toastWarn('Please select a quarter first'); return; }
        if (!currentSectionId) { toastWarn('Please select a section first'); return; }

        $('#importForm')[0].reset();
        $('.custom-file-label[for="importScoreFile"]').text('Choose file...');
        $('#importSubmitBtn').prop('disabled', true);
        $('#importColumnsList').html('<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Loading columns...</div>');
        $('#importModal').modal('show');

        $.ajax({
            url:  API_ROUTES.getGradebook,
            type: 'GET',
            data: { quarter_id: currentQuarterId, section_id: currentSectionId },
            success: function (response) {
                if (!response.success) { $('#importColumnsList').html('<div class="text-center text-muted py-3">Failed to load columns.</div>'); return; }

                const allColumns = response.data.columns;
                const f2fByType  = {};

                ['WW','PT','QA'].forEach(function (type) {
                    const cols = (allColumns[type] || []).filter(c => c.is_active && c.source_type !== 'online');
                    if (cols.length) f2fByType[type] = cols;
                });

                if (!Object.keys(f2fByType).length) {
                    $('#importColumnsList').html(`<div class="alert alert-secondary mb-0">
                        <i class="fas fa-info-circle"></i> No active face-to-face columns found for this quarter.
                        Enable a column using the toggle in the gradebook first.</div>`);
                    return;
                }

                const typeLabels = { WW: 'Written Work', PT: 'Performance Task', QA: 'Quarterly Assessment' };
                let html = '';

                Object.entries(f2fByType).forEach(function ([type, cols]) {
                    html += `<div class="mb-3">
                        <div class="d-flex align-items-center mb-1">
                            <strong class="mr-2">${typeLabels[type]}</strong>
                            <a href="#" class="text-secondary small select-type-link" data-type="${type}">select all</a>
                            <span class="text-muted mx-1 small">/</span>
                            <a href="#" class="text-secondary small deselect-type-link" data-type="${type}">none</a>
                        </div>
                        <div class="row no-gutters" data-type-group="${type}">`;

                    cols.forEach(function (col) {
                        html += `<div class="col-auto mr-2 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input import-col-check"
                                       id="col_${col.id}" value="${col.id}" data-type="${type}"
                                       data-name="${escapeHtml(col.column_name)}" data-points="${col.max_points}">
                                <label class="custom-control-label" for="col_${col.id}">
                                    ${escapeHtml(col.column_name)} <small class="text-muted">(${col.max_points}pts)</small>
                                </label>
                            </div>
                        </div>`;
                    });

                    html += `</div></div>`;
                });

                $('#importColumnsList').html(html);
                updateImportSubmitState();
            },
            error: function () {
                $('#importColumnsList').html('<div class="text-center text-muted py-3">Failed to load columns.</div>');
            }
        });
    });

    function updateImportSubmitState() {
        const hasFile    = $('#importScoreFile')[0].files.length > 0;
        const hasChecked = $('.import-col-check:checked').length > 0;
        $('#importSubmitBtn').prop('disabled', !(hasFile && hasChecked));
    }

    $('#importScoreFile').on('change', function () {
        $(this).siblings('.custom-file-label').text($(this).val().split('\\').pop() || 'Choose file...');
        updateImportSubmitState();
    });

    $(document).on('change', '.import-col-check', updateImportSubmitState);

    $('#selectAllColumnsBtn').click(function ()   { $('.import-col-check').prop('checked', true);  updateImportSubmitState(); });
    $('#deselectAllColumnsBtn').click(function ()  { $('.import-col-check').prop('checked', false); updateImportSubmitState(); });

    $(document).on('click', '.select-type-link', function (e) {
        e.preventDefault();
        $(`.import-col-check[data-type="${$(this).data('type')}"]`).prop('checked', true);
        updateImportSubmitState();
    });

    $(document).on('click', '.deselect-type-link', function (e) {
        e.preventDefault();
        $(`.import-col-check[data-type="${$(this).data('type')}"]`).prop('checked', false);
        updateImportSubmitState();
    });

    $('#importForm').submit(function (e) {
        e.preventDefault();

        const checkedCols = $('.import-col-check:checked');
        const file        = $('#importScoreFile')[0].files[0];

        if (!checkedCols.length) { Swal.fire({ icon: 'warning', title: 'No Columns Selected', text: 'Please select at least one column.' }); return; }
        if (!file)               { Swal.fire({ icon: 'warning', title: 'File Required',        text: 'Please choose an Excel file.'       }); return; }

        const formData = new FormData();
        formData.append('file',       file);
        formData.append('quarter_id', currentQuarterId);
        formData.append('section_id', currentSectionId);
        checkedCols.each(function () { formData.append('column_ids[]', $(this).val()); });

        const btn = $('#importSubmitBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Importing...');

        $.ajax({
            url:         API_ROUTES.import,
            type:        'POST',
            data:        formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    let html = response.message;
                    let icon = 'success';

                    if (response.data.column_summary?.length) {
                        html += '<br><br><small>' + response.data.column_summary.join('<br>') + '</small>';
                    }
                    if (response.data.errors?.length) {
                        icon  = 'warning';
                        const shown = response.data.errors.slice(0, 5);
                        html += '<br><br><small class="text-danger">' + shown.join('<br>') + '</small>';
                        if (response.data.errors.length > 5) html += `<br><small>... and ${response.data.errors.length - 5} more</small>`;
                    }

                    Swal.fire({ icon, title: 'Import Completed', html });
                    $('#importModal').modal('hide');
                    destroyAllGrids();
                    loadGradebook(currentQuarterId);
                } else {
                    Swal.fire({ icon: 'error', title: 'Import Failed', text: response.message });
                    btn.prop('disabled', false).html('<i class="fas fa-upload"></i> Import Selected');
                }
            },
            error: function (xhr) {
                Swal.fire({ icon: 'error', title: 'Import Failed', text: xhr.responseJSON?.message || 'Failed to import scores' });
                btn.prop('disabled', false).html('<i class="fas fa-upload"></i> Import Selected');
            }
        });
    });

    // =========================================================================
    // FINAL GRADE SUBMIT
    // =========================================================================

    $('#submitFinalGradeBtn').click(function () {
        if (!submissionStatus) { Swal.fire({ icon: 'warning', title: 'Loading', text: 'Please wait for submission status to load' }); return; }

        const grades = [];
        $('#finalGradeTableBody tr.pending-row').each(function () {
            const $row   = $(this);
            const sn     = $row.data('student');
            if (!sn) return;

            const q1     = parseFloat($row.find('td:eq(2)').text());
            const q2     = parseFloat($row.find('td:eq(3)').text());
            const final  = parseInt($row.find('td:eq(4) strong').text());
            const rem    = $row.find('td:eq(5) span').text().trim();

            if (!isNaN(q1) && !isNaN(q2) && !isNaN(final) && rem) {
                grades.push({ student_number: sn, q1_grade: q1, q2_grade: q2, final_grade: final, remarks: rem });
            }
        });

        if (!grades.length) {
            Swal.fire({ icon: 'info', title: 'No Pending Grades',
                text: submissionStatus.status === 'complete' ? 'All grades have already been submitted' : 'No valid grades to submit' });
            return;
        }

        window.pendingGrades = grades;
        pendingAction = 'submit';

        let msg = submissionStatus.status === 'partial'
            ? `<i class="fas fa-info-circle text-secondary"></i> Submitting final grades for <strong>${grades.length}</strong> remaining student(s).<br><br>
               <small class="text-muted">${submissionStatus.total_graded} student(s) already submitted.</small>`
            : `<i class="fas fa-info-circle text-secondary"></i> Submitting final grades for <strong>${grades.length}</strong> student(s).`;

        $('#passcodeModalMessage').html(msg);
        $('#passcodeModal').modal('show');
        $('#passcode').val('').focus();
    });

    $('#passcodeForm').submit(function (e) {
        e.preventDefault();

        const passcode = $('#passcode').val().trim();
        if (!passcode) { toastWarn('Please enter your passcode'); return; }

        const btn = $('#verifyPasscodeBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Verifying...');

        const verifyUrl = API_ROUTES.verifyPasscode;

        $.ajax({
            url:  verifyUrl,
            type: 'POST',
            data: { passcode },
            success: function (response) {
                if (response.success) {
                    $('#passcodeModal').modal('hide');
                    if (pendingAction === 'submit') submitFinalGrades(window.pendingGrades);
                    pendingAction = null;
                }
            },
            error: function (xhr) {
                Swal.fire({ icon: 'error', title: 'Invalid Passcode', text: xhr.responseJSON?.message || 'Verification failed', confirmButtonColor: '#dc3545' });
                btn.prop('disabled', false).html('<i class="fas fa-check"></i> Verify');
                $('#passcode').val('').focus();
            }
        });
    });

    $('#passcodeModal').on('hidden.bs.modal', function () {
        $('#passcode').val('');
        $('#verifyPasscodeBtn').prop('disabled', false).html('<i class="fas fa-check"></i> Verify');
        pendingAction = null;
    });

    function submitFinalGrades(grades) {
        const btn = $('#submitFinalGradeBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

        $.ajax({
            url:  API_ROUTES.submitFinalGrades,
            type: 'POST',
            data: { grades, semester_id: ACTIVE_SEMESTER_ID, section_id: currentSectionId },
            success: function (response) {
                if (response.success) {
                    Swal.fire({ icon: 'success', title: 'Success!', text: response.message || 'Final grades submitted successfully' })
                        .then(function () { loadFinalGrade(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: response.message || 'Failed to submit grades' });
                    btn.prop('disabled', false);
                    updateSubmitButton(submissionStatus);
                }
            },
            error: function (xhr) {
                Swal.fire({ icon: 'error', title: 'Error', text: xhr.responseJSON?.message || 'Failed to submit final grades' });
                btn.prop('disabled', false);
                updateSubmitButton(submissionStatus);
            }
        });
    }

    // =========================================================================
    // TRANSMUTATION
    // =========================================================================

    function transmuteGrade(initialGrade) {
        const table = [
            {min:100.00,max:100.00,grade:100},{min:98.40,max:99.99,grade:99},{min:96.80,max:98.39,grade:98},
            {min:95.20,max:96.79,grade:97},{min:93.60,max:95.19,grade:96},{min:92.00,max:93.59,grade:95},
            {min:90.40,max:91.99,grade:94},{min:88.80,max:90.39,grade:93},{min:87.20,max:88.79,grade:92},
            {min:85.60,max:87.19,grade:91},{min:84.00,max:85.59,grade:90},{min:82.40,max:83.99,grade:89},
            {min:80.80,max:82.39,grade:88},{min:79.20,max:80.79,grade:87},{min:77.60,max:79.19,grade:86},
            {min:76.00,max:77.59,grade:85},{min:74.40,max:75.99,grade:84},{min:72.80,max:74.39,grade:83},
            {min:71.20,max:72.79,grade:82},{min:69.60,max:71.19,grade:81},{min:68.00,max:69.59,grade:80},
            {min:66.40,max:67.99,grade:79},{min:64.80,max:66.39,grade:78},{min:63.20,max:64.79,grade:77},
            {min:61.60,max:63.19,grade:76},{min:60.00,max:61.59,grade:75},{min:56.00,max:59.99,grade:74},
            {min:52.00,max:55.99,grade:73},{min:48.00,max:51.99,grade:72},{min:44.00,max:47.99,grade:71},
            {min:40.00,max:43.99,grade:70},{min:36.00,max:39.99,grade:69},{min:32.00,max:35.99,grade:68},
            {min:28.00,max:31.99,grade:67},{min:24.00,max:27.99,grade:66},{min:20.00,max:23.99,grade:65},
            {min:16.00,max:19.99,grade:64},{min:12.00,max:15.99,grade:63},{min:8.00,max:11.99,grade:62},
            {min:4.00,max:7.99,grade:61},{min:0.00,max:3.99,grade:60}
        ];
        for (const r of table) {
            if (initialGrade >= r.min && initialGrade <= r.max) return r.grade;
        }
        return initialGrade;
    }
});