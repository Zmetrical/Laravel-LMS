console.log("Teacher List");

let activeDataTable;
let inactiveDataTable;

$(document).ready(function () {

    // ── DataTable configs ─────────────────────────────────────────────────────

    const dtDefaults = {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        scrollX: true,
        autoWidth: false,
        language: {
            emptyTable: 'No teachers found',
            zeroRecords: 'No matching teachers found',
            info: 'Showing _START_ to _END_ of _TOTAL_ teachers',
            infoEmpty: 'Showing 0 to 0 of 0 teachers',
            infoFiltered: '(filtered from _MAX_ total teachers)',
            lengthMenu: 'Show _MENU_ entries',
        },
        dom: '<"row"<"col-sm-12 col-md-6"l>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
    };

    activeDataTable = $('#activeTeacherTable').DataTable(Object.assign({}, dtDefaults, {
        order: [[1, 'asc']],
        columnDefs: [
            { targets: 0, orderable: false },
            { targets: 4, orderable: false },
        ],
        drawCallback: function () {
            // Clean up any open expand rows on redraw
            $('.expand-btn').removeClass('expanded');
            $('.classes-detail-row').remove();
        },
    }));

    inactiveDataTable = $('#inactiveTeacherTable').DataTable(Object.assign({}, dtDefaults, {
        order: [[0, 'asc']],
        columnDefs: [{ targets: 2, orderable: false }],
    }));

    // ── Expand / collapse class list (active tab only) ────────────────────────

    $(document).on('click', '.expand-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const $btn      = $(this);
        const $mainRow  = $btn.closest('tr');
        const $detailRow = $mainRow.next('.classes-detail-row');
        const expanded  = $btn.hasClass('expanded');

        if (expanded) {
            $btn.removeClass('expanded');
            $detailRow.remove();
            return;
        }

        $btn.addClass('expanded');

        const classesData = $mainRow.data('classes');
        let html = '';

        if (classesData && classesData.length > 0) {
            classesData.forEach(function (cls) {
                html += `<div class="class-item"><span>${cls.class_name}</span></div>`;
            });
        } else {
            html = '<p class="text-muted mb-0"><i class="fas fa-inbox mr-1"></i> No classes assigned for this school year</p>';
        }

        $mainRow.after(`
            <tr class="classes-detail-row">
                <td colspan="5" class="classes-detail-cell">${html}</td>
            </tr>
        `);
    });

    // ── Active tab filters ────────────────────────────────────────────────────

    $('#searchActiveTeacher').on('keyup', function () {
        activeDataTable.search($(this).val()).draw();
    });

    // Class dropdown search
    $('#classSearchInputActive').on('input', function () {
        const val = this.value.toLowerCase().trim();
        $('#classDropdownListActive .class-option').each(function () {
            $(this).attr('hidden', $(this).text().toLowerCase().indexOf(val) === -1 ? 'hidden' : null);
        });
    });

    $(document).on('click', '#classDropdownListActive .class-option', function (e) {
        e.preventDefault();

        const classId   = $(this).data('id');
        const className = $(this).text().trim();

        $('#classSearchInputActive').val(className).data('selected-class', classId);
        $('#classDropdownListActive').removeClass('show').parent().removeClass('show');

        // Remove any previous class filter
        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(
            fn => fn._tag !== 'activeClassFilter'
        );

        if (classId !== '' && classId !== undefined) {
            const filterFn = function (settings, data, dataIndex) {
                if (settings.nTable.id !== 'activeTeacherTable') return true;
                const row        = activeDataTable.row(dataIndex).node();
                const classesData = $(row).data('classes');
                return classesData && classesData.some(cls => cls.id == classId);
            };
            filterFn._tag = 'activeClassFilter';
            $.fn.dataTable.ext.search.push(filterFn);
        }

        activeDataTable.draw();
    });

    $('#clearActiveFilters').on('click', function () {
        $('#searchActiveTeacher').val('');
        $('#classSearchInputActive').val('').removeData('selected-class');
        $('#classDropdownListActive .class-option').removeAttr('hidden');
        $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(
            fn => fn._tag !== 'activeClassFilter'
        );
        activeDataTable.search('').draw();
    });

    // ── Inactive tab filters ──────────────────────────────────────────────────

    $('#searchInactiveTeacher').on('keyup', function () {
        inactiveDataTable.search($(this).val()).draw();
    });

    $('#clearInactiveFilters').on('click', function () {
        $('#searchInactiveTeacher').val('');
        inactiveDataTable.search('').draw();
    });

    // ── Close class dropdown when clicking outside ────────────────────────────

    $(document).on('click', function (e) {
        if (!$(e.target).is('#classSearchInputActive') &&
            !$(e.target).closest('#classDropdownListActive').length) {
            $('#classDropdownListActive').removeClass('show').parent().removeClass('show');
        }
    });

    // ── Resize / tab-show adjustments ────────────────────────────────────────

    $(window).on('resize', function () {
        activeDataTable.columns.adjust();
        inactiveDataTable.columns.adjust();
    });

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');
        if (target === '#activeTeachers')   activeDataTable.columns.adjust().draw();
        if (target === '#inactiveTeachers') inactiveDataTable.columns.adjust().draw();
    });
});