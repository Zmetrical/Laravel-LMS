console.log("Teacher Audit Logs");

let dataTable;

$(document).ready(function () {
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        };
        return date.toLocaleString('en-US', options);
    }

    function formatDateLong(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        };
        return date.toLocaleString('en-US', options);
    }

    function updateLogCount() {
        if (dataTable && dataTable.rows) {
            const count = dataTable.rows({ filter: 'applied' }).count();
            $('#logsCount').text(count + ' Log' + (count !== 1 ? 's' : ''));
        }
    }

    dataTable = $('#auditTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: API_ROUTES.getLogs,
            type: 'GET',
            data: function(d) {
                d.search_value = $('#searchTeacher').val();
                d.action = $('#actionFilter').val();
                d.module = $('#moduleFilter').val();
                d.date_from = $('#dateFrom').val();
                d.date_to = $('#dateTo').val();
            },
            error: function(xhr, error, code) {
                console.error('DataTable error:', error);
            }
        },
        columns: [
            { 
                data: 'created_at',
                render: function(data) {
                    return formatDate(data);
                }
            },
            { 
                data: 'user_identifier',
                render: function(data, type, row) {
                    return data || '<span class="text-muted">Unknown</span>';
                }
            },
            { 
                data: 'action',
                render: function(data) {
                    const badges = {
                        'created': 'badge-primary',
                        'updated': 'badge-secondary',
                        'viewed': 'badge-info',
                        'graded': 'badge-success',
                        'exported': 'badge-warning'
                    };
                    const badgeClass = badges[data] || 'badge-secondary';
                    return `<span class="badge ${badgeClass}">${data.toUpperCase()}</span>`;
                }
            },
            { data: 'module' },
            { 
                data: 'record_id',
                render: function(data) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            },
            { 
                data: 'description',
                render: function(data) {
                    if (!data) return '<span class="text-muted">No description</span>';
                    return data.length > 50 ? data.substring(0, 50) + '...' : data;
                }
            },
            { data: 'ip_address' },
            {
                data: null,
                orderable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    return `<button class="btn btn-sm btn-secondary view-details" 
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
            emptyTable: "No audit logs found",
            zeroRecords: "No matching logs found",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ logs",
            infoEmpty: "Showing 0 to 0 of 0 logs",
            infoFiltered: "(filtered from _MAX_ total logs)"
        },
        drawCallback: function() {
            updateLogCount();
        }
    });

    // Filter triggers
    $('#searchTeacher, #actionFilter, #moduleFilter, #dateFrom, #dateTo').on('change keyup', function() {
        dataTable.draw();
    });

    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#searchTeacher').val('');
        $('#actionFilter').val('');
        $('#moduleFilter').val('');
        $('#dateFrom').val('');
        $('#dateTo').val('');
        dataTable.draw();
    });

    // View details
    $('#auditTable').on('click', '.view-details', function() {
        const logId = $(this).data('id');
        
        $.ajax({
            url: API_ROUTES.getLogs + '/' + logId,
            type: 'GET',
            success: function(data) {
                $('#detail-timestamp').text(formatDateLong(data.created_at));
                $('#detail-user').text(data.user_identifier || 'Unknown');
                $('#detail-action').html(`<span class="badge badge-secondary">${data.action.toUpperCase()}</span>`);
                $('#detail-module').text(data.module);
                $('#detail-record').text(data.record_id || 'N/A');
                $('#detail-description').text(data.description || 'No description');
                $('#detail-ip').text(data.ip_address);
                $('#detail-agent').text(data.user_agent || 'N/A');
                
                // Show changes if available
                if (data.old_values || data.new_values) {
                    $('#changes-section').show();
                    $('#detail-old').text(data.old_values ? JSON.stringify(JSON.parse(data.old_values), null, 2) : 'N/A');
                    $('#detail-new').text(data.new_values ? JSON.stringify(JSON.parse(data.new_values), null, 2) : 'N/A');
                } else {
                    $('#changes-section').hide();
                }
                
                $('#detailsModal').modal('show');
            },
            error: function(xhr, status, error) {
                console.error('Error fetching log details:', error);
                alert('Failed to load log details');
            }
        });
    });

    updateLogCount();
});