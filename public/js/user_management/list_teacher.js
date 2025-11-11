console.log("list teacher");

let dataTable;

$(document).ready(function () {

    // ---------------------------------------------------------------------------
    // Initialize DataTable
    // ---------------------------------------------------------------------------

    dataTable = $('#teacherTable').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        scrollX: true,
        autoWidth: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
            '<"row"<"col-sm-12"tr>>' +
            '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search teachers..."
        },
        order: [[0, 'asc']] // Sort by first name by default
    });

    // ---------------------------------------------------------------------------
    // Filters Configuration
    // ---------------------------------------------------------------------------

    const filters = [
        { selector: '#teacherName', columns: [0, 1], type: 'nameSearch' },
        { selector: '#teacherEmail', column: 2, type: 'search' },
        { selector: '#teacherGender', column: 4, type: 'select' },
        { selector: '#teacherStatus', column: 5, type: 'status' }
    ];

    // ---------------------------------------------------------------------------
    // Apply Filters
    // ---------------------------------------------------------------------------

    filters.forEach(f => {
        if (f.type === 'search') {
            // Simple text search for single column
            $(f.selector).on('keyup', function () {
                const val = $(this).val();
                dataTable
                    .column(f.column)
                    .search(val, false, true)
                    .draw();
            });
        } else if (f.type === 'nameSearch') {
            // Custom search across first and last name columns
            $(f.selector).on('keyup', function () {
                const val = $(this).val().toLowerCase();

                // Custom global search on both first and last name columns
                dataTable.rows().every(function () {
                    const row = this.data();
                    const first = (row[0] || '').toLowerCase();
                    const last = (row[1] || '').toLowerCase();
                    const match = first.includes(val) || last.includes(val);
                    $(this.node()).toggle(match);
                });
            });
        } else if (f.type === 'status') {
            // Status filter (needs to match badge content)
            $(f.selector).on('change', function () {
                const val = $(this).val();
                if (val === '') {
                    dataTable.column(f.column).search('').draw();
                } else {
                    const searchTerm = val === '1' ? 'Active' : 'Inactive';
                    dataTable
                        .column(f.column)
                        .search(searchTerm, false, false)
                        .draw();
                }
            });
        } else {
            // Regular select dropdown filter
            $(f.selector).on('change', function () {
                const val = $(this).val();
                dataTable
                    .column(f.column)
                    .search(val ? '^' + val + '$' : '', true, false)
                    .draw();
            });
        }
    });

    // ---------------------------------------------------------------------------
    // Clear Filters
    // ---------------------------------------------------------------------------

    $('#clearFilters').on('click', function () {
        filters.forEach(f => {
            if (f.type === 'search' || f.type === 'nameSearch') {
                $(f.selector).val('');
            } else {
                $(f.selector).val('').prop('selectedIndex', 0);
            }
        });

        // Clear all DataTable filters and show all rows
        dataTable.search('').columns().search('').draw();
        
        // Reset any custom row visibility
        dataTable.rows().every(function () {
            $(this.node()).show();
        });
    });
});