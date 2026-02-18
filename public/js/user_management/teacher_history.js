console.log("Teacher History");

$(document).ready(function () {

    // ── Subject accordion ─────────────────────────────────────────────────────
    $(document).on('click', '.subject-header', function () {
        $(this).closest('.subject-item').toggleClass('expanded');
    });

    // ── Filtering ─────────────────────────────────────────────────────────────
    function applyFilters() {
        const search     = $('#searchSubject').val().toLowerCase().trim();
        const schoolYear = $('#schoolYearFilter').val();
        const status     = $('#statusFilter').val();
        let visible = 0;

        $('.school-year-card').each(function () {
            const $card      = $(this);
            const syId       = $card.data('school-year-id').toString();
            const trailStatus = $card.data('trail-status'); // 'active' | 'inactive' | 'none'
            let show         = true;

            if (schoolYear && syId !== schoolYear) show = false;

            if (show && status) {
                if (status === 'none') {
                    if (trailStatus !== 'none') show = false;
                } else {
                    if (trailStatus !== status) show = false;
                }
            }

            if (show && search) {
                const hasMatch = $card.find('.subject-item').filter(function () {
                    return ($(this).data('subject-name') || '').includes(search);
                }).length > 0;

                if (!hasMatch) show = false;
            }

            $card.toggle(show);
            if (show) visible++;
        });

        $('#noResultsMessage').toggle(visible === 0);
    }

    $('#searchSubject').on('keyup', applyFilters);
    $('#schoolYearFilter').on('change', applyFilters);
    $('#statusFilter').on('change', applyFilters);

    $('#clearFilters').on('click', function () {
        $('#searchSubject').val('');
        $('#schoolYearFilter').val('');
        $('#statusFilter').val('');
        $('.school-year-card').show();
        $('#noResultsMessage').hide();
    });

    // ── Activate / Deactivate teacher ─────────────────────────────────────────

    $(document).on('click', '.btn-toggle-status', function (e) {
        e.preventDefault();

        const $btn         = $(this);
        const teacherId    = $btn.data('teacher-id');
        const schoolYearId = $btn.data('school-year-id');
        const action       = $btn.data('action');
        const newStatus    = action === 'activate' ? 'active' : 'inactive';
        const isActivating = action === 'activate';

        Swal.fire({
            title: isActivating ? 'Reactivate Teacher?' : 'Deactivate Teacher?',
            text: isActivating
                ? 'This teacher will be marked as active and can be assigned to classes.'
                : 'This teacher will be marked as inactive.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: isActivating ? 'Yes, reactivate' : 'Yes, deactivate',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#6c757d',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
        }).then(function (result) {
            if (!result.isConfirmed) return;

            $btn.prop('disabled', true);
            const originalHtml = $btn.html();
            $btn.html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: TOGGLE_STATUS_URL,
                method: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    teacher_id: teacherId,
                    school_year_id: schoolYearId,
                    status: newStatus,
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Done!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonColor: '#6c757d',
                        }).then(function () {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message || 'Failed to update teacher status.',
                            icon: 'error',
                            confirmButtonColor: '#6c757d',
                        });
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'An error occurred.';
                    Swal.fire({
                        title: 'Error',
                        text: msg,
                        icon: 'error',
                        confirmButtonColor: '#6c757d',
                    });
                    $btn.prop('disabled', false).html(originalHtml);
                },
            });
        });
    });

    // Run on load
    applyFilters();
});