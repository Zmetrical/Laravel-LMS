$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 3000
    };

    let gradebookData = null;
    let classInfo = null;
    let pendingChanges = {};
    let wwGrid, ptGrid, qaGrid;
    let selectedFile = null;
    let availableSheets = [];
    let importData = null;

    loadGradebook();

    function loadGradebook() {
        $.ajax({
            url: API_ROUTES.getGradebook,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    gradebookData = response.data;
                    classInfo = response.data.class;
                    pendingChanges = {};
                    
                    initializeGrids();
                    calculateSummary();
                    updateSaveButton();
                    updateColumnCounts();
                } else {
                    toastr.error('Failed to load gradebook');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to load gradebook data');
                console.error(xhr);
            }
        });
    }

    function updateColumnCounts() {
        ['WW', 'PT', 'QA'].forEach(type => {
            const count = (gradebookData.columns[type] || []).length;
            $(`#${type.toLowerCase()}ColumnCount`).text(`${count}/${MAX_COLUMNS} columns`);
        });
    }

    function initializeGrids() {
        initGrid('WW', '#wwGrid');
        initGrid('PT', '#ptGrid');
        initGrid('QA', '#qaGrid');
    }

    function initGrid(componentType, selector) {
        const columns = gradebookData.columns[componentType] || [];
        
        let fields = [
            { 
                name: "student_number", 
                title: "USN", 
                type: "text", 
                width: 100,
                editing: false
            },
            { 
                name: "full_name", 
                title: "Student Name", 
                type: "text", 
                width: 200,
                editing: false
            }
        ];

        let totalMaxPoints = 0;

        columns.forEach(col => {
            totalMaxPoints += parseFloat(col.max_points);
            const isOnline = col.source_type === 'online';
            
            fields.push({
                name: col.column_name,
                title: createColumnHeader(col),
                type: "number",
                width: 90,
                editing: !isOnline,
                itemTemplate: function(value, item) {
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
                editTemplate: function(value, item) {
                    if (isOnline) {
                        return `<span class="badge badge-info">${value !== null && value !== undefined ? value : ''}</span>`;
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
                        .on('input', function() {
                            const val = parseFloat($(this).val());
                            if (val < 0) $(this).val(0);
                            if (val > col.max_points) $(this).val(col.max_points);
                        })
                        .on('blur', function() {
                            const newVal = $(this).val();
                            const oldVal = value !== null && value !== undefined ? value : '';
                            
                            if (newVal !== oldVal.toString()) {
                                markChanged(col.id, item.student_number, newVal, cellId);
                                item[col.column_name] = newVal === '' ? null : parseFloat(newVal);
                                $(selector).jsGrid("refresh");
                            }
                        })
                        .on('keypress', function(e) {
                            if (e.which === 13) {
                                $(this).blur();
                            }
                        });
                    
                    return input;
                },
                headerCss: isOnline ? 'online-column' : ''
            });
        });

        fields.push(
            {
                name: "total",
                title: `<div class="column-header"><span class="column-title">Total</span><span class="column-points">${totalMaxPoints}pts</span></div>`,
                type: "text",
                width: 80,
                editing: false,
                itemTemplate: function(value, item) {
                    let total = 0;
                    columns.forEach(col => {
                        const score = item[col.column_name];
                        if (score !== null && score !== undefined && score !== '') {
                            total += parseFloat(score);
                        }
                    });
                    return `<strong>${total.toFixed(2)}</strong>`;
                }
            },
            {
                name: "percentage",
                title: `<div class="column-header"><span class="column-title">Score</span><span class="column-points">%</span></div>`,
                type: "text",
                width: 80,
                editing: false,
                itemTemplate: function(value, item) {
                    let total = 0;
                    columns.forEach(col => {
                        const score = item[col.column_name];
                        if (score !== null && score !== undefined && score !== '') {
                            total += parseFloat(score);
                        }
                    });
                    const perc = totalMaxPoints > 0 ? (total / totalMaxPoints * 100).toFixed(2) : '0.00';
                    return `<strong>${perc}%</strong>`;
                }
            }
        );

        const gridData = gradebookData.students.map(student => {
            const row = {
                student_number: student.student_number,
                full_name: student.full_name
            };

            columns.forEach(col => {
                const scoreData = student[componentType.toLowerCase()][col.column_name] || {};
                row[col.column_name] = scoreData.score !== null ? parseFloat(scoreData.score) : null;
            });

            return row;
        });

        $(selector).jsGrid({
            width: "100%",
            height: "auto",
            editing: true,
            sorting: false,
            paging: false,
            autoload: true,
            data: gridData,
            fields: fields
        });

        if (componentType === 'WW') wwGrid = $(selector).data('JSGrid');
        if (componentType === 'PT') ptGrid = $(selector).data('JSGrid');
        if (componentType === 'QA') qaGrid = $(selector).data('JSGrid');
    }

    function createColumnHeader(col) {
        const icon = col.source_type === 'online' ? '<i class="fas fa-wifi text-info"></i> ' : '';
        const editIcon = col.source_type === 'manual' ? `<i class="fas fa-edit edit-column-btn" data-column-id="${col.id}" title="Edit column"></i>` : '';
        
        return `<div class="column-header">
            ${icon}<span class="column-title">${col.column_name}</span>
            <span class="column-points">${col.max_points}pts</span>
            ${editIcon}
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

    $(document).on('click', '.edit-column-btn', function(e) {
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
        $.ajax({
            url: API_ROUTES.getQuizzes,
            type: 'GET',
            success: function(response) {
                let options = '<option value="">Manual Entry</option>';
                if (response.success && response.data) {
                    response.data.forEach(quiz => {
                        const selected = quiz.id == currentQuizId ? 'selected' : '';
                        options += `<option value="${quiz.id}" ${selected} data-points="${quiz.total_points}">
                            ${escapeHtml(quiz.lesson_title)} - ${escapeHtml(quiz.title)} (${quiz.total_points}pts)
                        </option>`;
                    });
                }
                $('#editQuizId').html(options);
            }
        });
    }

    $('#editQuizId').change(function() {
        const selected = $(this).find('option:selected');
        if (selected.val()) {
            const points = selected.data('points');
            if (points) {
                $('#editMaxPoints').val(points);
            }
        }
    });

    $('#editColumnForm').submit(function(e) {
        e.preventDefault();
        
        const columnId = $('#editColumnId').val();
        const maxPoints = parseInt($('#editMaxPoints').val());
        const quizId = $('#editQuizId').val();

        $.ajax({
            url: API_ROUTES.updateColumn.replace('__COLUMN_ID__', columnId),
            type: 'PUT',
            data: {
                max_points: maxPoints,
                quiz_id: quizId || null
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Column updated successfully');
                    $('#editColumnModal').modal('hide');
                    loadGradebook();
                } else {
                    toastr.error(response.message || 'Failed to update column');
                }
            },
            error: function() {
                toastr.error('Failed to update column');
            }
        });
    });

    $('#saveChangesBtn').click(function() {
        if (Object.keys(pendingChanges).length === 0) return;

        const scores = Object.values(pendingChanges);
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: API_ROUTES.batchUpdate,
            type: 'POST',
            data: { scores: scores },
            success: function(response) {
                if (response.success) {
                    toastr.success('All changes saved successfully');
                    loadGradebook();
                } else {
                    toastr.error('Failed to save changes');
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i> <span id="saveChangesText">Save Changes</span>');
                }
            },
            error: function() {
                toastr.error('Failed to save changes');
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> <span id="saveChangesText">Save Changes</span>');
            }
        });
    });

    $('.add-column-btn').click(function() {
        const type = $(this).data('type');
        const currentCount = (gradebookData.columns[type] || []).length;
        
        if (currentCount >= MAX_COLUMNS) {
            toastr.warning(`Maximum of ${MAX_COLUMNS} columns reached for ${type}`);
            return;
        }
        
        $.ajax({
            url: API_ROUTES.addColumn,
            type: 'POST',
            data: { component_type: type },
            success: function(response) {
                if (response.success) {
                    toastr.success('Column added successfully');
                    loadGradebook();
                } else {
                    toastr.error(response.message || 'Failed to add column');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Failed to add column';
                toastr.error(msg);
            }
        });
    });

    function calculateSummary() {
        let summaryHtml = '';
        let grades = [];

        gradebookData.students.forEach(student => {
            let wwTotal = 0, wwMax = 0;
            Object.values(student.ww || {}).forEach(item => {
                if (item.score !== null && item.score !== undefined) {
                    wwTotal += parseFloat(item.score);
                }
                wwMax += parseFloat(item.max_points);
            });
            const wwPerc = wwMax > 0 ? (wwTotal / wwMax * 100) : 0;
            const wwWeighted = wwPerc * (classInfo.ww_perc / 100);

            let ptTotal = 0, ptMax = 0;
            Object.values(student.pt || {}).forEach(item => {
                if (item.score !== null && item.score !== undefined) {
                    ptTotal += parseFloat(item.score);
                }
                ptMax += parseFloat(item.max_points);
            });
            const ptPerc = ptMax > 0 ? (ptTotal / ptMax * 100) : 0;
            const ptWeighted = ptPerc * (classInfo.pt_perc / 100);

            let qaTotal = 0, qaMax = 0;
            Object.values(student.qa || {}).forEach(item => {
                if (item.score !== null && item.score !== undefined) {
                    qaTotal += parseFloat(item.score);
                }
                qaMax += parseFloat(item.max_points);
            });
            const qaPerc = qaMax > 0 ? (qaTotal / qaMax * 100) : 0;
            const qaWeighted = qaPerc * (classInfo.qa_perce / 100);

            const initialGrade = wwWeighted + ptWeighted + qaWeighted;
            const quarterlyGrade = Math.round(initialGrade);

            grades.push(quarterlyGrade);

            summaryHtml += `
                <tr>
                    <td>${escapeHtml(student.student_number)}</td>
                    <td>${escapeHtml(student.full_name)}</td>
                    <td class="text-center">${wwWeighted.toFixed(2)}</td>
                    <td class="text-center">${ptWeighted.toFixed(2)}</td>
                    <td class="text-center">${qaWeighted.toFixed(2)}</td>
                    <td class="text-center">${initialGrade.toFixed(2)}</td>
                    <td class="text-center bg-info"><strong>${quarterlyGrade}</strong></td>
                </tr>
            `;
        });

        $('#summaryTableBody').html(summaryHtml || '<tr><td colspan="7" class="text-center">No data available</td></tr>');

        if (grades.length > 0) {
            const avg = grades.reduce((a, b) => a + b, 0) / grades.length;
            const highest = Math.max(...grades);
            const lowest = Math.min(...grades);
            const passing = grades.filter(g => g >= 75).length;
            const passingRate = (passing / grades.length * 100).toFixed(0);

            $('#avgGrade').text(avg.toFixed(2));
            $('#highestGrade').text(highest.toFixed(2));
            $('#lowestGrade').text(lowest.toFixed(2));
            $('#passingRate').text(passingRate + '%');
        }
    }

    // ============================================================================
    // IMPORT FUNCTIONALITY - COMPLETE REWRITE
    // ============================================================================

    $('#importExcelBtn').click(function() {
        $('#excelFileInput').click();
    });

    $('#excelFileInput').change(function() {
        const file = this.files[0];
        if (!file) return;

        selectedFile = file;

        const formData = new FormData();
        formData.append('file', file);

        const btn = $('#importExcelBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Reading file...');

        $.ajax({
            url: API_ROUTES.getSheets,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success && response.sheets) {
                    availableSheets = response.sheets;
                    showSheetSelectionModal();
                } else {
                    toastr.error('Failed to read Excel file');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Failed to read Excel file';
                toastr.error(msg);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-upload"></i> Import from Excel');
            }
        });

        $(this).val('');
    });

    function showSheetSelectionModal() {
        if (availableSheets.length === 0) {
            toastr.error('No sheets found in the Excel file');
            return;
        }

        let options = '';
        availableSheets.forEach(sheet => {
            options += `<option value="${sheet.index}">
                ${escapeHtml(sheet.name)} (${sheet.row_count} rows)
            </option>`;
        });
        $('#sheetSelect').html(options);

        $('#sheetSelectionModal').modal('show');
    }

    $('#confirmSheetBtn').click(function() {
        const selectedSheetIndex = parseInt($('#sheetSelect').val());
        $('#sheetSelectionModal').modal('hide');
        parseImportFile(selectedSheetIndex);
    });

    function parseImportFile(sheetIndex) {
        if (!selectedFile) return;

        const formData = new FormData();
        formData.append('file', selectedFile);
        formData.append('sheet_index', sheetIndex);

        const btn = $('#importExcelBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Parsing...');

        $.ajax({
            url: API_ROUTES.importGradebook,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    importData = response;
                    showImportPreview(response);
                } else {
                    toastr.error(response.message || 'Failed to parse file');
                }
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Failed to parse Excel file';
                toastr.error(msg);
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-upload"></i> Import from Excel');
            }
        });
    }

    function showImportPreview(data) {
        let columnsHtml = '<div class="list-group mb-3">';
        
        data.columns.forEach(col => {
            const statusBadge = col.exists_in_db 
                ? `<span class="badge badge-info">Exists (${col.db_max_points}pts)</span>`
                : `<span class="badge badge-success">New Column</span>`;
            
            const checked = col.needs_creation || col.max_points === col.db_max_points ? 'checked' : '';
            
            let warning = '';
            if (col.exists_in_db && col.max_points !== col.db_max_points) {
                warning = `<br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> Max points mismatch: Excel has ${col.max_points}pts, Database has ${col.db_max_points}pts</small>`;
            }
            
            columnsHtml += `
                <div class="list-group-item">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input import-column-check" 
                               id="col_${col.excel_col_index}" 
                               data-col-index="${col.excel_col_index}"
                               ${checked}>
                        <label class="custom-control-label w-100" for="col_${col.excel_col_index}">
                            <strong>${col.column_name}</strong> (${col.max_points}pts) ${statusBadge}
                            ${warning}
                        </label>
                    </div>
                </div>
            `;
        });
        
        columnsHtml += '</div>';
        
        const errorsHtml = data.errors.length > 0 
            ? `<div class="alert alert-warning">
                   <strong><i class="fas fa-exclamation-triangle"></i> Warnings (${data.errors.length}):</strong>
                   <ul class="mb-0 mt-2">${data.errors.slice(0, 10).map(e => `<li>${escapeHtml(e)}</li>`).join('')}</ul>
                   ${data.errors.length > 10 ? `<small class="text-muted">... and ${data.errors.length - 10} more warnings</small>` : ''}
               </div>`
            : '';
        
        const previewHtml = `
            <div class="modal fade" id="importPreviewModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-info">
                            <h5 class="modal-title">
                                <i class="fas fa-file-import"></i> Import Preview - ${data.sheet_name}
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                Found <strong>${data.students.length} students</strong> and <strong>${data.columns.length} columns</strong>.
                                Select which columns to import into the gradebook.
                            </div>
                            
                            ${errorsHtml}
                            
                            <h6><strong>Select Columns to Import:</strong></h6>
                            <p class="text-muted small">Check the columns you want to add to the gradebook. Scores will be loaded into the grid.</p>
                            ${columnsHtml}
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle"></i> <strong>Important:</strong> 
                                Scores will be loaded into the gradebook grids. Click <strong>"Save Changes"</strong> button to persist them to the database.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="button" class="btn btn-info" id="applyImportBtn">
                                <i class="fas fa-check"></i> Apply to Gradebook
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#importPreviewModal').remove();
        $('body').append(previewHtml);
        
        $('#importPreviewModal').modal('show');
    }

    $(document).on('click', '#applyImportBtn', function() {
        const selectedColumns = [];
        $('.import-column-check:checked').each(function() {
            const colIndex = parseInt($(this).data('col-index'));
            const column = importData.columns.find(c => c.excel_col_index === colIndex);
            if (column) selectedColumns.push(column);
        });
        
        if (selectedColumns.length === 0) {
            toastr.warning('Please select at least one column to import');
            return;
        }
        
        $('#importPreviewModal').modal('hide');
        applyImportToGrid(selectedColumns, importData.students);
    });

    function applyImportToGrid(columns, students) {
        let appliedCount = 0;
        let createdColumns = [];
        
        columns.forEach(col => {
            // If column needs creation, we need to create it first
            if (col.needs_creation) {
                createdColumns.push(col);
            }
            
            students.forEach(studentData => {
                if (studentData.scores[col.column_name] !== undefined) {
                    const score = studentData.scores[col.column_name];
                    
                    // Find the appropriate grid
                    let grid;
                    if (col.component_type === 'WW') grid = wwGrid;
                    else if (col.component_type === 'PT') grid = ptGrid;
                    else if (col.component_type === 'QA') grid = qaGrid;
                    
                    if (grid) {
                        const rowIndex = grid.data.findIndex(r => r.student_number === studentData.student_number);
                        if (rowIndex !== -1) {
                            grid.data[rowIndex][col.column_name] = score;
                            
                            // Mark as changed for save (only if column exists in DB)
                            if (col.db_column_id) {
                                markChanged(col.db_column_id, studentData.student_number, score, `cell_${col.db_column_id}_${studentData.student_number}`);
                            }
                            
                            appliedCount++;
                        }
                    }
                }
            });
        });
        
        if (createdColumns.length > 0) {
            // Need to create columns first, then reload
            createImportedColumns(createdColumns, columns, students);
        } else {
            // Just refresh grids
            initializeGrids();
            calculateSummary();
            
            toastr.success(`Applied ${appliedCount} scores from Excel. Click "Save Changes" to persist to database.`);
            cleanupImport();
        }
    }

    function createImportedColumns(columnsToCreate, allColumns, students) {
        const btn = $('#importExcelBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating columns...');
        
        const requests = columnsToCreate.map(col => {
            return $.ajax({
                url: API_ROUTES.addColumn,
                type: 'POST',
                data: {
                    component_type: col.component_type,
                    column_name: col.column_name,
                    max_points: col.max_points,
                    order_number: col.order_number
                }
            });
        });
        
        $.when.apply($, requests).done(function() {
            toastr.success(`Created ${columnsToCreate.length} new column(s)`);
            
            // Reload gradebook to get new column IDs
            $.ajax({
                url: API_ROUTES.getGradebook,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        gradebookData = response.data;
                        classInfo = response.data.class;
                        
                        // Now map imported scores to the newly created columns
                        allColumns.forEach(col => {
                            const dbColumn = findColumnByName(col.column_name, col.component_type);
                            if (dbColumn) {
                                col.db_column_id = dbColumn.id;
                                
                                students.forEach(studentData => {
                                    if (studentData.scores[col.column_name] !== undefined) {
                                        const score = studentData.scores[col.column_name];
                                        markChanged(dbColumn.id, studentData.student_number, score, `cell_${dbColumn.id}_${studentData.student_number}`);
                                    }
                                });
                            }
                        });
                        
                        initializeGrids();
                        calculateSummary();
                        updateColumnCounts();
                        
                        toastr.info('Scores loaded into gradebook. Click "Save Changes" to save to database.');
                        cleanupImport();
                    }
                },
                complete: function() {
                    btn.prop('disabled', false).html('<i class="fas fa-upload"></i> Import from Excel');
                }
            });
        }).fail(function() {
            toastr.error('Failed to create some columns');
            btn.prop('disabled', false).html('<i class="fas fa-upload"></i> Import from Excel');
        });
    }

    function findColumnByName(columnName, componentType) {
        const columns = gradebookData.columns[componentType] || [];
        return columns.find(c => c.column_name === columnName);
    }

    function cleanupImport() {
        selectedFile = null;
        availableSheets = [];
        importData = null;
    }

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