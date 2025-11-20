$(document).ready(function() {
    // CSRF setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Toastr config
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 3000
    };

    let gradebookData = null;
    let classInfo = null;

    // Load gradebook on page load
    loadGradebook();

    /**
     * Load gradebook data
     */
    function loadGradebook() {
        $.ajax({
            url: API_ROUTES.getGradebook,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    gradebookData = response.data;
                    classInfo = response.data.class;
                    
                    $('#className').text(classInfo.class_name);
                    $('#wwPercLabel').text(`(${classInfo.ww_perc}%)`);
                    $('#ptPercLabel').text(`(${classInfo.pt_perc}%)`);
                    $('#qaPercLabel').text(`(${classInfo.qa_perce}%)`);
                    
                    renderGradebook();
                    calculateSummary();
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

    /**
     * Render gradebook tables
     */
    function renderGradebook() {
        renderComponentTable('WW', gradebookData.columns.WW || [], '#wwHeaderRow', '#wwTableBody');
        renderComponentTable('PT', gradebookData.columns.PT || [], '#ptHeaderRow', '#ptTableBody');
        renderComponentTable('QA', gradebookData.columns.QA || [], '#qaHeaderRow', '#qaTableBody');
    }

    /**
     * Render component table (WW, PT, or QA)
     */
    function renderComponentTable(componentType, columns, headerSelector, bodySelector) {
        // Render header
        let headerHtml = `
            <th class="sticky-col">USN</th>
            <th class="sticky-col-2">Student Name</th>
        `;

        let totalMaxPoints = 0;
        columns.forEach(col => {
            totalMaxPoints += parseFloat(col.max_points);
            let sourceIcon = col.source_type === 'online' ? 
                '<i class="fas fa-wifi text-info" title="Online Quiz"></i> ' : '';
            
            headerHtml += `
                <th class="text-center">
                    ${sourceIcon}${escapeHtml(col.column_name)}
                    <br><small>(${col.max_points}pts)</small>
                </th>
            `;
        });

        headerHtml += `
            <th class="text-center">Total<br><small>(${totalMaxPoints}pts)</small></th>
            <th class="text-center bg-light">Score<br><small>(%)</small></th>
        `;

        $(headerSelector).html(headerHtml);

        // Render body
        let bodyHtml = '';
        if (gradebookData.students.length === 0) {
            bodyHtml = `
                <tr>
                    <td colspan="${columns.length + 4}" class="text-center text-muted">
                        <i class="fas fa-users"></i> No students enrolled
                    </td>
                </tr>
            `;
        } else {
            gradebookData.students.forEach(student => {
                let rowHtml = `
                    <tr>
                        <td class="sticky-col">${escapeHtml(student.student_number)}</td>
                        <td class="sticky-col-2">${escapeHtml(student.full_name)}</td>
                `;

                let total = 0;
                let studentScores = student[componentType.toLowerCase()] || {};

                columns.forEach(col => {
                    let scoreData = studentScores[col.column_name] || {};
                    let score = scoreData.score !== null ? scoreData.score : '';
                    let isOnline = scoreData.source === 'online';
                    
                    if (score !== '') {
                        total += parseFloat(score);
                    }

                    let badge = isOnline ? 
                        '<span class="badge badge-info badge-sm online-badge">Online</span>' : '';

                    rowHtml += `
                        <td class="text-center score-cell">
                            ${badge}
                            <input type="number" 
                                   class="form-control form-control-sm gradebook-input score-input" 
                                   data-column-id="${col.id}"
                                   data-student="${escapeHtml(student.student_number)}"
                                   data-max="${col.max_points}"
                                   value="${score}"
                                   step="0.01"
                                   min="0"
                                   max="${col.max_points}"
                                   ${isOnline ? 'readonly title="Score from online quiz"' : ''}>
                        </td>
                    `;
                });

                let percentage = totalMaxPoints > 0 ? (total / totalMaxPoints * 100).toFixed(2) : '0.00';

                rowHtml += `
                        <td class="text-center"><strong>${total.toFixed(2)}</strong></td>
                        <td class="text-center bg-light"><strong>${percentage}%</strong></td>
                    </tr>
                `;

                bodyHtml += rowHtml;
            });
        }

        $(bodySelector).html(bodyHtml);
    }

    /**
     * Calculate and render summary
     */
    function calculateSummary() {
        let summaryHtml = '';
        let grades = [];

        gradebookData.students.forEach(student => {
            // Calculate WW score
            let wwTotal = 0, wwMax = 0;
            Object.values(student.ww || {}).forEach(item => {
                if (item.score !== null) {
                    wwTotal += parseFloat(item.score);
                }
                wwMax += parseFloat(item.max_points);
            });
            let wwPerc = wwMax > 0 ? (wwTotal / wwMax * 100) : 0;
            let wwWeighted = wwPerc * (classInfo.ww_perc / 100);

            // Calculate PT score
            let ptTotal = 0, ptMax = 0;
            Object.values(student.pt || {}).forEach(item => {
                if (item.score !== null) {
                    ptTotal += parseFloat(item.score);
                }
                ptMax += parseFloat(item.max_points);
            });
            let ptPerc = ptMax > 0 ? (ptTotal / ptMax * 100) : 0;
            let ptWeighted = ptPerc * (classInfo.pt_perc / 100);

            // Calculate QA score
            let qaTotal = 0, qaMax = 0;
            Object.values(student.qa || {}).forEach(item => {
                if (item.score !== null) {
                    qaTotal += parseFloat(item.score);
                }
                qaMax += parseFloat(item.max_points);
            });
            let qaPerc = qaMax > 0 ? (qaTotal / qaMax * 100) : 0;
            let qaWeighted = qaPerc * (classInfo.qa_perce / 100);

            // Calculate initial and quarterly grade
            let initialGrade = wwWeighted + ptWeighted + qaWeighted;
            let quarterlyGrade = Math.round(initialGrade);

            grades.push(quarterlyGrade);

            summaryHtml += `
                <tr>
                    <td>${escapeHtml(student.student_number)}</td>
                    <td>${escapeHtml(student.full_name)}</td>
                    <td class="text-center">${wwWeighted.toFixed(2)}</td>
                    <td class="text-center">${ptWeighted.toFixed(2)}</td>
                    <td class="text-center">${qaWeighted.toFixed(2)}</td>
                    <td class="text-center">${initialGrade.toFixed(2)}</td>
                    <td class="text-center bg-primary"><strong>${quarterlyGrade}</strong></td>
                </tr>
            `;
        });

        $('#summaryTableBody').html(summaryHtml);

        // Calculate statistics
        if (grades.length > 0) {
            let avg = grades.reduce((a, b) => a + b, 0) / grades.length;
            let highest = Math.max(...grades);
            let lowest = Math.min(...grades);
            let passing = grades.filter(g => g >= 75).length;
            let passingRate = (passing / grades.length * 100).toFixed(0);

            $('#avgGrade').text(avg.toFixed(2));
            $('#highestGrade').text(highest.toFixed(2));
            $('#lowestGrade').text(lowest.toFixed(2));
            $('#passingRate').text(passingRate + '%');
        }
    }

    /**
     * Handle score input change with debounce
     */
    let scoreUpdateTimeout;
    $(document).on('change', '.score-input', function() {
        let input = $(this);
        let columnId = input.data('column-id');
        let studentNumber = input.data('student');
        let maxPoints = parseFloat(input.data('max'));
        let score = input.val();

        // Validate score
        if (score !== '' && (parseFloat(score) < 0 || parseFloat(score) > maxPoints)) {
            toastr.warning(`Score must be between 0 and ${maxPoints}`);
            input.val('');
            return;
        }

        // Update score
        clearTimeout(scoreUpdateTimeout);
        scoreUpdateTimeout = setTimeout(function() {
            updateScore(columnId, studentNumber, score);
        }, 500);
    });

    /**
     * Update score via AJAX
     */
    function updateScore(columnId, studentNumber, score) {
        $.ajax({
            url: API_ROUTES.updateScore,
            type: 'POST',
            data: {
                column_id: columnId,
                student_number: studentNumber,
                score: score === '' ? null : score
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Score updated');
                    loadGradebook(); // Reload to recalculate
                } else {
                    toastr.error('Failed to update score');
                }
            },
            error: function() {
                toastr.error('Failed to update score');
            }
        });
    }

    /**
     * Add Column button
     */
    $('#addColumnBtn').click(function() {
        $('#addColumnModal').modal('show');
        loadAvailableQuizzes();
    });

    /**
     * Source type change
     */
    $('#sourceType').change(function() {
        if ($(this).val() === 'online') {
            $('#quizSelectGroup').show();
            $('#quizSelect').prop('required', true);
        } else {
            $('#quizSelectGroup').hide();
            $('#quizSelect').prop('required', false);
        }
    });

    /**
     * Load available quizzes
     */
    function loadAvailableQuizzes() {
        $.ajax({
            url: API_ROUTES.getQuizzes,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    let options = '<option value="">Select Quiz</option>';
                    response.data.forEach(quiz => {
                        options += `<option value="${quiz.id}">
                            ${escapeHtml(quiz.lesson_title)} - ${escapeHtml(quiz.title)}
                        </option>`;
                    });
                    $('#quizSelect').html(options);
                } else {
                    $('#quizSelect').html('<option value="">No quizzes available</option>');
                }
            },
            error: function() {
                $('#quizSelect').html('<option value="">Failed to load quizzes</option>');
            }
        });
    }

    /**
     * Add column form submit
     */
    $('#addColumnForm').submit(function(e) {
        e.preventDefault();
        
        let formData = {
            component_type: $('[name="component_type"]').val(),
            column_name: $('[name="column_name"]').val(),
            max_points: $('[name="max_points"]').val(),
            source_type: $('[name="source_type"]').val(),
            quiz_id: $('[name="quiz_id"]').val()
        };

        $.ajax({
            url: API_ROUTES.addColumn,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success('Column added successfully');
                    $('#addColumnModal').modal('hide');
                    $('#addColumnForm')[0].reset();
                    loadGradebook();
                } else {
                    toastr.error(response.message || 'Failed to add column');
                }
            },
            error: function(xhr) {
                let msg = 'Failed to add column';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                toastr.error(msg);
            }
        });
    });

    /**
     * Import Excel
     */
    $('#importExcelBtn').click(function() {
        $('#excelFileInput').click();
    });

    $('#excelFileInput').change(function() {
        let file = this.files[0];
        if (!file) return;

        let formData = new FormData();
        formData.append('file', file);

        $.ajax({
            url: API_ROUTES.importGradebook,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success('Gradebook imported successfully');
                    loadGradebook();
                } else {
                    toastr.error(response.message || 'Import failed');
                }
            },
            error: function() {
                toastr.error('Failed to import gradebook');
            }
        });

        $(this).val('');
    });

    /**
     * Export Excel
     */
    $('#exportGradebookBtn').click(function() {
        window.location.href = API_ROUTES.exportGradebook;
    });

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        let map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
});