console.log("Teacher History Page");

$(document).ready(function() {
    
    // Toggle subject accordion
    $(document).on('click', '.subject-header', function(e) {
        e.preventDefault();
        const $subjectItem = $(this).closest('.subject-item');
        $subjectItem.toggleClass('expanded');
    });

    // Filter functions
    function updateRecordsCount() {
        const visibleCards = $('.school-year-card:visible').length;
        
        if (visibleCards === 0) {
            $('#noResultsMessage').show();
        } else {
            $('#noResultsMessage').hide();
        }
    }

    function filterRecords() {
        const searchValue = $('#searchSubject').val().toLowerCase();
        const selectedSchoolYear = $('#schoolYearFilter').val();

        $('.school-year-card').each(function() {
            const $card = $(this);
            const schoolYearId = $card.data('school-year-id').toString();
            const teacherStatus = $card.data('teacher-status');
            
            let show = true;

            // School Year filter
            if (selectedSchoolYear && schoolYearId !== selectedSchoolYear) {
                show = false;
            }



            // Subject search filter (includes adviser assignments)
            if (searchValue) {
                const hasMatchingSubject = $card.find('.subject-item').filter(function() {
                    const subjectName = $(this).data('subject-name');
                    return subjectName && subjectName.includes(searchValue);
                }).length > 0;

                if (!hasMatchingSubject) {
                    show = false;
                }
            }

            if (show) {
                $card.show();
            } else {
                $card.hide();
            }
        });

        updateRecordsCount();
    }

    // Search by subject
    $('#searchSubject').on('keyup', function() {
        filterRecords();
    });

    // Filter by school year
    $('#schoolYearFilter').on('change', function() {
        filterRecords();
    });

    // Clear all filters
    $('#clearFilters').on('click', function() {
        $('#searchSubject').val('');
        $('#schoolYearFilter').val($('#schoolYearFilter option:first').val());
        
        $('.school-year-card').show();
        updateRecordsCount();
    });

    // Toggle Status Button Click
    $(document).on('click', '.btn-toggle-status', function(e) {
        e.preventDefault();
        
        const btn = $(this);
        const teacherId = btn.data('teacher-id');
        const schoolYearId = btn.data('school-year-id');
        const action = btn.data('action');
        const newStatus = action === 'activate' ? 'active' : 'inactive';
        
        const actionText = action === 'activate' ? 'activate' : 'deactivate';
        const confirmText = `Are you sure you want to ${actionText} this teacher for this school year?`;
        
        if (!confirm(confirmText)) {
            return;
        }
        
        // Disable button
        btn.prop('disabled', true);
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');
        
        $.ajax({
            url: TOGGLE_STATUS_URL,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                teacher_id: teacherId,
                school_year_id: schoolYearId,
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    window.location.reload();
                } else {
                    alert(response.message || 'Failed to update teacher status');
                    btn.prop('disabled', false);
                    btn.html(originalHtml);
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'An error occurred while updating teacher status';
                alert(message);
                btn.prop('disabled', false);
                btn.html(originalHtml);
            }
        });
    });

    // Initial filter
    updateRecordsCount();
    filterRecords();
});