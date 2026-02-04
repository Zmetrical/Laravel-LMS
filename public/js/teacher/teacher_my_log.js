$(document).ready(function () {

    // ── helpers ──────────────────────────────────────────────────────────────
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric', month: 'short', day: '2-digit',
            hour: '2-digit', minute: '2-digit', hour12: true
        });
    }

    function formatDateLong(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric', month: 'long', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
        });
    }

    // Action → badge-class map  (only primary / secondary / default — no info/success/warning)
    const ACTION_BADGES = {
        'created':  'badge-primary',
        'updated':  'badge-secondary',
        'enabled':  'badge-primary',
        'disabled': 'badge-secondary',
        'viewed':   'badge-secondary',
        'exported': 'badge-secondary'
    };

    function actionBadge(action) {
        const cls = ACTION_BADGES[action] || 'badge-secondary';
        return `<span class="badge ${cls}">${action.toUpperCase()}</span>`;
    }

    // ── DataTable ────────────────────────────────────────────────────────────
    const dataTable = $('#myAuditTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: API_ROUTES.getMyLogs,
            type: 'GET',
            data: function (d) {
                d.action     = $('#actionFilter').val();
                d.module     = $('#moduleFilter').val();
                d.date_from  = $('#dateFrom').val();
                d.date_to    = $('#dateTo').val();
            },
            error: function (xhr) {
                console.error('DataTable AJAX error:', xhr.statusText);
            }
        },
        columns: [
            {
                data: 'created_at',
                render: function (data) { return formatDate(data); }
            },
            {
                data: 'action',
                render: function (data) { return actionBadge(data); }
            },
            { data: 'module' },
            {
                data: 'record_id',
                render: function (data) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            },
            {
                data: 'description',
                render: function (data) {
                    if (!data) return '<span class="text-muted">—</span>';
                    return data.length > 80 ? data.substring(0, 80) + '…' : data;
                }
            },
            { data: 'ip_address' },
            {
                data: null,
                orderable: false,
                className: 'text-center',
                render: function (data, type, row) {
                    return `<button class="btn btn-sm btn-secondary view-detail"
                                    data-id="${row.id}" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>`;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6">>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            emptyTable:   'No activity logs found',
            zeroRecords:  'No matching logs found',
            lengthMenu:   'Show _MENU_ entries',
            info:         'Showing _START_ to _END_ of _TOTAL_ logs',
            infoEmpty:    'Showing 0 to 0 of 0 logs',
            infoFiltered: '(filtered from _MAX_ total logs)'
        },
        drawCallback: function () {
            const count = this.api().rows({ filter: 'applied' }).count();
            $('#logsCount').text(count + ' Log' + (count !== 1 ? 's' : ''));
        }
    });

    // ── Filters ──────────────────────────────────────────────────────────────
    $('#actionFilter, #moduleFilter, #dateFrom, #dateTo').on('change', function () {
        dataTable.draw();
    });

    $('#clearFilters').on('click', function () {
        $('#actionFilter, #moduleFilter').val('');
        $('#dateFrom, #dateTo').val('');
        dataTable.draw();
    });

    // ── Detail modal ─────────────────────────────────────────────────────────
    $('#myAuditTable').on('click', '.view-detail', function () {
        const url = API_ROUTES.getLogDetail.replace('__ID__', $(this).data('id'));

        $.ajax({
            url: url,
            type: 'GET',
            success: function (log) {
                $('#detail-timestamp').text(formatDateLong(log.created_at));
                $('#detail-action').html(actionBadge(log.action));
                $('#detail-module').text(log.module);
                $('#detail-record').text(log.record_id || 'N/A');
                $('#detail-description').text(log.description || '—');
                $('#detail-ip').text(log.ip_address || 'N/A');

                if (log.old_values || log.new_values) {
                    $('#changes-section').show();
                    $('#detail-old').text(
                        log.old_values
                            ? JSON.stringify(JSON.parse(log.old_values), null, 2)
                            : 'N/A'
                    );
                    $('#detail-new').text(
                        log.new_values
                            ? JSON.stringify(JSON.parse(log.new_values), null, 2)
                            : 'N/A'
                    );
                } else {
                    $('#changes-section').hide();
                }

                $('#detailsModal').modal('show');
            },
            error: function () {
                alert('Failed to load log details.');
            }
        });
    });
});