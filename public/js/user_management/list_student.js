console.log("list user");

let dataTable;

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
        { selector: '#studentName', columns: [1, 2], type: 'nameSearch' },
        { selector: '#strand', column: 3, type: 'select' },
        { selector: '#level', column: 4, type: 'select' },
        { selector: '#section', column: 5, type: 'select' }
    ];


    filters.forEach(f => {
        if (f.type === 'search') {
            $(f.selector).on('keyup', function () {
                const val = $(this).val();
                dataTable
                    .column(f.column)
                    .search(val, false, true)
                    .draw();
            });
        } else if (f.type === 'nameSearch') {
            $(f.selector).on('keyup', function () {
                const val = $(this).val().toLowerCase();

                // Custom global search on both first and last name columns
                dataTable.rows().every(function () {
                    const row = this.data();
                    const first = (row[1] || '').toLowerCase();
                    const last = (row[2] || '').toLowerCase();
                    const match = first.includes(val) || last.includes(val);
                    $(this.node()).toggle(match);
                });
            });
        } else {
            $(f.selector).on('change', function () {
                const val = $(this).val();
                dataTable
                    .column(f.column)
                    .search(val ? '^' + val + '$' : '', true, false)
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

        const params = {};
        if (strandId) params.strand_id = strandId;
        if (levelId) params.level_id = levelId;

        if (Object.keys(params).length > 0) {
            $.ajax({
                url: API_ROUTES.getSections,
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

    // === Clear FILTER ===
    $('#clearFilters').on('click', function () {
        filters.forEach(f => {
            if (f.type === 'search') {
                $(f.selector).val('');
            } else {
                $(f.selector).val('').prop('selectedIndex', 0);
            }
        });

        dataTable.search('').columns().search('').draw();
    });
});
