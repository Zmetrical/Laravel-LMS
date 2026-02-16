console.log("Teacher List - Tab Version");

let activeDataTable;
let inactiveDataTable;

$(document).ready(function () {
    
    // Column index mapping for Active Teachers
    const COL_ACTIVE = {
        EXPAND: 0,
        FULL_NAME: 1,
        EMAIL: 2,
        CLASSES: 3,
        ACTIONS: 4
    };

    // Column index mapping for Inactive Teachers (no expand, no classes)
    const COL_INACTIVE = {
        FULL_NAME: 0,
        EMAIL: 1,
        ACTIONS: 2
    };

    // Active Teachers DataTable Configuration
    const activeConfig = {
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
            emptyTable: "No teachers found",
            zeroRecords: "No matching teachers found",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ teachers",
            infoEmpty: "Showing 0 to 0 of 0 teachers",
            infoFiltered: "(filtered from _MAX_ total teachers)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        columnDefs: [
            { targets: 0, orderable: false },
            { targets: 4, orderable: false }
        ],
        drawCallback: function() {
            $('.expand-btn').removeClass('expanded');
            $('.classes-detail-row').remove();
        }
    };

    // Inactive Teachers DataTable Configuration
    const inactiveConfig = {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        scrollX: true,
        autoWidth: false,
        order: [[0, 'asc']],
        searching: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6">>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            emptyTable: "No inactive teachers found",
            zeroRecords: "No matching teachers found",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ teachers",
            infoEmpty: "Showing 0 to 0 of 0 teachers",
            infoFiltered: "(filtered from _MAX_ total teachers)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        columnDefs: [
            { targets: 2, orderable: false }
        ]
    };

    // Initialize Active Teachers DataTable
    activeDataTable = $('#activeTeacherTable').DataTable(activeConfig);
    
    // Initialize Inactive Teachers DataTable
    inactiveDataTable = $('#inactiveTeacherTable').DataTable(inactiveConfig);

    // Handle expand/collapse button clicks (Active teachers only)
    $(document).on('click', '.expand-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const teacherId = $(this).data('teacher-id');
        const mainRow = $(this).closest('tr');
        const existingDetailRow = mainRow.next('.classes-detail-row');
        const isExpanded = $(this).hasClass('expanded');
        
        if (isExpanded) {
            $(this).removeClass('expanded');
            existingDetailRow.remove();
        } else {
            $(this).addClass('expanded');
            
            const classesData = mainRow.data('classes');
            
            let classesHtml = '';
            if (classesData && classesData.length > 0) {
                classesData.forEach(function(cls) {
                    classesHtml += `
                        <div class="class-item">
                            <span class="class-name">${cls.class_name}</span>
                        </div>
                    `;
                });
            } else {
                classesHtml = `
                    <div class="no-classes">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>No classes assigned</p>
                    </div>
                `;
            }
            
            const detailRow = $(`
                <tr class="classes-detail-row">
                    <td colspan="5" class="classes-detail-cell">
                        <div class="classes-container">
                            ${classesHtml}
                        </div>
                    </td>
                </tr>
            `);
            
            mainRow.after(detailRow);
        }
    });

    // ========================================
    // ACTIVE TAB FILTERS
    // ========================================
    
    // Search Active Teacher Filter
    $('#searchActiveTeacher').on('keyup', function() {
        const searchValue = this.value.toLowerCase();
        
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'activeTeacherTable') return true;
            if (searchValue === '') return true;
            
            const fullName = data[COL_ACTIVE.FULL_NAME].toLowerCase();
            const email = data[COL_ACTIVE.EMAIL].toLowerCase();
            
            return fullName.includes(searchValue) || email.includes(searchValue);
        });
        
        activeDataTable.draw();
        $.fn.dataTable.ext.search.pop();
    });

    // Active Class Filter (Bootstrap dropdown)
    $('#classSearchInputActive').on('input', function () {
        const value = this.value.toLowerCase().trim();

        $('#classDropdownListActive .class-option').each(function () {
            const text = $(this).text().toLowerCase();
            if (text.indexOf(value) !== -1) {
                $(this).removeAttr('hidden');
            } else {
                $(this).attr('hidden', 'hidden');
            }
        });

        if ($('#classDropdownListActive .class-option:not([hidden])').length > 0) {
            $('#classSearchInputActive').trigger('click');
        }
    });

    $(document).on('click', '#classDropdownListActive .class-option', function (e) {
        e.preventDefault();

        const classId = $(this).data('id');
        const className = $(this).text().trim();

        $('#classSearchInputActive').val(className);
        $('#classSearchInputActive').data('selected-class', classId);

        $('#classDropdownListActive').removeClass('show');
        $('#classDropdownListActive').parent().removeClass('show');

        if (classId === "" || classId === undefined) {
            activeDataTable.draw();
            return;
        }

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'activeTeacherTable') return true;
            
            const row = activeDataTable.row(dataIndex).node();
            const classesData = $(row).data('classes');

            if (!classesData || classesData.length === 0) {
                return false;
            }

            return classesData.some(cls => cls.id == classId);
        });

        activeDataTable.draw();
        $.fn.dataTable.ext.search.pop();
    });

    // Clear Active Filters
    $('#clearActiveFilters').on('click', function() {
        $('#searchActiveTeacher').val('');
        $('#classSearchInputActive').val('').removeData('selected-class');
        $('#classDropdownListActive .class-option').removeAttr('hidden');
        activeDataTable.search('').columns().search('').draw();
    });

    // ========================================
    // INACTIVE TAB FILTERS
    // ========================================
    
    // Search Inactive Teacher Filter
    $('#searchInactiveTeacher').on('keyup', function() {
        const searchValue = this.value.toLowerCase();
        
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (settings.nTable.id !== 'inactiveTeacherTable') return true;
            if (searchValue === '') return true;
            
            const fullName = data[COL_INACTIVE.FULL_NAME].toLowerCase();
            const email = data[COL_INACTIVE.EMAIL].toLowerCase();
            
            return fullName.includes(searchValue) || email.includes(searchValue);
        });
        
        inactiveDataTable.draw();
        $.fn.dataTable.ext.search.pop();
    });

    // Clear Inactive Filters
    $('#clearInactiveFilters').on('click', function() {
        $('#searchInactiveTeacher').val('');
        inactiveDataTable.search('').columns().search('').draw();
    });

    // Close dropdown when clicking outside
    $(document).on('click', function (e) {
        const target = $(e.target);
        if (!target.is('#classSearchInputActive') && target.closest('#classDropdownListActive').length === 0) {
            $('#classDropdownListActive').removeClass('show');
            $('#classDropdownListActive').parent().removeClass('show');
        }
    });

    // Fix header/body alignment on zoom/resize
    $(window).on('resize', function() {
        if (activeDataTable) {
            activeDataTable.columns.adjust().draw();
        }
        if (inactiveDataTable) {
            inactiveDataTable.columns.adjust().draw();
        }
    });
    
    // Adjust tables when tab is shown
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#activeTeachers') {
            activeDataTable.columns.adjust().draw();
        } else if ($(e.target).attr('href') === '#inactiveTeachers') {
            inactiveDataTable.columns.adjust().draw();
        }
    });
});