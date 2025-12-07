console.log("Grades List");

let dataTable;

$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Initialize Select2 for class and section filters
    $('#classFilter').select2({
        theme: 'bootstrap4',
        placeholder: 'All Classes',
        allowClear: true,
        width: '100%'
    });

    $('#sectionFilter').select2({
        theme: 'bootstrap4',
        placeholder: 'All Sections',
        allowClear: true,
        width: '100%'
    });

    function updateGradesCount() {
        if (dataTable && dataTable.rows) {
            const count = dataTable.rows({ filter: 'applied' }).count();
            $('#gradesCount').text(count + ' Record' + (count !== 1 ? 's' : ''));
        }
    }

    dataTable = $('#gradesTable').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        scrollX: true,
        autoWidth: false,
        order: [[4, 'desc'], [1, 'asc']], // Sort by semester (desc), then student name (asc)
        searching: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6">>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            emptyTable: "No grade records found",
            zeroRecords: "No matching grade records found",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ records",
            infoEmpty: "Showing 0 to 0 of 0 records",
            infoFiltered: "(filtered from _MAX_ total records)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        columnDefs: [
            { targets: [5, 6], orderable: true },
            { targets: 7, orderable: false }
        ],
        drawCallback: function() {
            updateGradesCount();
        }
    });

    const COL = {
        STUDENT_NO: 0,
        STUDENT_NAME: 1,
        CLASS: 2,
        SECTION: 3,
        SEMESTER: 4,
        FINAL: 5,
        REMARKS: 6,
        ACTIONS: 7
    };

    // Search by student number or name
    $('#searchStudent').on('keyup', function() {
        const searchValue = this.value;
        
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (searchValue === '') return true;
            
            const studentNumber = data[COL.STUDENT_NO].toLowerCase();
            const studentName = data[COL.STUDENT_NAME].toLowerCase();
            const search = searchValue.toLowerCase();
            
            return studentNumber.includes(search) || studentName.includes(search);
        });
        
        dataTable.draw();
        $.fn.dataTable.ext.search.pop();
    });

    // Filter by semester
    $('#semester').on('change', function() {
        const selectedSemesterId = $(this).val();
        
        if (!selectedSemesterId) {
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(f => !f.name || f.name !== 'semesterFilter');
            dataTable.draw();
            return;
        }
        
        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(f => !f.name || f.name !== 'semesterFilter');
        
        const semesterFilter = function(settings, data, dataIndex) {
            const row = dataTable.row(dataIndex).node();
            const rowSemesterId = $(row).find('td').eq(COL.SEMESTER).data('semester-id');
            
            return String(rowSemesterId) === String(selectedSemesterId);
        };
        semesterFilter.name = 'semesterFilter';
        
        $.fn.dataTable.ext.search.push(semesterFilter);
        dataTable.draw();
    });

    // Filter by class with Select2
    $('#classFilter').on('change', function() {
        const selectedClassCode = $(this).val();
        
        if (!selectedClassCode) {
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(f => !f.name || f.name !== 'classFilter');
            dataTable.draw();
            return;
        }
        
        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(f => !f.name || f.name !== 'classFilter');
        
        const classFilter = function(settings, data, dataIndex) {
            const row = dataTable.row(dataIndex).node();
            const rowClassCode = $(row).find('td').eq(COL.CLASS).data('class-code');
            
            return String(rowClassCode) === String(selectedClassCode);
        };
        classFilter.name = 'classFilter';
        
        $.fn.dataTable.ext.search.push(classFilter);
        dataTable.draw();
    });

    // Filter by section
    $('#sectionFilter').on('change', function() {
        const selectedSectionCode = $(this).val();
        
        if (!selectedSectionCode) {
            $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(f => !f.name || f.name !== 'sectionFilter');
            dataTable.draw();
            return;
        }
        
        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(f => !f.name || f.name !== 'sectionFilter');
        
        const sectionFilter = function(settings, data, dataIndex) {
            const row = dataTable.row(dataIndex).node();
            const rowSectionCode = $(row).find('td').eq(COL.SECTION).data('section-code');
            
            return String(rowSectionCode) === String(selectedSectionCode);
        };
        sectionFilter.name = 'sectionFilter';
        
        $.fn.dataTable.ext.search.push(sectionFilter);
        dataTable.draw();
    });

    // Filter by status/remarks
    $('#statusFilter').on('change', function() {
        const val = $(this).val();
        if (val) {
            dataTable.column(COL.REMARKS).search(val, false, false).draw();
        } else {
            dataTable.column(COL.REMARKS).search('').draw();
        }
    });

    // Clear all filters
    $('#clearFilters').on('click', function() {
        $('#searchStudent').val('');
        $('#semester').val($('#semester option[selected]').val() || '');
        $('#classFilter').val(null).trigger('change');
        $('#sectionFilter').val(null).trigger('change');
        $('#statusFilter').val('');

        $.fn.dataTable.ext.search = [];
        dataTable.search('').columns().search('').draw();
    });

    // View grade details
    $(document).on('click', '.view-details-btn', function() {
        const gradeId = $(this).data('grade-id');
        loadGradeDetails(gradeId);
    });

    function loadGradeDetails(gradeId) {
        const url = API_ROUTES.getGradeDetails.replace(':id', gradeId);
        
        $.ajax({
            url: url,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    displayGradeDetails(response.data);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to load grade details'
                    });
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to load grade details';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
            }
        });
    }

    function displayGradeDetails(grade) {
        // Student Info
        $('#detailStudentNumber').text(grade.student_number);
        $('#detailStudentName').text(grade.full_name);
        
        const sectionInfo = grade.section_name ? 
            `${grade.section_name} (${grade.strand_code || ''} - ${grade.level_name || ''})` : 
            'N/A';
        $('#detailSection').text(sectionInfo);

        // Class and Semester Info
        $('#detailClass').text(grade.class_name);
        $('#detailSemester').text(grade.semester_display);
        $('#detailComputedBy').text(grade.computed_by_name || 'N/A');
        
        if (grade.computed_at) {
            const computedDate = new Date(grade.computed_at);
            $('#detailComputedAt').text(computedDate.toLocaleString());
        } else {
            $('#detailComputedAt').text('N/A');
        }

        // Grades
        $('#detailQ1').text(grade.q1_grade || 'N/A');
        $('#detailQ2').text(grade.q2_grade || 'N/A');
        $('#detailFinalGrade').text(grade.final_grade);
        
        const remarksClass = getRemarksClass(grade.remarks);
        $('#detailRemarks').html(`<span class="badge ${remarksClass}">${grade.remarks}</span>`);

        $('#gradeDetailsModal').modal('show');
    }

    function getRemarksClass(remarks) {
        const remarksMap = {
            'PASSED': 'badge-passed',
            'FAILED': 'badge-failed',
            'INC': 'badge-inc',
            'DRP': 'badge-drp',
            'W': 'badge-w'
        };
        return remarksMap[remarks] || 'badge-secondary';
    }

    updateGradesCount();
});