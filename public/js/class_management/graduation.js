$(document).ready(function () {
    let allStudents = [];
    let currentStudentNumber = null;
    let semesterNames = {};

    loadStudents();

    // ── Load ─────────────────────────────────────────────────────────────────

    function loadStudents() {
        $('#tableLoading').show();
        $('#tableContainer').hide();
        $('#tableEmpty').hide();
        $('#tableActions').hide();

        $.ajax({
            url: GRAD_ROUTES.getStudents,
            method: 'GET',
            success: function (res) {
                $('#tableLoading').hide();

                if (!res.success) {
                    showError(res.message || 'Failed to load students.');
                    return;
                }

                // Build semester id → name map
                res.data.forEach(function (s) {
                    (s.class_details || []).forEach(function (c) {
                        if (c.semester_id && !semesterNames[c.semester_id]) {
                            semesterNames[c.semester_id] = 'Semester ' + c.semester_id;
                        }
                    });
                });

                allStudents = res.data;
                buildSectionFilter();
                applyFilter();

                if (res.note) {
                    toastInfo(res.note);
                }
            },
            error: function (xhr) {
                $('#tableLoading').hide();
                showError(xhr.responseJSON?.message || 'Failed to load students.');
            }
        });
    }

    // ── Section Filter Builder ────────────────────────────────────────────────

    function buildSectionFilter() {
        const seen = {};
        const $sel = $('#filterSection');
        $sel.find('option:not(:first)').remove();

        allStudents.forEach(function (s) {
            // Regular students have a section_id; use section name if available
            // We'll key on whatever section label we have
            const label = s.section_name || null;
            const id    = s.section_id   || null;

            if (id && label && !seen[id]) {
                seen[id] = true;
                $sel.append($('<option>').val(id).text(label));
            }
        });
    }

    // ── Filter ────────────────────────────────────────────────────────────────

    function applyFilter() {
        const eligibility = $('#filterEligibility').val() || 'all';
        const type        = $('#filterType').val();
        const section     = $('#filterSection').val();
        const search      = $('#searchInput').val().toLowerCase().trim();

        let filtered = allStudents;

        if (eligibility !== 'all') {
            filtered = filtered.filter(s => s.eligibility_status === eligibility);
        }
        if (type) {
            filtered = filtered.filter(s => s.student_type === type);
        }
        if (section) {
            filtered = filtered.filter(s => String(s.section_id) === String(section));
        }
        if (search) {
            filtered = filtered.filter(s =>
                s.full_name.toLowerCase().includes(search) ||
                s.student_number.toLowerCase().includes(search)
            );
        }

        $('#studentsCount').text(filtered.length + ' Students');
        renderTable(filtered);
    }

    $('#filterEligibility, #filterType, #filterSection').on('change', applyFilter);
    $('#searchInput').on('input', applyFilter);

    $('#clearFilters').on('click', function () {
        $('#searchInput').val('');
        $('#filterEligibility').val('all');
        $('#filterType').val('');
        $('#filterSection').val('');
        applyFilter();
    });

    // ── Render Table ──────────────────────────────────────────────────────────

    function renderTable(students) {
        if (!students.length) {
            $('#tableContainer').hide();
            $('#tableActions').hide();
            $('#tableEmpty').show();
            updateSelectedCount();
            return;
        }

        $('#tableEmpty').hide();
        $('#tableContainer').show();
        $('#tableActions').show();

        const tbody = $('#graduationTableBody');
        tbody.empty();

        students.forEach(function (s, idx) {
            const eligibilityBadge = eligibilityLabel(s.eligibility_status);
            const statusCell       = gradStatusCell(s);
            const typeBadge        = s.student_type === 'regular'
                ? '<span class="badge badge-primary">Regular</span>'
                : '<span class="badge badge-secondary">Irregular</span>';
            const sectionLabel = s.section_name
                ? `<small>${escHtml(s.section_name)}</small>`
                : '<small class="text-muted">—</small>';

            const checkboxCell = !IS_FINALIZED
                ? `<td class="text-center align-middle">
                       <input type="checkbox" class="student-checkbox"
                              data-student="${s.student_number}">
                   </td>`
                : '';

            const row = `
                <tr data-student="${s.student_number}">
                    ${checkboxCell}
                    <td class="text-muted">${idx + 1}</td>
                    <td>
                        <strong>${escHtml(s.full_name)}</strong><br>
                        <small class="text-muted">${escHtml(s.student_number)}</small>
                    </td>
                    <td class="text-center">${typeBadge}</td>
                    <td class="text-center">${sectionLabel}</td>
                    <td class="text-center">${s.total_subjects}</td>
                    <td class="text-center text-primary"><strong>${s.passed_count}</strong></td>
                    <td class="text-center ${s.failed_count > 0 ? 'text-secondary' : ''}">${s.failed_count}</td>
                    <td class="text-center ${s.inc_count > 0 ? 'text-secondary' : ''}">${s.inc_count}</td>
                    <td class="text-center">${eligibilityBadge}</td>
                    <td class="text-center">${statusCell}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-secondary view-details-btn"
                                data-student="${s.student_number}"
                                title="View Subject Grades">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });

        updateSelectedCount();
    }

    function eligibilityLabel(status) {
        const map = {
            eligible: '<span class="badge badge-primary">Eligible</span>',
            issues:   '<span class="badge badge-secondary">Has Issues</span>',
            missing:  '<span class="badge badge-dark">Missing</span>',
        };
        return map[status] || '<span class="badge badge-light">—</span>';
    }

    function gradStatusCell(s) {
        if (s.graduation_status) {
            const cls   = s.graduation_status === 'graduated' ? 'badge-primary' : 'badge-secondary';
            const label = s.graduation_status === 'graduated' ? 'Graduated' : 'Not Graduated';
            return `<span class="badge ${cls}">${label}</span>`;
        }
        return '<span class="text-muted">—</span>';
    }

    // ── Checkbox / Select All ─────────────────────────────────────────────────

    $('#selectAllCheckbox').on('change', function () {
        $('.student-checkbox').prop('checked', $(this).is(':checked'));
        updateSelectedCount();
    });

    $(document).on('change', '.student-checkbox', function () {
        const total   = $('.student-checkbox').length;
        const checked = $('.student-checkbox:checked').length;
        $('#selectAllCheckbox').prop('checked', total === checked);
        updateSelectedCount();
    });

    $('#selectAllBtn').on('click', function () {
        $('#selectAllCheckbox').prop('checked', true).trigger('change');
    });

    $('#deselectAllBtn').on('click', function () {
        $('#selectAllCheckbox').prop('checked', false).trigger('change');
    });

    function updateSelectedCount() {
        const count = $('.student-checkbox:checked').length;
        $('#selectedCount').text(count);
        $('#applyBulkBtn').prop('disabled', count === 0);
    }

    // ── Bulk Apply ────────────────────────────────────────────────────────────

    $('#applyBulkBtn').on('click', function () {
        const selected = [];
        $('.student-checkbox:checked').each(function () {
            selected.push($(this).attr('data-student'));
        });

        if (!selected.length) return;

        const status  = $('#bulkGradStatus').val();
        const label   = status === 'graduated' ? 'Graduated' : 'Not Graduated';

        Swal.fire({
            title: 'Apply to Selected?',
            html: `Set <strong>${selected.length}</strong> student(s) as <strong>${label}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Apply',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#007bff',
        }).then(function (result) {
            if (!result.isConfirmed) return;
            processBulk(selected, status);
        });
    });

    function processBulk(studentNumbers, status) {
        const batchSize = 20;
        const batches   = [];
        for (let i = 0; i < studentNumbers.length; i += batchSize) {
            batches.push(studentNumbers.slice(i, i + batchSize));
        }

        Swal.fire({
            title: '<i class="fas fa-spinner fa-spin"></i> Saving...',
            html: `
                <div class="progress mt-3" style="height:24px;">
                    <div id="bulkProgress" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                         style="width:0%">0%</div>
                </div>
                <small id="bulkDetail" class="text-muted">0 / ${studentNumbers.length}</small>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
        });

        let done = 0;

        function nextBatch(idx) {
            if (idx >= batches.length) {
                Swal.fire({
                    icon: 'success',
                    title: 'Done!',
                    text: `${done} record(s) saved.`,
                    timer: 2000,
                    showConfirmButton: false,
                }).then(function () { location.reload(); });
                return;
            }

            const batch   = batches[idx];
            const calls   = batch.map(function (sn) {
                return $.ajax({
                    url: GRAD_ROUTES.saveStudentRecord,
                    method: 'POST',
                    data: { student_number: sn, status: status },
                    headers: { 'X-CSRF-TOKEN': GRAD_ROUTES.csrfToken },
                });
            });

            $.when.apply($, calls).always(function () {
                done += batch.length;
                const pct = Math.round((done / studentNumbers.length) * 100);
                $('#bulkProgress').css('width', pct + '%').text(pct + '%');
                $('#bulkDetail').text(`${done} / ${studentNumbers.length}`);
                nextBatch(idx + 1);
            });
        }

        nextBatch(0);
    }

    // ── Modal ─────────────────────────────────────────────────────────────────

    $(document).on('click', '.view-details-btn', function () {
        openModal($(this).attr('data-student'));
    });

    function openModal(studentNumber) {
        const s = allStudents.find(x => x.student_number === studentNumber);
        if (!s) return;

        currentStudentNumber = studentNumber;

        $('#modalStudentName').text(s.full_name + ' (' + s.student_number + ')');

        // Set evaluation link
        const evalUrl = GRAD_ROUTES.evaluationBase.replace('__SN__', encodeURIComponent(studentNumber));
        $('#viewEvaluationBtn').attr('href', evalUrl);

        // Subject rows
        const subBody = $('#modalSubjectsBody');
        subBody.empty();

        (s.class_details || []).forEach(function (c) {
            const remarksBadge = remarksLabel(c.remarks);
            const gradeDisplay = c.final_grade !== null ? parseFloat(c.final_grade).toFixed(2) : '—';
            const semLabel     = c.semester_id ? ('Sem ' + c.semester_id) : '—';

            subBody.append(`
                <tr>
                    <td>${escHtml(c.class_code)}</td>
                    <td>${semLabel}</td>
                    <td class="text-center">${gradeDisplay}</td>
                    <td class="text-center">${remarksBadge}</td>
                </tr>
            `);
        });

        if (!s.class_details || !s.class_details.length) {
            subBody.append('<tr><td colspan="4" class="text-center text-muted py-3">No grade records found.</td></tr>');
        }

        $('#studentDetailModal').modal('show');
    }

    function remarksLabel(remarks) {
        if (!remarks) return '<span class="badge badge-dark">Missing</span>';
        const map = {
            'PASSED': '<span class="badge badge-primary">PASSED</span>',
            'FAILED': '<span class="badge badge-secondary">FAILED</span>',
            'INC':    '<span class="badge badge-secondary">INC</span>',
            'DRP':    '<span class="badge badge-dark">DRP</span>',
            'W':      '<span class="badge badge-dark">W</span>',
        };
        return map[remarks] || `<span class="badge badge-light">${escHtml(remarks)}</span>`;
    }

    // ── Finalize ──────────────────────────────────────────────────────────────

    $('#finalizeBtn').on('click', function () {
        const unsaved = allStudents.filter(s => !s.graduation_status).length;

        let confirmText = 'This will lock all graduation records and cannot be undone.';
        if (unsaved > 0) {
            confirmText = `${unsaved} student(s) have no graduation status set. They will be excluded from the finalized list. ${confirmText}`;
        }

        Swal.fire({
            title: 'Finalize Graduation List?',
            html: confirmText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-lock mr-1"></i> Yes, Finalize',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#007bff',
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $.ajax({
                url: GRAD_ROUTES.finalize,
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': GRAD_ROUTES.csrfToken },
                success: function (res) {
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Finalized!',
                            text: res.message,
                            timer: 3000,
                            showConfirmButton: false,
                        }).then(function () { location.reload(); });
                    } else {
                        showError(res.message);
                    }
                },
                error: function (xhr) {
                    showError(xhr.responseJSON?.message || 'Failed to finalize.');
                }
            });
        });
    });

    // ── Helpers ───────────────────────────────────────────────────────────────

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showError(msg) {
        Swal.fire({ icon: 'error', title: 'Error', text: msg });
    }

    function toastSuccess(msg) {
        Swal.fire({ icon: 'success', title: msg, timer: 1800, showConfirmButton: false, toast: true, position: 'top-end' });
    }

    function toastInfo(msg) {
        Swal.fire({ icon: 'info', title: msg, timer: 3000, showConfirmButton: false, toast: true, position: 'top-end' });
    }
});