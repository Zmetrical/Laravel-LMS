console.log("Student List - AJAX Pagination");

$(document).ready(function () {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // ── State ─────────────────────────────────────────────────────────────────
    let currentPage  = 1;
    let searchTimer  = null;

    // Pre-select active semester
    if (typeof DEFAULT_SEMESTER !== 'undefined' && DEFAULT_SEMESTER) {
        $('#semester').val(DEFAULT_SEMESTER);
    }

    // ── Skeleton Loader ───────────────────────────────────────────────────────
    function showSkeleton() {
        let html = '';
        for (let i = 0; i < 8; i++) {
            html += `<tr class="skeleton-row">
                <td><span class="skeleton-line" style="width:90px"></span></td>
                <td><span class="skeleton-line" style="width:80px"></span></td>
                <td><span class="skeleton-line" style="width:130px"></span></td>
                <td><span class="skeleton-line" style="width:45px"></span></td>
                <td><span class="skeleton-line" style="width:35px"></span></td>
                <td><span class="skeleton-line" style="width:100px"></span></td>
                <td><span class="skeleton-line" style="width:60px"></span></td>
                <td class="text-center"><span class="skeleton-line" style="width:55px"></span></td>
                <td class="text-center"><span class="skeleton-line" style="width:60px"></span></td>
            </tr>`;
        }
        $('#studentTableBody').html(html);
        $('#noResultsMessage').hide();
        $('#paginationWrapper').hide();
    }

    // ── Render Table Rows ─────────────────────────────────────────────────────
    function renderRows(data) {
        
        if (!data.length) {
            $('#studentTableBody').empty();
            $('#noResultsMessage').show();
            $('#paginationWrapper').hide();
            return;
        }

        $('#noResultsMessage').hide();

        let html = '';
        data.forEach(function (s) {
            // Semester display
            const semDisplay = s.semester_display
                ? s.semester_display
                : '<span class="text-muted">No enrollment</span>';

            // Student type badge
            const typeBadge = s.student_type === 'regular' ? 'badge-primary' : 'badge-secondary';
            const typeLabel = s.student_type
                ? s.student_type.charAt(0).toUpperCase() + s.student_type.slice(1)
                : '—';

            // Verified badge
            let verifiedBadge = '';
            if (s.verification_status === 'verified') {
                const verifiedOn = s.email_verified_at
                    ? new Date(s.email_verified_at).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })
                    : '';
                verifiedBadge = `<span class="badge badge-primary"
                    title="Guardian email verified: ${s.guardian_email || ''}\nVerified on: ${verifiedOn}">
                    <i class="fas fa-check-circle"></i> <small>Verified</small>
                </span>`;
            } else if (s.verification_status === 'pending') {
                verifiedBadge = `<span class="badge badge-secondary"
                    title="Verification pending for: ${s.guardian_email || ''}">
                    <i class="fas fa-clock"></i> <small>Pending</small>
                </span>`;
            } else {
                verifiedBadge = `<span class="badge badge-secondary" title="No guardian linked">
                    <i class="fas fa-times-circle"></i> <small>None</small>
                </span>`;
            }

            const sectionDisplay = s.enrolled_section_name || s.current_section || '—';
            const fullName = `${s.last_name || ''}, ${s.first_name || ''}`;

            html += `<tr>
                <td>${semDisplay}</td>
                <td>${s.student_number}</td>
                <td>${fullName}</td>
                <td>${s.strand || '—'}</td>
                <td>${s.level || '—'}</td>
                <td>${sectionDisplay}</td>
                <td><span class="badge ${typeBadge}">${typeLabel}</span></td>
                <td class="text-center">${verifiedBadge}</td>
                <td class="text-center">
                    <a href="${API_ROUTES.showStudent}/${s.id}"
                       class="btn btn-sm btn-primary" title="View Profile">
                        <i class="fas fa-user"></i>
                    </a>
                    <a href="${API_ROUTES.editStudent}/${s.id}/edit"
                       class="btn btn-sm btn-primary" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                </td>
            </tr>`;
        });

        $('#studentTableBody').html(html);
    }

    // ── Render Pagination ─────────────────────────────────────────────────────
    function renderPagination(meta) {
        const { current_page, last_page, from, to, total } = meta;

        $('#studentsCount').text(total + ' Student' + (total !== 1 ? 's' : ''));

        if (total === 0 || last_page <= 1) {
            $('#paginationWrapper').hide();
            return;
        }

        $('#paginationInfo').text(`Showing ${from}–${to} of ${total} student${total !== 1 ? 's' : ''}`);

        const range = 2;
        const start = Math.max(1, current_page - range);
        const end   = Math.min(last_page, current_page + range);

        let links = '';

        // Prev
        links += `<li class="page-item ${current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${current_page - 1}">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                  </li>`;

        if (start > 1) {
            links += pageLink(1);
            if (start > 2) links += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
        }

        for (let i = start; i <= end; i++) {
            links += pageLink(i, i === current_page);
        }

        if (end < last_page) {
            if (end < last_page - 1) links += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            links += pageLink(last_page);
        }

        // Next
        links += `<li class="page-item ${current_page === last_page ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${current_page + 1}">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                  </li>`;

        $('#paginationLinks').html(links);
        $('#paginationWrapper').show();
    }

    function pageLink(page, active = false) {
        return `<li class="page-item ${active ? 'active' : ''}">
                  <a class="page-link" href="#" data-page="${page}">${page}</a>
                </li>`;
    }

    // ── Fetch ─────────────────────────────────────────────────────────────────
    function fetchStudents(page) {
        page = page || 1;
        currentPage = page;

        showSkeleton();

        $.ajax({
            url: API_ROUTES.getStudentsAjax,
            method: 'GET',
            data: {
                page:         page,
                per_page:     $('#perPageSelect').val(),
                search:       $('#searchStudent').val().trim(),
                semester_id:  $('#semester').val(),
                student_type: $('#studentType').val(),
                strand_code:  $('#strand').val(),
                level_name:   $('#level').val(),
                section_name: $('#section').val(),
            },
            success: function (res) {
                if (!res.success) {
                    showError('Failed to load students.');
                    return;
                }
                renderRows(res.data);
                renderPagination(res.meta);
            },
            error: function () {
                showError('An error occurred while loading students.');
            }
        });
    }

    function showError(msg) {
        $('#studentTableBody').html(
            `<tr><td colspan="9" class="text-center text-danger py-3">
                <i class="fas fa-exclamation-triangle mr-1"></i>${msg}
             </td></tr>`
        );
        $('#paginationWrapper').hide();
    }

    // ── Section AJAX (strand/level dependent) ─────────────────────────────────
    function loadSections(strandCode, levelName) {
        const params = {};
        if (strandCode) params.strand_code = strandCode;
        if (levelName)  params.level_name  = levelName;

        $.ajax({
            url: API_ROUTES.getSections,
            type: 'GET',
            data: params,
            success: function (response) {
                $('#section').html('<option value="">All Sections</option>');
                const sections = Array.isArray(response) ? response : (response.data || response.sections || []);
                sections.forEach(function (sec) {
                    const name = sec.name || sec.section_name || sec.code;
                    $('#section').append(`<option value="${name}">${name}</option>`);
                });
            },
            error: function () {
                $('#section').html('<option value="">All Sections</option>');
            }
        });
    }

    // ── Events ────────────────────────────────────────────────────────────────

    // Debounced search
    $('#searchStudent').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { fetchStudents(1); }, 400);
    });

    // Simple filter changes
    $('#semester, #studentType').on('change', function () { fetchStudents(1); });

    // Section depends on strand/level
    $('#strand, #level').on('change', function () {
        const strandCode = $('#strand').val();
        const levelName  = $('#level').val();

        $('#section').html('<option value="">All Sections</option>');

        if (strandCode || levelName) {
            loadSections(strandCode, levelName);
        }

        fetchStudents(1);
    });

    $('#section').on('change', function () { fetchStudents(1); });

    // Per-page
    $('#perPageSelect').on('change', function () { fetchStudents(1); });

    // Pagination click
    $(document).on('click', '#paginationLinks .page-link', function (e) {
        e.preventDefault();
        const $li = $(this).closest('.page-item');
        if ($li.hasClass('disabled') || $li.hasClass('active')) return;
        const page = parseInt($(this).data('page'));
        if (page) {
            fetchStudents(page);
            $('html, body').animate({ scrollTop: $('#studentTable').offset().top - 80 }, 200);
        }
    });

    // Clear filters
    $('#clearFilters').on('click', function () {
        $('#searchStudent').val('');
        $('#semester').val(typeof DEFAULT_SEMESTER !== 'undefined' ? DEFAULT_SEMESTER : '');
        $('#studentType').val('');
        $('#strand').val('');
        $('#level').val('');
        $('#section').html('<option value="">All Sections</option>');
        fetchStudents(1);
    });

    // ── Initial Load ──────────────────────────────────────────────────────────
    fetchStudents(1);
});