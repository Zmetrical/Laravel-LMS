console.log("Teacher List");

let dataTable;

$(document).ready(function () {
    // NOTE: Select2 initialization removed for class filter (we're using Bootstrap dropdown)
    // Update teacher count badge
    function updateTeacherCount() {
        if (dataTable && dataTable.rows) {
            const count = dataTable.rows({ filter: 'applied' }).count();
            $('#teachersCount').text(count + ' Teacher' + (count !== 1 ? 's' : ''));
        }
    }

    // Remove detail rows from DataTable processing before initialization
    $('#teacherTable tbody tr.classes-detail-row').remove();
    
    // Initialize DataTable
    dataTable = $('#teacherTable').DataTable({
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        scrollX: true,
        autoWidth: false,
        order: [[1, 'asc']], // Sort by Full Name (column 1, since column 0 is expand button)
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
            { targets: 0, orderable: false }, // Expand button column
            { targets: 4, orderable: false }  // Actions column
        ],
        drawCallback: function() {
            updateTeacherCount();
            // Collapse all expanded rows when table redraws
            $('.expand-btn').removeClass('expanded');
            $('.classes-detail-row').remove();
        }
    });

    // Column index mapping
    const COL = {
        EXPAND: 0,
        FULL_NAME: 1,
        EMAIL: 2,
        CLASSES: 3,
        ACTIONS: 4
    };

    // Handle expand/collapse button clicks
    $(document).on('click', '.expand-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const teacherId = $(this).data('teacher-id');
        const mainRow = $(this).closest('tr');
        const existingDetailRow = mainRow.next('.classes-detail-row');
        const isExpanded = $(this).hasClass('expanded');
        
        if (isExpanded) {
            // Collapse
            $(this).removeClass('expanded');
            existingDetailRow.remove();
        } else {
            // Expand - create and insert detail row
            $(this).addClass('expanded');
            
            // Get classes data from the main row
            const classesData = mainRow.data('classes');
            
            // Build classes HTML (without class codes)
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
            
            // Create detail row
            const detailRow = $(`
                <tr class="classes-detail-row">
                    <td colspan="5" class="classes-detail-cell">
                        <div class="classes-container">
                            ${classesHtml}
                        </div>
                    </td>
                </tr>
            `);
            
            // Insert after main row
            mainRow.after(detailRow);
        }
    });

    // Search Teacher Filter (searches name and email)
    $('#searchTeacher').on('keyup', function() {
        const searchValue = this.value.toLowerCase();
        
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            if (searchValue === '') return true;
            
            const fullName = data[COL.FULL_NAME].toLowerCase();
            const email = data[COL.EMAIL].toLowerCase();
            
            return fullName.includes(searchValue) || email.includes(searchValue);
        });
        
        dataTable.draw();
        $.fn.dataTable.ext.search.pop();
    });

    // ------------------------------
    // Bootstrap searchable dropdown
    // ------------------------------

    // Filter dropdown items as you type
    $('#classSearchInput').on('input', function () {
        const value = this.value.toLowerCase().trim();

        $('#classDropdownList .class-option').each(function () {
            const text = $(this).text().toLowerCase();
            if (text.indexOf(value) !== -1) {
                $(this).removeAttr('hidden');
            } else {
                $(this).attr('hidden', 'hidden');
            }
        });

        // Try to open dropdown (works if data-toggle="dropdown" is present)
        // Trigger click only if at least one visible item exists
        if ($('#classDropdownList .class-option:not([hidden])').length > 0) {
            // Opening programmatically: trigger click which the data-toggle handles
            // If the dropdown is already open, this won't close it (Bootstrap handles toggle)
            $('#classSearchInput').trigger('click');
        }
    });

    // Click a class option to apply filter
    $(document).on('click', '.class-option', function (e) {
        e.preventDefault();

        const classId = $(this).data('id');
        const className = $(this).text().trim();

        // Put selected class name into input
        $('#classSearchInput').val(className);
        // Save selected class id on the input for reference
        $('#classSearchInput').data('selected-class', classId);

        // Close the dropdown menu (Bootstrap: remove show classes)
        $('#classDropdownList').removeClass('show');
        $('#classDropdownList').parent().removeClass('show');

        // Apply DataTable filter
        if (classId === "" || classId === undefined) {
            // Clear class filter (show all)
            dataTable.draw(); // draw with no extra filter
            return;
        }

        // Push class filter
        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
            const row = dataTable.row(dataIndex).node();
            const classesData = $(row).data('classes');

            if (!classesData || classesData.length === 0) {
                return false;
            }

            return classesData.some(cls => cls.id == classId);
        });

        dataTable.draw();
        // Remove the last pushed filter (keeps other ext.search functions independent)
        $.fn.dataTable.ext.search.pop();
    });

    // If user clicks outside, hide dropdown (safety)
    $(document).on('click', function (e) {
        const target = $(e.target);
        if (!target.is('#classSearchInput') && target.closest('#classDropdownList').length === 0) {
            $('#classDropdownList').removeClass('show');
            $('#classDropdownList').parent().removeClass('show');
        }
    });

    // Clear All Filters (updated to reset the new dropdown)
    $('#clearFilters').on('click', function() {
        // Clear input fields
        $('#searchTeacher').val('');
        $('#classSearchInput').val('').removeData('selected-class');

        // Reset dropdown items visibility
        $('#classDropdownList .class-option').removeAttr('hidden');

        // Clear all DataTable searches and redraw
        dataTable.search('').columns().search('').draw();
    });

    // Initial count
    updateTeacherCount();
});