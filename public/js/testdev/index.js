$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    console.log('Page loaded, initializing...');
    console.log('API Routes:', API_ROUTES);
    
    // Load guardians into selector and table
    loadGuardians();

    // Guardian selector change event
    $('#guardian_selector').change(function() {
        const guardianId = $(this).val();
        
        if (!guardianId) {
            $('#guardianInfo').hide();
            return;
        }

        // Find guardian data
        $.ajax({
            url: API_ROUTES.getGuardians,
            method: 'GET',
            success: function(guardians) {
                const guardian = guardians.find(g => g.id == guardianId);
                
                if (guardian) {
                    $('#guardianName').text(guardian.first_name + ' ' + guardian.last_name);
                    $('#guardianEmail').text(guardian.email);
                    
                    // Get student names
                    $.ajax({
                        url: API_ROUTES.getGuardianStudents + '/' + guardianId,
                        method: 'GET',
                        success: function(students) {
                            const studentNames = students.map(s => s.full_name).join(', ');
                            $('#guardianStudents').text(studentNames || 'No students linked');
                            $('#guardianInfo').slideDown();
                        }
                    });
                }
            }
        });
    });

    // Send guardian email form
    $('#sendGuardianEmailForm').submit(function(e) {
        e.preventDefault();
        
        const btn = $(this).find('button[type="submit"]');
        const originalText = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Sending...').prop('disabled', true);

        $.ajax({
            url: API_ROUTES.sendGuardianEmail,
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Email Sent Successfully!',
                        html: '<p><strong>Sent to:</strong> ' + response.guardian_email + '</p>',
                        confirmButtonColor: '#28a745'
                    });
                    
                    let resultHtml = '<div class="alert alert-success">';
                    resultHtml += '<h5><i class="fas fa-check-circle mr-2"></i>Email Sent Successfully!</h5>';
                    resultHtml += '<p><strong>Sent to:</strong> ' + response.guardian_email + '</p>';
                    resultHtml += '<p><strong>Access URL:</strong></p>';
                    resultHtml += '<div class="input-group">';
                    resultHtml += '<input type="text" class="form-control" value="' + response.access_url + '" readonly>';
                    resultHtml += '<div class="input-group-append">';
                    resultHtml += '<button class="btn btn-primary copy-url-btn" data-url="' + response.access_url + '">';
                    resultHtml += '<i class="fas fa-copy"></i></button>';
                    resultHtml += '</div></div>';
                    resultHtml += '<a href="' + response.access_url + '" class="btn btn-sm btn-primary mt-2" target="_blank">';
                    resultHtml += '<i class="fas fa-external-link-alt mr-2"></i>Test Access</a>';
                    resultHtml += '</div>';
                    
                    $('#resultContent').html(resultHtml);
                    $('#resultCard').slideDown();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message,
                        confirmButtonColor: '#dc3545'
                    });
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'An error occurred';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error,
                    confirmButtonColor: '#dc3545'
                });
            },
            complete: function() {
                btn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Copy URL button
    $(document).on('click', '.copy-url-btn', function() {
        const url = $(this).data('url');
        const input = $(this).closest('.input-group').find('input');
        input.select();
        document.execCommand('copy');
        
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: 'URL copied to clipboard!',
            timer: 1500,
            showConfirmButton: false
        });
    });

    // Refresh guardians list
    $('#refreshGuardiansBtn').click(function() {
        loadGuardians();
    });

    // Copy URL in modal
    $('#copyUrlBtn').click(function() {
        const input = $('#accessUrlInput');
        input.select();
        document.execCommand('copy');
        
        Swal.fire({
            icon: 'success',
            title: 'Copied!',
            text: 'URL copied to clipboard!',
            timer: 1500,
            showConfirmButton: false
        });
    });
});

function loadGuardians() {
    console.log('loadGuardians() called');
    $('#guardiansTableBody').html('<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>');
    
    $.ajax({
        url: API_ROUTES.getGuardians,
        method: 'GET',
        beforeSend: function() {
            console.log('Sending request to:', API_ROUTES.getGuardians);
        },
        success: function(guardians) {
            console.log('Guardians loaded:', guardians);
            
            // Populate selector
            const selector = $('#guardian_selector');
            const currentValue = selector.val();
            
            selector.empty();
            selector.append('<option value="">-- Select Guardian --</option>');
            
            guardians.forEach(function(guardian) {
                const text = guardian.first_name + ' ' + guardian.last_name + ' (' + guardian.email + ')';
                const option = $('<option></option>').attr('value', guardian.id).text(text);
                selector.append(option);
            });
            
            // Restore selection
            if (currentValue) {
                selector.val(currentValue).trigger('change');
            }

            // Populate table
            if (guardians.length === 0) {
                $('#guardiansTableBody').html('<tr><td colspan="5" class="text-center text-muted">No guardians found</td></tr>');
                $('#totalGuardians').text('0');
                $('#activeGuardians').text('0');
                return;
            }

            let html = '';
            let activeCount = 0;

            guardians.forEach(function(guardian) {
                if (guardian.is_active) activeCount++;

                html += '<tr>';
                html += '<td>' + guardian.first_name + ' ' + guardian.last_name + '</td>';
                html += '<td><small>' + guardian.email + '</small></td>';
                html += '<td class="text-center"><span class="badge badge-primary">' + guardian.student_count + '</span></td>';
                html += '<td class="text-center">';
                
                if (guardian.is_active) {
                    html += '<span class="badge badge-success">Active</span>';
                } else {
                    html += '<span class="badge badge-secondary">Inactive</span>';
                }
                
                html += '</td>';
                html += '<td class="text-right">';
                html += '<div class="btn-group btn-group-sm">';
                
                html += '<button class="btn btn-primary send-email-btn" data-id="' + guardian.id + '" title="Send Email">';
                html += '<i class="fas fa-paper-plane"></i></button>';
                
                html += '<button class="btn btn-secondary view-url-btn" data-url="' + guardian.access_url + '" title="View URL">';
                html += '<i class="fas fa-link"></i></button>';
                
                html += '<button class="btn btn-warning toggle-status-btn" data-id="' + guardian.id + '" ';
                html += 'data-status="' + guardian.is_active + '" title="Toggle Status">';
                html += '<i class="fas fa-toggle-' + (guardian.is_active ? 'on' : 'off') + '"></i></button>';
                
                html += '</div></td>';
                html += '</tr>';
            });

            $('#guardiansTableBody').html(html);
            $('#totalGuardians').text(guardians.length);
            $('#activeGuardians').text(activeCount);

            // Attach event handlers
            attachGuardianActions();
        },
        error: function(xhr, status, error) {
            console.error('Error loading guardians:', status, error);
            console.error('Response:', xhr.responseText);
            $('#guardiansTableBody').html('<tr><td colspan="5" class="text-center text-danger">Error loading guardians</td></tr>');
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load guardians: ' + error,
                confirmButtonColor: '#dc3545'
            });
        }
    });
}

function attachGuardianActions() {
    console.log('Attaching guardian actions...');
    
    // Send email directly from table
    $('.send-email-btn').click(function() {
        const guardianId = $(this).data('id');
        $('#guardian_selector').val(guardianId).trigger('change');
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#sendGuardianEmailForm').offset().top - 100
        }, 500);
    });

    // View URL
    $('.view-url-btn').click(function() {
        const url = $(this).data('url');
        $('#accessUrlInput').val(url);
        $('#openUrlBtn').attr('href', url);
        $('#viewUrlModal').modal('show');
    });

    // Toggle status
    $('.toggle-status-btn').click(function() {
        const id = $(this).data('id');
        const currentStatus = $(this).data('status');
        const btn = $(this);

        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to ' + (currentStatus ? 'deactivate' : 'activate') + ' this guardian?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, ' + (currentStatus ? 'deactivate' : 'activate') + ' it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: API_ROUTES.toggleGuardianStatus + '/' + id,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Updated!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            loadGuardians();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to update status',
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            }
        });
    });
}