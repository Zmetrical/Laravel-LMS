console.log("Login Audit Logs");

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
            $('#logsCount').text(count + ' Session' + (count !== 1 ? 's' : ''));
        }
    }

    function formatDuration(seconds) {
        if (!seconds || seconds <= 0) return 'N/A';
        
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            return `${hours}h ${minutes}m`;
        } else if (minutes > 0) {
            return `${minutes}m ${secs}s`;
        } else {
            return `${secs}s`;
        }
    }

    dataTable = $('#loginTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: API_ROUTES.getLogs,
            type: 'GET',
            data: function(d) {
                d.user_type = $('#userTypeFilter').val();
                d.search_value = $('#searchUser').val();
                d.status = $('#statusFilter').val();
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
                data: 'user_type',
                render: function(data) {
                    return `<span class="badge badge-secondary">${data.toUpperCase()}</span>`;
                }
            },
            { 
                data: 'user_identifier',
                render: function(data) {
                    return data || '<span class="text-muted">Unknown</span>';
                }
            },
            { data: 'ip_address' },
            { 
                data: 'duration_seconds',
                render: function(data, type, row) {
                    if (row.status === 'active') {
                        return '<span class="badge badge-secondary">Active</span>';
                    }
                    return formatDuration(data);
                }
            },
            { 
                data: 'logout_at',
                render: function(data) {
                    return data ? formatDate(data) : '<span class="text-muted">-</span>';
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    const badges = {
                        'active': '<span class="badge badge-secondary">Active</span>',
                        'logged_out': '<span class="badge badge-secondary">Logged Out</span>',
                        'expired': '<span class="badge badge-secondary">Expired</span>'
                    };
                    return badges[data] || '<span class="badge badge-secondary">Unknown</span>';
                }
            },
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
            emptyTable: "No login sessions found",
            zeroRecords: "No matching sessions found",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ sessions",
            infoEmpty: "Showing 0 to 0 of 0 sessions",
            infoFiltered: "(filtered from _MAX_ total sessions)"
        },
        drawCallback: function() {
            updateLogCount();
        }
    });

    // Filter triggers
    $('#userTypeFilter, #searchUser, #statusFilter, #dateFrom, #dateTo').on('change keyup', function() {
        dataTable.draw();
    });

    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#userTypeFilter').val('');
        $('#searchUser').val('');
        $('#statusFilter').val('');
        $('#dateFrom').val('');
        $('#dateTo').val('');
        dataTable.draw();
    });

    // View details
    $('#loginTable').on('click', '.view-details', function() {
        const logId = $(this).data('id');
        const detailUrl = API_ROUTES.getLogDetails.replace(':id', logId);
        
        $.ajax({
            url: detailUrl,
            type: 'GET',
            success: function(data) {
                $('#detail-usertype').html(`<span class="badge badge-secondary">${data.user_type.toUpperCase()}</span>`);
                $('#detail-user').text(data.user_identifier || 'Unknown');
                $('#detail-login').text(formatDateLong(data.created_at));
                $('#detail-logout').text(data.logout_at ? formatDateLong(data.logout_at) : 'Still Active');
                $('#detail-duration').text(data.duration_seconds ? formatDuration(data.duration_seconds) : 'N/A');
                $('#detail-ip').text(data.ip_address || 'N/A');
                $('#detail-session').text(data.session_id || 'N/A');
                
                // Set status badge
                const statusBadges = {
                    'active': '<span class="badge badge-secondary">Active</span>',
                    'logged_out': '<span class="badge badge-secondary">Logged Out</span>',
                    'expired': '<span class="badge badge-secondary">Expired</span>'
                };
                $('#detail-status').html(statusBadges[data.status] || 'Unknown');
                
                $('#detailsModal').modal('show');
            },
            error: function(xhr, status, error) {
                console.error('Error fetching login details:', error);
                alert('Failed to load login details');
            }
        });
    });

    updateLogCount();
});