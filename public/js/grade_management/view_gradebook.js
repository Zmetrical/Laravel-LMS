console.log("gradebook view");

$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let currentQuarterId = null;
    let currentSectionId = null;
    let currentViewType = 'quarter';
    let gradebookData = null;
    let classInfo = null;
    let allStudentsData = null;
    let finalGradesSubmitted = false;

    if (QUARTERS.length > 0) {
        currentQuarterId = QUARTERS[0].id;
        $('.quarter-btn[data-type="quarter"]').first().addClass('btn-secondary active').removeClass('btn-outline-secondary');
    }

    showEmptyState();

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
            clearAllTables();
            return;
        }

        if (currentViewType === 'quarter') {
            if (currentQuarterId) {
                loadGradebook(currentQuarterId);
            }
        } else {
            loadFinalGrade();
        }
    });

    $('.quarter-btn').click(function () {
        const type = $(this).data('type');
        
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
            
            currentViewType = 'quarter';
            currentQuarterId = quarterId;
            $('#custom-tabs li').show();
            loadGradebook(currentQuarterId);
        }
    });

    function showEmptyState() {
        const emptyHtml = `
            <tr class="empty-state-row">
                <td colspan="100">
                    <div style="padding: 60px 20px; text-align: center;">
                        <i class="fas fa-table" style="font-size: 48px; color: #6c757d; margin-bottom: 15px;"></i>
                        <h5 style="color: #6c757d; margin-bottom: 10px;">No Section Selected</h5>
                        <p style="color: #adb5bd;">Please select a section from the dropdown above to view grades.</p>
                    </div>
                </td>
            </tr>
        `;
        $('#wwBody, #ptBody, #qaBody, #summaryTableBody, #finalGradeTableBody').html(emptyHtml);
    }

    function loadGradebook(quarterId) {
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
            return;
        }

        showTabLoading();
        $('#finalGradeTable').hide();
        $('.summary-table-wrapper').first().show();
        
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
                    allStudentsData = response.data.students;
                    currentQuarterId = quarterId;
                    
                    const quarterName = QUARTERS.find(q => q.id == quarterId)?.name || '';
                    $('#exportQuarterName').text(quarterName);
                    
                    if (!allStudentsData || allStudentsData.length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'No Students',
                            text: 'No students enrolled in this section',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                        clearAllTables();
                    } else {
                        renderAllTables(gradebookData);
                        renderSummaryTable(gradebookData);
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to load gradebook'
                    });
                    clearAllTables();
                }
            },
            error: function (xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to load gradebook data';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
                clearAllTables();
            }
        });
    }

    function loadFinalGrade() {
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
            return;
        }

        const loadingHtml = '<tr class="loading-row"><td colspan="7"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
        $('#finalGradeTableBody').html(loadingHtml);
        
        $('.summary-table-wrapper').first().hide();
        $('#finalGradeTable').show();
        $('#submitFinalGradeBtn').show();
        
        // Check if grades already submitted
        $.ajax({
            url: API_ROUTES.checkFinalGradesStatus,
            type: 'GET',
            data: { 
                semester_id: ACTIVE_SEMESTER_ID,
                section_id: currentSectionId 
            },
            success: function (statusResponse) {
                if (statusResponse.success) {
                    finalGradesSubmitted = statusResponse.data.is_submitted;
                    
                    if (finalGradesSubmitted) {
                        $('#submitFinalGradeBtn').html('<i class="fas fa-check-circle"></i> Grades Already Submitted').prop('disabled', true);
                    } else {
                        $('#submitFinalGradeBtn').html('<i class="fas fa-save"></i> Submit Final Grades').prop('disabled', false);
                    }
                }
                
                // Load the grades
                $.ajax({
                    url: API_ROUTES.getFinalGrade,
                    type: 'GET',
                    data: { section_id: currentSectionId },
                    success: function (response) {
                        if (response.success) {
                            renderFinalGradeTable(response.data);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to load final grades'
                            });
                            $('#finalGradeTableBody').html('<tr class="loading-row"><td colspan="7">No data available</td></tr>');
                        }
                    },
                    error: function (xhr) {
                        const errorMsg = xhr.responseJSON?.message || 'Failed to load final grades';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMsg
                        });
                        $('#finalGradeTableBody').html('<tr class="loading-row"><td colspan="7">Error loading data</td></tr>');
                    }
                });
            }
        });
    }

    function renderFinalGradeTable(data) {
        const students = data.students;
        
        if (!students || students.length === 0) {
            $('#finalGradeTableBody').html('<tr class="loading-row"><td colspan="7">No students found</td></tr>');
            return;
        }

        const maleStudents = students.filter(s => s.gender && s.gender.toLowerCase() === 'male');
        const femaleStudents = students.filter(s => s.gender && s.gender.toLowerCase() === 'female');
        const otherStudents = students.filter(s => !s.gender || (s.gender.toLowerCase() !== 'male' && s.gender.toLowerCase() !== 'female'));

        let html = '';
        
        if (maleStudents.length > 0) {
            html += `<tr class="gender-separator"><td colspan="7"><i class="fas fa-mars"></i> MALE</td></tr>`;
            maleStudents.forEach(student => {
                html += renderFinalGradeRow(student);
            });
        }

        if (femaleStudents.length > 0) {
            html += `<tr class="gender-separator"><td colspan="7"><i class="fas fa-venus"></i> FEMALE</td></tr>`;
            femaleStudents.forEach(student => {
                html += renderFinalGradeRow(student);
            });
        }

        if (otherStudents.length > 0) {
            html += `<tr class="gender-separator"><td colspan="7"><i class="fas fa-user"></i> OTHER</td></tr>`;
            otherStudents.forEach(student => {
                html += renderFinalGradeRow(student);
            });
        }

        $('#finalGradeTableBody').html(html);
    }

    function renderFinalGradeRow(student) {
        const remarksClass = student.remarks === 'PASSED' ? 'remarks-passed' : 'remarks-failed';
        
        return `
            <tr data-student="${escapeHtml(student.student_number)}">
                <td>${escapeHtml(student.student_number)}</td>
                <td>${escapeHtml(student.full_name)}</td>
                <td class="text-center">${student.q1_grade}</td>
                <td class="text-center">${student.q2_grade}</td>
                <td class="text-center">${student.semester_grade}</td>
                <td class="text-center grade-cell"><strong>${student.final_grade}</strong></td>
                <td class="text-center"><span class="${remarksClass}">${student.remarks}</span></td>
            </tr>
        `;
    }

    function showTabLoading() {
        const loadingHtml = '<tr class="loading-row"><td colspan="100"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
        $('#wwBody, #ptBody, #qaBody, #summaryTableBody').html(loadingHtml);
    }

    function clearAllTables() {
        $('#wwBody, #ptBody, #qaBody, #summaryTableBody').html(
            '<tr class="loading-row"><td colspan="100">No data available</td></tr>'
        );
        $('#finalGradeTableBody').html(
            '<tr class="loading-row"><td colspan="7">No data available</td></tr>'
        );
    }

    function renderAllTables(data) {
        renderTable('WW', data);
        renderTable('PT', data);
        renderTable('QA', data);
    }

    function renderTable(componentType, data) {
        const { class: classInfo, columns, students } = data;
        const cols = columns[componentType] || [];
        
        let headerHtml = '<th class="student-info">USN</th><th class="student-info">Student Name</th>';
        
        cols.forEach(col => {
            const badge = col.source_type === 'online' ? '<span class="badge badge-info online-badge">Online</span>' : '';
            
            headerHtml += `<th>
                <div class="column-header">
                    <span class="column-title">${escapeHtml(col.column_name)}</span>
                    ${badge}
                    <span class="column-points">${col.max_points}pts</span>
                </div>
            </th>`;
        });
        
        const activeColumns = cols.filter(c => c.is_active);
        const totalMaxPoints = activeColumns.reduce((sum, c) => sum + parseFloat(c.max_points), 0);
        
        headerHtml += `<th class="component-header">
            <div class="column-header">
                <span class="column-title">Total</span>
                <span class="column-points">${totalMaxPoints}pts</span>
            </div>
        </th>`;
        headerHtml += `<th class="component-header">
            <div class="column-header">
                <span class="column-title">Score</span>
                <span class="column-points">%</span>
            </div>
        </th>`;
        
        const maleStudents = students.filter(s => s.gender && s.gender.toLowerCase() === 'male');
        const femaleStudents = students.filter(s => s.gender && s.gender.toLowerCase() === 'female');
        const otherStudents = students.filter(s => !s.gender || (s.gender.toLowerCase() !== 'male' && s.gender.toLowerCase() !== 'female'));

        let bodyHtml = '';
        
        if (maleStudents.length > 0) {
            bodyHtml += `<tr class="gender-separator"><td colspan="${cols.length + 4}"><i class="fas fa-mars"></i> MALE</td></tr>`;
            maleStudents.forEach(student => {
                bodyHtml += renderStudentRow(student, cols, componentType, activeColumns, totalMaxPoints);
            });
        }

        if (femaleStudents.length > 0) {
            bodyHtml += `<tr class="gender-separator"><td colspan="${cols.length + 4}"><i class="fas fa-venus"></i> FEMALE</td></tr>`;
            femaleStudents.forEach(student => {
                bodyHtml += renderStudentRow(student, cols, componentType, activeColumns, totalMaxPoints);
            });
        }

        if (otherStudents.length > 0) {
            bodyHtml += `<tr class="gender-separator"><td colspan="${cols.length + 4}"><i class="fas fa-user"></i> OTHER</td></tr>`;
            otherStudents.forEach(student => {
                bodyHtml += renderStudentRow(student, cols, componentType, activeColumns, totalMaxPoints);
            });
        }
        
        $(`#${componentType.toLowerCase()}HeaderRow`).html(headerHtml);
        $(`#${componentType.toLowerCase()}Body`).html(bodyHtml || '<tr class="loading-row"><td colspan="100">No data available</td></tr>');
    }

    function renderStudentRow(student, cols, componentType, activeColumns, totalMaxPoints) {
        let hasMissing = false;
        let rowHtml = '<tr';
        
        let total = 0;
        
        // Check for missing grades
        activeColumns.forEach(col => {
            const scoreData = student[componentType.toLowerCase()][col.column_name] || {};
            const score = scoreData.score;
            if (score === null || score === undefined) {
                hasMissing = true;
            }
        });
        
        if (hasMissing) {
            rowHtml += ' class="row-missing-grade"';
        }
        
        rowHtml += '>';
        rowHtml += `<td class="student-info">${escapeHtml(student.student_number)}</td>`;
        rowHtml += `<td class="student-info">${escapeHtml(student.full_name)}</td>`;
        
        cols.forEach(col => {
            const scoreData = student[componentType.toLowerCase()][col.column_name] || {};
            const score = scoreData.score;
            
            rowHtml += `<td class="score-cell`;
            if (col.is_active && (score === null || score === undefined)) {
                rowHtml += ' missing-score';
            }
            rowHtml += `">`;
            
            if (score !== null && score !== undefined) {
                rowHtml += parseFloat(score).toFixed(2);
                if (col.is_active) {
                    total += parseFloat(score);
                }
            } else {
                rowHtml += '-';
            }
            rowHtml += `</td>`;
        });
        
        const percentage = totalMaxPoints > 0 ? (total / totalMaxPoints * 100).toFixed(2) : '0.00';
        rowHtml += `<td class="total-cell">${total.toFixed(2)}</td>`;
        rowHtml += `<td class="total-cell">${percentage}%</td>`;
        rowHtml += '</tr>';
        
        return rowHtml;
    }

    function renderSummaryTable(data) {
        const { class: classInfo, students } = data;
        
        const maleStudents = students.filter(s => s.gender && s.gender.toLowerCase() === 'male');
        const femaleStudents = students.filter(s => s.gender && s.gender.toLowerCase() === 'female');
        const otherStudents = students.filter(s => !s.gender || (s.gender.toLowerCase() !== 'male' && s.gender.toLowerCase() !== 'female'));

        let summaryHtml = '';
        
        if (maleStudents.length > 0) {
            summaryHtml += `<tr class="gender-separator"><td colspan="7"><i class="fas fa-mars"></i> MALE</td></tr>`;
            maleStudents.forEach(student => {
                summaryHtml += renderSummaryRow(student, classInfo);
            });
        }

        if (femaleStudents.length > 0) {
            summaryHtml += `<tr class="gender-separator"><td colspan="7"><i class="fas fa-venus"></i> FEMALE</td></tr>`;
            femaleStudents.forEach(student => {
                summaryHtml += renderSummaryRow(student, classInfo);
            });
        }

        if (otherStudents.length > 0) {
            summaryHtml += `<tr class="gender-separator"><td colspan="7"><i class="fas fa-user"></i> OTHER</td></tr>`;
            otherStudents.forEach(student => {
                summaryHtml += renderSummaryRow(student, classInfo);
            });
        }

        $('#summaryTableBody').html(summaryHtml || '<tr class="loading-row"><td colspan="7">No data available</td></tr>');
    }

    function renderSummaryRow(student, classInfo) {
        let hasMissing = false;
        
        let wwTotal = 0, wwMax = 0;
        Object.entries(student.ww || {}).forEach(([key, item]) => {
            if (item.is_active) {
                if (item.score !== null && item.score !== undefined) {
                    wwTotal += parseFloat(item.score);
                } else {
                    hasMissing = true;
                }
                wwMax += parseFloat(item.max_points);
            }
        });
        const wwPerc = wwMax > 0 ? (wwTotal / wwMax * 100) : 0;
        const wwWeighted = wwPerc * (classInfo.ww_perc / 100);

        let ptTotal = 0, ptMax = 0;
        Object.entries(student.pt || {}).forEach(([key, item]) => {
            if (item.is_active) {
                if (item.score !== null && item.score !== undefined) {
                    ptTotal += parseFloat(item.score);
                } else {
                    hasMissing = true;
                }
                ptMax += parseFloat(item.max_points);
            }
        });
        const ptPerc = ptMax > 0 ? (ptTotal / ptMax * 100) : 0;
        const ptWeighted = ptPerc * (classInfo.pt_perc / 100);

        let qaTotal = 0, qaMax = 0;
        Object.entries(student.qa || {}).forEach(([key, item]) => {
            if (item.is_active) {
                if (item.score !== null && item.score !== undefined) {
                    qaTotal += parseFloat(item.score);
                } else {
                    hasMissing = true;
                }
                qaMax += parseFloat(item.max_points);
            }
        });
        const qaPerc = qaMax > 0 ? (qaTotal / qaMax * 100) : 0;
        const qaWeighted = qaPerc * (classInfo.qa_perce / 100);

        const initialGrade = wwWeighted + ptWeighted + qaWeighted;
        const quarterlyGrade = Math.round(initialGrade);

        let rowClass = hasMissing ? ' class="row-missing-grade"' : '';

        return `
            <tr${rowClass}>
                <td>${escapeHtml(student.student_number)}</td>
                <td>${escapeHtml(student.full_name)}</td>
                <td class="text-center">${wwWeighted.toFixed(2)}</td>
                <td class="text-center">${ptWeighted.toFixed(2)}</td>
                <td class="text-center">${qaWeighted.toFixed(2)}</td>
                <td class="text-center">${initialGrade.toFixed(2)}</td>
                <td class="text-center grade-cell"><strong>${quarterlyGrade}</strong></td>
            </tr>
        `;
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

    $('#editBtn').on('click', function() {
        window.location.href = API_ROUTES.editGradebook;
    });

    $('#submitFinalGradeBtn').click(function() {
        if (finalGradesSubmitted) {
            Swal.fire({
                icon: 'info',
                title: 'Already Submitted',
                text: 'Final grades have already been submitted for this semester'
            });
            return;
        }

        // Collect all student grades
        const grades = [];
        $('#finalGradeTableBody tr:not(.gender-separator)').each(function() {
            const $row = $(this);
            const studentNumber = $row.data('student');
            
            if (studentNumber) {
                grades.push({
                    student_number: studentNumber,
                    q1_grade: parseFloat($row.find('td:eq(2)').text()),
                    q2_grade: parseFloat($row.find('td:eq(3)').text()),
                    final_grade: parseInt($row.find('td:eq(5)').text()),
                    remarks: $row.find('td:eq(6) span').text()
                });
            }
        });

        if (grades.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Grades',
                text: 'No grades to submit'
            });
            return;
        }

        Swal.fire({
            title: 'Submit Final Grades?',
            html: `You are about to submit final grades for <strong>${grades.length}</strong> student(s).<br><br>This action cannot be undone.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#007bff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check"></i> Yes, Submit',
            cancelButtonText: '<i class="fas fa-times"></i> Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                submitFinalGrades(grades);
            }
        });
    });

    function submitFinalGrades(grades) {
        const btn = $('#submitFinalGradeBtn');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

        $.ajax({
            url: API_ROUTES.submitFinalGrades,
            type: 'POST',
            data: {
                grades: grades,
                semester_id: ACTIVE_SEMESTER_ID,
                section_id: currentSectionId
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Final grades submitted successfully',
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        finalGradesSubmitted = true;
                        btn.html('<i class="fas fa-check-circle"></i> Grades Already Submitted');
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to submit grades'
                    });
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i> Submit Final Grades');
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to submit final grades';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Submit Final Grades');
            }
        });
    }

    $('#exportBtn').click(function () {
        if (!currentQuarterId) {
            Swal.fire({
                icon: 'warning',
                title: 'Quarter Required',
                text: 'Please select a quarter first'
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
        if (currentViewType === 'final') {
            Swal.fire({
                icon: 'warning',
                title: 'Export Not Available',
                text: 'Export is only available for quarter view'
            });
            return;
        }
        $('#exportModal').modal('show');
    });

    $('#exportForm').submit(function (e) {
        e.preventDefault();

        const btn = $('#exportForm button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = API_ROUTES.exportGradebook;

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = $('meta[name="csrf-token"]').attr('content');
        form.appendChild(csrfInput);

        const quarterInput = document.createElement('input');
        quarterInput.type = 'hidden';
        quarterInput.name = 'quarter_id';
        quarterInput.value = currentQuarterId;
        form.appendChild(quarterInput);

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        setTimeout(function () {
            btn.prop('disabled', false).html('<i class="fas fa-download"></i> Download Excel');
            $('#exportModal').modal('hide');
            Swal.fire({
                icon: 'success',
                title: 'Export Started',
                text: 'Your download should begin shortly',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }, 1000);
    });
});