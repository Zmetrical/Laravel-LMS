console.log("Student List");

let dataTable;

$(document).ready(function () {
    function updateStudentCount() {
        if (dataTable && dataTable.rows) {
            const count = dataTable.rows({ filter: 'applied' }).count();
            $('#studentsCount').text(count + ' Student' + (count !== 1 ? 's' : ''));
        }
    }

    dataTable = $('#studentTable').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        scrollX: true,
        autoWidth: false,
        order: [[1, 'asc']],
        searching: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6">>' +
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
            { targets: 7, orderable: false }
        ],
        drawCallback: function() {
            updateStudentCount();
        }
    });

    const COL = {
        STUDENT_NO: 0,
        FULL_NAME: 1,
        STRAND: 2,
        LEVEL: 3,
        SECTION: 4,
        TYPE: 5,
        SEMESTER: 6
    };

    // Search by student number or name
    $('#searchStudent').on('keyup', function() {
        const searchValue = this.value;
        
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (searchValue === '') return true;
            
            const studentNumber = data[COL.STUDENT_NO].toLowerCase();
            const fullName = data[COL.FULL_NAME].toLowerCase();
            const search = searchValue.toLowerCase();
            
            return studentNumber.includes(search) || fullName.includes(search);
        });
        
        dataTable.draw();
        $.fn.dataTable.ext.search.pop();
    });

    // Filter by semester - Shows students with ANY enrollment in selected semester
    $('#semester').on('change', function() {
        const selectedSemesterId = $(this).val();
        
        if (!selectedSemesterId) {
            // Show all students
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(f => !f.name || f.name !== 'semesterFilter');
            dataTable.draw();
            return;
        }
        
        // Remove previous semester filter if exists
        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(f => !f.name || f.name !== 'semesterFilter');
        
        // Add new semester filter
        const semesterFilter = function(settings, data, dataIndex) {
            const row = dataTable.row(dataIndex).node();
            const rowSemesterId = $(row).find('td').eq(COL.SEMESTER).data('semester-id');
            
            // Show row if semester matches (includes enrolled, completed, dropped)
            return String(rowSemesterId) === String(selectedSemesterId);
        };
        semesterFilter.name = 'semesterFilter';
        
        $.fn.dataTable.ext.search.push(semesterFilter);
        dataTable.draw();
    });

    // Filter by student type
    $('#studentType').on('change', function() {
        const val = $(this).val();
        dataTable.column(COL.TYPE).search(val ? '^' + escapeRegex(val) + '$' : '', true, false).draw();
    });

    // Filter by strand
    $('#strand').on('change', function() {
        const val = $(this).val();
        dataTable.column(COL.STRAND).search(val ? '^' + escapeRegex(val) + '$' : '', true, false).draw();
    });

    // Filter by level
    $('#level').on('change', function() {
        const val = $(this).val();
        dataTable.column(COL.LEVEL).search(val ? '^' + escapeRegex(val) + '$' : '', true, false).draw();
    });

    // Filter by section
    $('#section').on('change', function() {
        const val = $(this).val();
        dataTable.column(COL.SECTION).search(val ? '^' + escapeRegex(val) + '$' : '', true, false).draw();
    });

    function escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Load sections based on strand/level selection
    $('#strand, #level').on('change', function() {
        const strandCode = $('#strand').val();
        const levelName = $('#level').val();

        $('#section').html('<option value="">All Sections</option>');
        dataTable.column(COL.SECTION).search('').draw();

        if (strandCode || levelName) {
            loadSections(strandCode, levelName);
        }
    });

    function loadSections(strandCode, levelName) {
        const params = {};
        
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

    // Clear all filters
    $('#clearFilters').on('click', function() {
        $('#searchStudent').val('');
        $('#semester').val('');
        $('#studentType').val('');
        $('#strand').val('');
        $('#level').val('');
        $('#section').html('<option value="">All Sections</option>');

        // Clear custom search filters
        $.fn.dataTable.ext.search = [];
        
        dataTable.search('').columns().search('').draw();
    });

    updateStudentCount();
});