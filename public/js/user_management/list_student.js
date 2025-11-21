console.log("Student List Initialized");

let dataTable;

$(document).ready(function () {
    // Update student count badge
    function updateStudentCount() {
        if (dataTable && dataTable.rows) {
            const count = dataTable.rows({ filter: 'applied' }).count();
            $('#studentsCount').text(count + ' Student' + (count !== 1 ? 's' : ''));
        }
    }

    // Initialize DataTable
    dataTable = $('#studentTable').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        scrollX: true,
        autoWidth: false,
        order: [[1, 'asc']], // Sort by Full Name by default
        searching: true, // Keep search functionality enabled
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6">>' + // Removed 'f' to hide search box
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            emptyTable: "No students found",
            zeroRecords: "No matching students found",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ students",
            infoEmpty: "Showing 0 to 0 of 0 students",
            infoFiltered: "(filtered from _MAX_ total students)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        columnDefs: [
            { targets: 6, orderable: false } // Disable sorting on Actions column
        ],
        drawCallback: function() {
            updateStudentCount();
        }
    });

    // Column index mapping
    const COL = {
        STUDENT_NO: 0,
        FULL_NAME: 1,
        STRAND: 2,
        LEVEL: 3,
        SECTION: 4,
        TYPE: 5
    };

    // Student Number Filter
    $('#studentNumber').on('keyup', function() {
        dataTable.column(COL.STUDENT_NO).search(this.value).draw();
    });

    // Student Name Filter
    $('#studentName').on('keyup', function() {
        dataTable.column(COL.FULL_NAME).search(this.value).draw();
    });

    // Strand Filter (exact match)
    $('#strand').on('change', function() {
        const val = $(this).val();
        dataTable.column(COL.STRAND).search(val ? '^' + escapeRegex(val) + '$' : '', true, false).draw();
    });

    // Level Filter (exact match)
    $('#level').on('change', function() {
        const val = $(this).val();
        dataTable.column(COL.LEVEL).search(val ? '^' + escapeRegex(val) + '$' : '', true, false).draw();
    });

    // Section Filter (exact match)
    $('#section').on('change', function() {
        const val = $(this).val();
        dataTable.column(COL.SECTION).search(val ? '^' + escapeRegex(val) + '$' : '', true, false).draw();
    });

    // Escape special regex characters
    function escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Dynamic Section Loading - works with strand OR level OR both
    $('#strand, #level').on('change', function() {
        const strandCode = $('#strand').val();
        const levelName = $('#level').val();

        // Reset section dropdown
        $('#section').html('<option value="">All Sections</option>');
        
        // Clear section filter when either strand or level changes
        dataTable.column(COL.SECTION).search('').draw();

        // Load sections if EITHER strand OR level is selected (or both)
        if (strandCode || levelName) {
            loadSections(strandCode, levelName);
        }
    });

    function loadSections(strandCode, levelName) {
        const params = {};
        
        // Only add parameters that have values
        if (strandCode) params.strand_code = strandCode;
        if (levelName) params.level_name = levelName;
        
        console.log('Loading sections with:', params);
        
        $.ajax({
            url: API_ROUTES.getSections,
            type: 'GET',
            data: params,
            success: function(response) {
                console.log('Sections response:', response);
                
                $('#section').html('<option value="">All Sections</option>');
                
                // Handle different response formats
                const sections = Array.isArray(response) ? response : (response.data || response.sections || []);
                
                if (sections && sections.length > 0) {
                    sections.forEach(function(section) {
                        const sectionName = section.name || section.section_name || section.code;
                        const sectionValue = section.name || section.section_name || section.code;
                        $('#section').append(`<option value="${sectionValue}">${sectionName}</option>`);
                    });
                    console.log(`Loaded ${sections.length} sections`);
                } else {
                    console.log('No sections found for the selected filters');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading sections:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    error: error,
                    response: xhr.responseText
                });
                $('#section').html('<option value="">All Sections</option>');
            }
        });
    }

    // Clear All Filters
    $('#clearFilters').on('click', function() {
        // Clear input fields
        $('#studentNumber').val('');
        $('#studentName').val('');
        
        // Reset select dropdowns
        $('#strand').val('');
        $('#level').val('');
        $('#section').html('<option value="">All Sections</option>');

        // Clear all DataTable searches and redraw
        dataTable.search('').columns().search('').draw();
    });

    // Initial count
    updateStudentCount();
});