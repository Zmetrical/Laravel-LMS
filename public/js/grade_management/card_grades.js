console.log("Grade Cards List - AJAX Pagination");

$(document).ready(function () {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    // ── Select2 ──────────────────────────────────────────────────────────────
    $('#sectionFilter').select2({
        theme: 'bootstrap4',
        placeholder: 'All Sections',
        allowClear: true,
        width: '100%'
    });

    // ── State ─────────────────────────────────────────────────────────────────
    let currentPage  = 1;
    let searchTimer  = null;
    const PER_PAGE   = 15;

    // Pre-select active semester if available
    if (typeof DEFAULT_SEMESTER !== 'undefined' && DEFAULT_SEMESTER) {
        $('#semesterFilter').val(DEFAULT_SEMESTER);
    }

    // ── Skeleton Loader ───────────────────────────────────────────────────────
    function showSkeleton() {
        let html = '';
        for (let i = 0; i < 5; i++) {
            html += `
                <div class="skeleton-card">
                    <div class="skeleton-line medium"></div>
                    <div class="skeleton-line short"></div>
                    <div class="skeleton-line long" style="margin-top:6px"></div>
                </div>`;
        }
        $('#gradeCardsContainer').html(html);
        $('#noResultsMessage').hide();
        $('#paginationWrapper').hide();
    }

    // ── Render Cards ──────────────────────────────────────────────────────────
    function renderCards(data) {
        if (!data.length) {
            $('#gradeCardsContainer').empty();
            $('#noResultsMessage').show();
            $('#paginationWrapper').hide();
            return;
        }

        $('#noResultsMessage').hide();

        let html = '';
        data.forEach(function (card) {
            const lastName  = (card.last_name  || '').toUpperCase();
            const firstName = (card.first_name || '').toUpperCase();
            const typeBadge = card.student_type === 'regular' ? 'badge-primary' : 'badge-secondary';
            const sectionInfo = card.section_name
                ? `${card.section_name} <small class="text-muted">(${card.strand_code || ''} - ${card.level_name || ''})</small>`
                : 'N/A';

            const viewUrl = `${CARD_VIEW_BASE}/${card.student_number}/${card.semester_id}`;

            html += `
            <div class="grade-card-list">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <div class="d-flex align-items-center mb-2">
                                <span class="student-id-badge">${card.student_number}</span>
                                <h5 class="student-name-display">${lastName}, ${firstName}</h5>
                            </div>
                            <div class="info-inline">
                                <div class="info-item-inline">
                                    <i class="fas fa-users"></i>
                                    <span class="info-value-inline">${sectionInfo}</span>
                                </div>
                                <div class="info-item-inline">
                                    <i class="fas fa-user-tag"></i>
                                    <span class="badge ${typeBadge}">${(card.student_type || '').toUpperCase()}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center justify-content-end">
                                <div class="subjects-count-compact">
                                    <span class="semester-label">${card.semester_display || ''}</span>
                                </div>
                                <a href="${viewUrl}" class="btn btn-primary view-card-btn">
                                    <i class="fas fa-file-alt"></i> View Card
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>`;
        });

        $('#gradeCardsContainer').html(html);
    }

    // ── Render Pagination ─────────────────────────────────────────────────────
    function renderPagination(meta) {
        const { current_page, last_page, from, to, total } = meta;

        // Info text
        if (total === 0) {
            $('#paginationWrapper').hide();
            return;
        }

        $('#paginationInfo').text(`Showing ${from}–${to} of ${total} record${total !== 1 ? 's' : ''}`);

        // Build page links (show max 5 around current)
        let links = '';

        // Prev
        links += `<li class="page-item ${current_page === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${current_page - 1}">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                  </li>`;

        const range  = 2; // pages on each side of current
        const start  = Math.max(1, current_page - range);
        const end    = Math.min(last_page, current_page + range);

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
    function fetchCards(page) {
        page = page || 1;
        currentPage = page;

        showSkeleton();

        $.ajax({
            url: API_ROUTES.getCardsAjax,
            method: 'GET',
            data: {
                page:         page,
                per_page:     PER_PAGE,
                search:       $('#searchStudent').val().trim(),
                semester_id:  $('#semesterFilter').val(),
                section_code: $('#sectionFilter').val() || '',
            },
            success: function (res) {
                if (!res.success) {
                    showError('Failed to load grade cards.');
                    return;
                }

                const meta = res.meta;
                $('#cardsCount').text(meta.total + ' Record' + (meta.total !== 1 ? 's' : ''));

                renderCards(res.data);
                renderPagination(meta);
            },
            error: function () {
                showError('An error occurred while loading grade cards.');
            }
        });
    }

    function showError(msg) {
        $('#gradeCardsContainer').html(
            `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-1"></i>${msg}</div>`
        );
        $('#paginationWrapper').hide();
    }

    // ── Events ────────────────────────────────────────────────────────────────

    // Debounced search
    $('#searchStudent').on('keyup', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { fetchCards(1); }, 400);
    });

    // Semester & section filters
    $('#semesterFilter').on('change', function () { fetchCards(1); });
    $('#sectionFilter').on('change',  function () { fetchCards(1); });

    // Pagination click
    $(document).on('click', '#paginationLinks .page-link', function (e) {
        e.preventDefault();
        const $li = $(this).closest('.page-item');
        if ($li.hasClass('disabled') || $li.hasClass('active')) return;
        const page = parseInt($(this).data('page'));
        if (page) {
            fetchCards(page);
            $('html, body').animate({ scrollTop: $('#gradeCardsContainer').offset().top - 80 }, 200);
        }
    });

    // Clear filters
    $('#clearFilters').on('click', function () {
        $('#searchStudent').val('');
        $('#semesterFilter').val(typeof DEFAULT_SEMESTER !== 'undefined' ? DEFAULT_SEMESTER : '');
        $('#sectionFilter').val(null).trigger('change'); // triggers select2 clear + fetch
    });

    // ── Initial Load ──────────────────────────────────────────────────────────
    fetchCards(1);
});