$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    
    let currentGuardian = null;
    
    loadGuardians();

    // Guardian selector change event
    $('#guardian_selector').change(function() {
        const guardianId = $(this).val();
        
        if (!guardianId) {
            $('#guardianInfo').hide();
            $('#resultCard').hide();
            currentGuardian = null;
            return;
        }

        $.ajax({
            url: API_ROUTES.getGuardians,
            method: 'GET',
            success: function(guardians) {
                currentGuardian = guardians.find(g => g.id == guardianId);
                
                if (currentGuardian) {
                    updateGuardianInfo(currentGuardian);
                }
            }
        });
    });

    // Send verification email
    $('#sendVerificationBtn').click(function() {
        if (!currentGuardian) return;
        
        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Sending...').prop('disabled', true);

        $.ajax({
            url: API_ROUTES.sendVerification,
            method: 'POST',
            data: { guardian_id: currentGuardian.id },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Verification Email Sent!',
                        html: `
                            <p>Sent to: <strong>${currentGuardian.email}</strong></p>
                            <div class="alert alert-secondary mt-3">
                                <small><i class="fas fa-info-circle mr-1"></i>After verification, access email will be sent automatically</small>
                            </div>
                        `,
                        confirmButtonText: 'OK'
                    });
                    
                    showResult(response, 'verification');
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON?.message || 'An error occurred';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error,
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                btn.html(originalHtml).prop('disabled', false);
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
    $('#refreshBtn').click(function() {
        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        loadGuardians();
        
        setTimeout(function() {
            btn.html(originalHtml).prop('disabled', false);
            
            Swal.fire({
                icon: 'success',
                title: 'Refreshed!',
                timer: 1000,
                showConfirmButton: false
            });
        }, 500);
    });
});

function updateGuardianInfo(guardian) {
    $('#guardianName').text(guardian.first_name + ' ' + guardian.last_name);
    $('#guardianEmail').text(guardian.email);
    $('#studentCount').text(guardian.student_count);
    
    // Update verification status
    const isVerified = guardian.is_verified;
    const statusIcon = $('#verificationIcon');
    const statusText = $('#verificationStatus');
    const callout = $('#guardianDetails');
    
    if (isVerified) {
        statusIcon.removeClass('bg-warning').addClass('bg-success');
        statusIcon.find('i').removeClass('fa-envelope').addClass('fa-check-circle');
        statusText.html('Verified <i class="fas fa-check ml-1"></i>');
        callout.removeClass('callout-warning').addClass('callout-success');
        
        $('#verifiedAt').text(new Date(guardian.email_verified_at).toLocaleString());
        $('#verifiedAtContainer').show();
        
        $('#sendVerificationBtn')
            .prop('disabled', true)
            .removeClass('btn-default')
            .addClass('btn-success')
            .html('<i class="fas fa-check-circle mr-2"></i>Already Verified');
    } else {
        statusIcon.removeClass('bg-success').addClass('bg-warning');
        statusIcon.find('i').removeClass('fa-check-circle').addClass('fa-envelope');
        statusText.html('Not Verified <i class="fas fa-times ml-1"></i>');
        callout.removeClass('callout-success').addClass('callout-warning');
        
        $('#verifiedAtContainer').hide();
        
        $('#sendVerificationBtn')
            .prop('disabled', false)
            .removeClass('btn-success')
            .addClass('btn-default')
            .html('<i class="fas fa-envelope-open-text mr-2"></i>Send Verification Email');
    }
    
    // Load students
    $.ajax({
        url: API_ROUTES.getGuardianStudents + '/' + guardian.id,
        method: 'GET',
        success: function(students) {
            const studentNames = students.map(s => s.full_name).join(', ');
            $('#guardianStudents').text(studentNames || 'No students linked');
            $('#guardianInfo').slideDown();
        }
    });
}

function showResult(response, type) {
    let resultHtml = '<div class="callout callout-secondary">';
    resultHtml += '<h5><i class="fas fa-paper-plane mr-2"></i>';
    resultHtml += 'Verification Email Sent';
    resultHtml += '</h5>';
    resultHtml += '<p class="mb-2">The guardian will receive an email with a verification link.</p>';
    
    if (response.verification_url) {
        const url = response.verification_url;
        resultHtml += '<p class="mb-2"><strong>Verification URL (for testing):</strong></p>';
        resultHtml += '<div class="input-group mb-2">';
        resultHtml += '<input type="text" class="form-control form-control-sm" value="' + url + '" readonly>';
        resultHtml += '<div class="input-group-append">';
        resultHtml += '<button class="btn btn-sm btn-default copy-url-btn" data-url="' + url + '">';
        resultHtml += '<i class="fas fa-copy"></i></button>';
        resultHtml += '</div></div>';
        resultHtml += '<a href="' + url + '" class="btn btn-sm btn-secondary" target="_blank">';
        resultHtml += '<i class="fas fa-external-link-alt mr-2"></i>Test Verification Link</a>';
    }
    
    resultHtml += '<div class="alert alert-secondary mt-3 mb-0">';
    resultHtml += '<small><i class="fas fa-info-circle mr-1"></i>';
    resultHtml += 'After the guardian clicks the verification link, the access email will be sent automatically.</small>';
    resultHtml += '</div>';
    
    resultHtml += '</div>';
    
    $('#resultContent').html(resultHtml);
    $('#resultCard').slideDown();
}

function loadGuardians() {
    $.ajax({
        url: API_ROUTES.getGuardians,
        method: 'GET',
        success: function(guardians) {
            const selector = $('#guardian_selector');
            const currentValue = selector.val();
            
            selector.empty();
            selector.append('<option value="">-- Select Guardian --</option>');
            
            guardians.forEach(function(guardian) {
                const status = guardian.is_verified ? '✓' : '✗';
                const text = `${guardian.first_name} ${guardian.last_name} (${guardian.email}) [${status}]`;
                const option = $('<option></option>').attr('value', guardian.id).text(text);
                selector.append(option);
            });
            
            if (currentValue) {
                selector.val(currentValue).trigger('change');
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load guardians: ' + error,
                confirmButtonText: 'OK'
            });
        }
    });
}