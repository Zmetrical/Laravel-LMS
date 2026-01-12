console.log("Grade Cards List");

$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize Select2
    $('#sectionFilter').select2({
        theme: 'bootstrap4',
        placeholder: 'All Sections',
        allowClear: true,
        width: '100%'
    });

    function updateCardsCount() {
        const visibleCards = $('.grade-card-item:visible').length;
        $('#cardsCount').text(visibleCards + ' Record' + (visibleCards !== 1 ? 's' : ''));
        
        if (visibleCards === 0) {
            $('#noResultsMessage').show();
        } else {
            $('#noResultsMessage').hide();
        }
    }

    function filterCards() {
        const searchValue = $('#searchStudent').val().toLowerCase();
        const selectedSemester = $('#semesterFilter').val();
        const selectedSection = $('#sectionFilter').val();

        $('.grade-card-item').each(function() {
            const $card = $(this);
            const studentNumber = $card.data('student-number').toString().toLowerCase();
            const studentName = $card.data('student-name');
            const sectionCode = $card.data('section-code') ? $card.data('section-code').toString() : '';
            const semesterId = $card.data('semester-id') ? $card.data('semester-id').toString() : '';

            let show = true;

            // Search filter
            if (searchValue && !studentNumber.includes(searchValue) && !studentName.includes(searchValue)) {
                show = false;
            }

            // Semester filter
            if (selectedSemester && semesterId !== selectedSemester) {
                show = false;
            }

            // Section filter
            if (selectedSection && sectionCode !== selectedSection) {
                show = false;
            }

            if (show) {
                $card.show();
            } else {
                $card.hide();
            }
        });

        updateCardsCount();
    }

    // Search by student number or name
    $('#searchStudent').on('keyup', function() {
        filterCards();
    });

    // Filter by semester
    $('#semesterFilter').on('change', function() {
        filterCards();
    });

    // Filter by section
    $('#sectionFilter').on('change', function() {
        filterCards();
    });

    // Clear all filters
    $('#clearFilters').on('click', function() {
        $('#searchStudent').val('');
        $('#semesterFilter').val($('#semesterFilter option[selected]').val() || '');
        $('#sectionFilter').val(null).trigger('change');
        
        $('.grade-card-item').show();
        updateCardsCount();
    });

    // Initial count
    updateCardsCount();
});