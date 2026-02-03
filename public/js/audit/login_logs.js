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

    function calculateDuration(loginTime, logoutTime) {
        if (!logoutTime) return 'Active';
        
        const login = new Date(loginTime);
        const logout = new Date(logoutTime);
        const diffMs = logout - login;
        
        const hours = Math.floor(diffMs / (1000 * 60 * 60));
        const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diffMs % (1000 * 60)) / 1000);
        
        if (hours > 0) {
            return `${hours}h ${minutes}m`;
        } else if (minutes > 0) {
            return `${minutes}m ${seconds}s`;
        } else {
            return `${seconds}s`;
        }
    }

    function parseBrowser(userAgent) {
        if (!userAgent) return 'Unknown';
        
        if (userAgent.includes('Chrome') && !userAgent.includes('Edg')) return 'Chrome';
        if (userAgent.includes('Firefox')) return 'Firefox';
        if (userAgent.includes('Safari') && !userAgent.includes('Chrome')) return 'Safari';
        if (userAgent.includes('Edg')) return 'Edge';
        if (userAgent.includes('Opera') || userAgent.includes('OPR')) return 'Opera';
        
        return 'Other';
    }

    function parsePlatform(userAgent) {
        if (!userAgent) return 'Unknown';
        
        if (userAgent.includes('Windows')) return 'Windows';
        if (userAgent.includes('Mac')) return 'macOS';
        if (userAgent.includes('Linux')) return 'Linux';
        if (userAgent.includes('Android')) return 'Android';
        if (userAgent.includes('iOS') || userAgent.includes('iPhone') || userAgent.includes('iPad')) return 'iOS';
        
        return 'Other';
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
                    const badges = {
                        'admin': 'badge-danger',
                        'teacher': 'badge-primary',
                        'student': 'badge-secondary',
                        'guardian': 'badge-info'
                    };
                    const badgeClass = badges[data] || 'badge-secondary';
                    return `<span class="badge ${badgeClass}">${data.toUpperCase()}</span>`;
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
                data: null,
                render: function(data, type, row) {
                    return calculateDuration(row.created_at, row.logout_at);
                }
            },
            { 
                data: 'logout_at',
                render: function(data) {
                    return data ? formatDate(data) : '<span class="text-muted">-</span>';
                }
            },
            { 
                data: 'logout_at',
                render: function(data) {
                    if (data) {
                        return '<span class="badge badge-logout">Logged Out</span>';
                    } else {
                        return '<span class="badge badge-active">Active</span>';
                    }
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
        
        $.ajax({
            url: API_ROUTES.getLogs + '/' + logId,
            type: 'GET',
            success: function(data) {
                $('#detail-usertype').html(`<span class="badge badge-secondary">${data.user_type.toUpperCase()}</span>`);
                $('#detail-user').text(data.user_identifier || 'Unknown');
                $('#detail-login').text(formatDateLong(data.created_at));
                $('#detail-logout').text(data.logout_at ? formatDateLong(data.logout_at) : 'Still Active');
                $('#detail-duration').text(calculateDuration(data.created_at, data.logout_at));
                $('#detail-ip').text(data.ip_address);
                $('#detail-session').text(data.session_id || 'N/A');
                $('#detail-agent').text(data.user_agent || 'N/A');
                $('#detail-browser').text(parseBrowser(data.user_agent));
                $('#detail-platform').text(parsePlatform(data.user_agent));
                
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