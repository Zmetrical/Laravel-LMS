console.log("gradebook view");

$(document).ready(function () {
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

    let currentQuarterId = null;
    let currentSectionId = null;
    let gradebookData = null;
    let classInfo = null;
    let allStudentsData = null;

    // Initialize first quarter as active
    if (QUARTERS.length > 0) {
        currentQuarterId = QUARTERS[0].id;
        $('.quarter-btn').first().addClass('btn-secondary active').removeClass('btn-outline-secondary');
        loadGradebook(currentQuarterId);
    }

    // Quarter button group click
    $('.quarter-btn').click(function () {
        const quarterId = $(this).data('quarter');
        if (quarterId === currentQuarterId) return;

        $('.quarter-btn').removeClass('btn-secondary active').addClass('btn-outline-secondary');
        $(this).addClass('btn-secondary active').removeClass('btn-outline-secondary');
        
        currentQuarterId = quarterId;
        loadGradebook(currentQuarterId);
    });

    // Section filter
    $('#sectionFilter').change(function () {
        currentSectionId = $(this).val();
        filterAndRenderData();
    });

    function loadGradebook(quarterId) {
        showTabLoading();
        
        $.ajax({
            url: API_ROUTES.getGradebook,
            type: 'GET',
            data: { quarter_id: quarterId },
            success: function (response) {
                console.log('Gradebook response:', response);
                
                if (response.success) {
                    gradebookData = response.data;
                    classInfo = response.data.class;
                    allStudentsData = response.data.students;
                    currentQuarterId = quarterId;
                    
                    const quarterName = QUARTERS.find(q => q.id == quarterId)?.name || '';
                    $('#exportQuarterName').text(quarterName);
                    
                    if (!allStudentsData || allStudentsData.length === 0) {
                        toastr.info('No students enrolled in this class yet');
                        clearAllTables();
                    } else {
                        filterAndRenderData();
                    }
                } else {
                    toastr.error(response.message || 'Failed to load gradebook');
                    clearAllTables();
                }
            },
            error: function (xhr) {
                console.error('Load gradebook error:', xhr);
                const errorMsg = xhr.responseJSON?.message || 'Failed to load gradebook data';
                toastr.error(errorMsg);
                clearAllTables();
            }
        });
    }

    function filterAndRenderData() {
        if (!allStudentsData) return;

        let filteredStudents = allStudentsData;

        // Filter by section if selected
        if (currentSectionId) {
            filteredStudents = allStudentsData.filter(s => s.section_id == currentSectionId);
        }

        if (filteredStudents.length === 0) {
            toastr.info('No students found for selected filters');
            clearAllTables();
            return;
        }

        const filteredData = {
            ...gradebookData,
            students: filteredStudents
        };

        renderAllTables(filteredData);
        renderSummaryTable(filteredData);
    }

    function showTabLoading() {
        const loadingHtml = '<tr class="loading-row"><td colspan="100"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
        $('#wwBody, #ptBody, #qaBody, #summaryTableBody').html(loadingHtml);
    }

    function clearAllTables() {
        $('#wwBody, #ptBody, #qaBody, #summaryTableBody').html(
            '<tr class="loading-row"><td colspan="100">No data available</td></tr>'
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
        
        // Group students by gender
        const maleStudents = students.filter(s => s.gender && s.gender.toLowerCase() === 'male');
        const femaleStudents = students.filter(s => s.gender && s.gender.toLowerCase() === 'female');
        const otherStudents = students.filter(s => !s.gender || (s.gender.toLowerCase() !== 'male' && s.gender.toLowerCase() !== 'female'));

        let bodyHtml = '';
        
        // Render Male students
        if (maleStudents.length > 0) {
            bodyHtml += `<tr class="gender-separator"><td colspan="${cols.length + 4}"><i class="fas fa-mars"></i> MALE</td></tr>`;
            maleStudents.forEach(student => {
                bodyHtml += renderStudentRow(student, cols, componentType, activeColumns, totalMaxPoints);
            });
        }

        // Render Female students
        if (femaleStudents.length > 0) {
            bodyHtml += `<tr class="gender-separator"><td colspan="${cols.length + 4}"><i class="fas fa-venus"></i> FEMALE</td></tr>`;
            femaleStudents.forEach(student => {
                bodyHtml += renderStudentRow(student, cols, componentType, activeColumns, totalMaxPoints);
            });
        }

        // Render Other students (if any)
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
        let rowHtml = '<tr>';
        rowHtml += `<td class="student-info">${escapeHtml(student.student_number)}</td>`;
        rowHtml += `<td class="student-info">${escapeHtml(student.full_name)}</td>`;
        
        let total = 0;
        
        cols.forEach(col => {
            const scoreData = student[componentType.toLowerCase()][col.column_name] || {};
            const score = scoreData.score;
            
            rowHtml += `<td class="score-cell">`;
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
        
        // Group students by gender
        const maleStudents = students.filter(s => s.gender && s.gender.toLowerCase() === 'male');
        const femaleStudents = students.filter(s => s.gender && s.gender.toLowerCase() === 'female');
        const otherStudents = students.filter(s => !s.gender || (s.gender.toLowerCase() !== 'male' && s.gender.toLowerCase() !== 'female'));

        let summaryHtml = '';
        
        // Render Male students
        if (maleStudents.length > 0) {
            summaryHtml += `<tr class="gender-separator"><td colspan="7"><i class="fas fa-mars"></i> MALE</td></tr>`;
            maleStudents.forEach(student => {
                summaryHtml += renderSummaryRow(student, classInfo);
            });
        }

        // Render Female students
        if (femaleStudents.length > 0) {
            summaryHtml += `<tr class="gender-separator"><td colspan="7"><i class="fas fa-venus"></i> FEMALE</td></tr>`;
            femaleStudents.forEach(student => {
                summaryHtml += renderSummaryRow(student, classInfo);
            });
        }

        // Render Other students
        if (otherStudents.length > 0) {
            summaryHtml += `<tr class="gender-separator"><td colspan="7"><i class="fas fa-user"></i> OTHER</td></tr>`;
            otherStudents.forEach(student => {
                summaryHtml += renderSummaryRow(student, classInfo);
            });
        }

        $('#summaryTableBody').html(summaryHtml || '<tr class="loading-row"><td colspan="7">No data available</td></tr>');
    }

    function renderSummaryRow(student, classInfo) {
        let wwTotal = 0, wwMax = 0;
        Object.entries(student.ww || {}).forEach(([key, item]) => {
            if (item.is_active) {
                if (item.score !== null && item.score !== undefined) {
                    wwTotal += parseFloat(item.score);
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
                }
                qaMax += parseFloat(item.max_points);
            }
        });
        const qaPerc = qaMax > 0 ? (qaTotal / qaMax * 100) : 0;
        const qaWeighted = qaPerc * (classInfo.qa_perce / 100);

        const initialGrade = wwWeighted + ptWeighted + qaWeighted;
        const quarterlyGrade = Math.round(initialGrade);

        return `
            <tr>
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

    $('#exportBtn').click(function () {
        if (!currentQuarterId) {
            toastr.warning('Please select a quarter first');
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
            toastr.success('Export started! Your download should begin shortly.');
        }, 1000);
    });
});