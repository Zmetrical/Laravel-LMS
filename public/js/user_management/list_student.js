console.log("list user");

let dataTable;
let currentFirstNameFilter = '';
let currentLastNameFilter = '';

$(document).ready(function () {

    // ---------------------------------------------------------------------------
    // Filters
    // ---------------------------------------------------------------------------

    dataTable = $('#studentTable').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        scrollX: true,
        autoWidth: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
            '<"row"<"col-sm-12"tr>>' +
            '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search students..."
        }
    });

    // === Header FILTERS ===
    const filters = [
        { selector: '#studentNumber', column: 0, type: 'search' },
        { selector: '#strand', column: 2, type: 'select' },
        { selector: '#level', column: 3, type: 'select' },
        { selector: '#section', column: 4, type: 'select' }
    ];

    filters.forEach(f => {
        if (f.type === 'search') {
            // Text search filter
            $(f.selector).on('keyup', function () {
                const val = $(this).val();
                dataTable
                    .column(f.column)
                    .search(val, false, true) // Regular search (not regex exact match)
                    .draw();
            });
        } else {
            // Select dropdown filter
            $(f.selector).on('change', function () {
                const val = $(this).val();
                dataTable
                    .column(f.column)
                    .search(val ? '^' + val + '$' : '', true, false) // Exact match with regex
                    .draw();
            });
        }
    });

    const strandSelect = $('#strand');
    const levelSelect = $('#level');
    const sectionSelect = $('#section');

    // === Update Header OPTIONS ===
    function render_Sections() {
        const strandId = strandSelect.val();
        const levelId = levelSelect.val();

        // Reset section dropdown
        sectionSelect.html('<option hidden disabled selected>Select Section</option>');

        // Build query dynamically
        const params = {};
        if (strandId) params.strand_id = strandId;
        if (levelId) params.level_id = levelId;

        if (Object.keys(params).length > 0) {
            $.ajax({
                url: '/procedure/get_sections',
                method: 'GET',
                data: params,
                success: function (data) {
                    if (data.length > 0) {
                        data.forEach(section => {
                            sectionSelect.append(
                                $('<option>', { value: section.id, text: section.name })
                            );
                        });
                    } else {
                        sectionSelect.append(
                            $('<option>', { text: 'No sections available', disabled: true })
                        );
                    }
                },
                error: function () {
                    sectionSelect.append(
                        $('<option>', { text: 'Error loading sections', disabled: true })
                    );
                }
            });
        } else {
            sectionSelect.append(
                $('<option>', { text: 'Please select strand and level first', disabled: true })
            );
        }
    }

    strandSelect.on('change', render_Sections);
    levelSelect.on('change', render_Sections);


    // === ALPAHABET FILTERS ===
    initialize_AlphabetFilters();

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        const firstName = data[1] || ''; // Column index 1 is first name
        const lastName = data[2] || '';  // Column index 2 is last name

        // Check first name filter
        if (currentFirstNameFilter && !firstName.toUpperCase().startsWith(currentFirstNameFilter)) {
            return false;
        }

        // Check last name filter
        if (currentLastNameFilter && !lastName.toUpperCase().startsWith(currentLastNameFilter)) {
            return false;
        }

        return true;
    });

    // === Clear FILTER ===
    $('#clearFilters').on('click', function () {

        // Clear header filters
        filters.forEach(f => {
            if (f.type === 'search') {
                $(f.selector).val('');
            } else {
                $(f.selector).val('').prop('selectedIndex', 0);
            }
        });

        // Clear alphabet filters
        currentFirstNameFilter = '';
        currentLastNameFilter = '';


        reset_AlphabetFilters();
        dataTable.search('').columns().search('').draw();
    });
});

// Function to reset alphabet filters to default state
function reset_AlphabetFilters() {
    // Reset first name filter buttons
    const firstNameDiv = document.getElementById('firstNameFilter');
    if (firstNameDiv) {
        firstNameDiv.querySelectorAll('button').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
        });
        // Set "All" button as active
        const firstAllBtn = firstNameDiv.querySelector('button[data-letter=""]');
        if (firstAllBtn) {
            firstAllBtn.classList.remove('btn-outline-secondary');
            firstAllBtn.classList.add('btn-primary');
        }
    }

    // Reset last name filter buttons
    const lastNameDiv = document.getElementById('lastNameFilter');
    if (lastNameDiv) {
        lastNameDiv.querySelectorAll('button').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
        });
        // Set "All" button as active
        const lastAllBtn = lastNameDiv.querySelector('button[data-letter=""]');
        if (lastAllBtn) {
            lastAllBtn.classList.remove('btn-outline-secondary');
            lastAllBtn.classList.add('btn-primary');
        }
    }
}
// Create alphabet filter buttons
function initialize_AlphabetFilters() {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');

    // First name filter
    const firstNameDiv = document.getElementById('firstNameFilter');
    if (firstNameDiv) {
        let firstNameHTML = '<button type="button" class="filter-alpha btn btn-sm btn-primary" data-letter="">All</button>';
        alphabet.forEach(letter => {
            firstNameHTML += `<button type="button" class="filter-alpha btn btn-sm btn-outline-secondary" data-letter="${letter}">${letter}</button>`;
        });
        firstNameDiv.innerHTML = firstNameHTML;

        // Add event listeners for first name
        firstNameDiv.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!dataTable) return;

                // Update button styles
                firstNameDiv.querySelectorAll('button').forEach(b => {
                    b.classList.remove('btn-primary');
                    b.classList.add('btn-outline-secondary');
                });
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-primary');

                // Update filter and redraw
                currentFirstNameFilter = this.dataset.letter;
                dataTable.draw();
            });
        });
    }

    // Last name filter
    const lastNameDiv = document.getElementById('lastNameFilter');
    if (lastNameDiv) {
        let lastNameHTML = '<button type="button" class="filter-alpha btn btn-sm btn-primary" data-letter="">All</button>';
        alphabet.forEach(letter => {
            lastNameHTML += `<button type="button" class="filter-alpha btn btn-sm btn-outline-secondary" data-letter="${letter}">${letter}</button>`;
        });
        lastNameDiv.innerHTML = lastNameHTML;

        // Add event listeners for last name
        lastNameDiv.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', function () {
                if (!dataTable) return;

                // Update button styles
                lastNameDiv.querySelectorAll('button').forEach(b => {
                    b.classList.remove('btn-primary');
                    b.classList.add('btn-outline-secondary');
                });
                this.classList.remove('btn-outline-secondary');
                this.classList.add('btn-primary');

                // Update filter and redraw
                currentLastNameFilter = this.dataset.letter;
                dataTable.draw();
            });
        });
    }
}