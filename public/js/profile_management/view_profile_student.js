$(document).ready(function () {

    // ---------------------------------------------------------------------------
    //  Page Mode
    // ---------------------------------------------------------------------------

    const pathSegments = window.location.pathname.split('/');
    const isEditMode = pathSegments.includes('edit');

    if (!isEditMode) {
        $('#profileImagePreview').css({
            'width': '200px',
            'height': '200px'
        });

        $('[data-editable]').each(function () {
            if ($(this).is('input, textarea')) {
                $(this).prop('readonly', true);
            } else if ($(this).is('select')) {
                $(this).prop('disabled', true);
            } else {
                $(this).hide();
            }
        });

        // Load guardian statuses in view mode
        loadGuardianStatuses();
    } else {
        $('#profileImagePreview').css({
            'width': '150px',
            'height': '150px'
        });
    }


    // ---------------------------------------------------------------------------
    //  Student Credentials Toggle
    // ---------------------------------------------------------------------------

    let passwordVisible = false;
    let actualPassword = null;

    $('#togglePassword').on('click', function() {
        const $btn = $(this);
        const $icon = $btn.find('i');
        const $input = $('#studentPassword');
        
        if (!passwordVisible) {
            // Show password - fetch from server
            if (actualPassword === null) {
                // First time - fetch password
                $btn.prop('disabled', true);
                $icon.removeClass('fa-eye').addClass('fa-spinner fa-spin');
                
                $.ajax({
                    url: `/admin/profile/student/${API_ROUTES.studentId}/credentials`,
                    type: 'GET',
                    success: function(response) {
                        if (response.success) {
                            actualPassword = response.credential;
                            $input.attr('type', 'text').val(actualPassword);
                            $icon.removeClass('fa-spinner fa-spin').addClass('fa-eye-slash');
                            $btn.attr('title', 'Hide password').prop('disabled', false);
                            passwordVisible = true;
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to retrieve password'
                            });
                            $icon.removeClass('fa-spinner fa-spin').addClass('fa-eye');
                            $btn.prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to retrieve password'
                        });
                        $icon.removeClass('fa-spinner fa-spin').addClass('fa-eye');
                        $btn.prop('disabled', false);
                    }
                });
            } else {
                // Already fetched - just show
                $input.attr('type', 'text').val(actualPassword);
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                $btn.attr('title', 'Hide password');
                passwordVisible = true;
            }
        } else {
            // Hide password
            $input.attr('type', 'password').val('••••••••');
            $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            $btn.attr('title', 'Show password');
            passwordVisible = false;
        }
    });

    // ---------------------------------------------------------------------------
    //  Guardian Status Management
    // ---------------------------------------------------------------------------

function loadGuardianStatuses() {
    $('.verification-status').each(function() {
        const guardianId = $(this).data('guardian-id');
        
        // Skip if no guardian ID (empty guardian placeholder)
        if (!guardianId || guardianId === '') {
            console.log('Skipping empty guardian ID');
            $(this).closest('.guardian-card').find('.guardian-actions').hide();
            return;
        }
        
        const $status = $(this);
        const $actionBtn = $(`.btn-email-action[data-guardian-id="${guardianId}"]`);

        $.ajax({
            url: `/admin/profile/student/${API_ROUTES.studentId}/guardian/${guardianId}/status`,
            type: 'GET',
            success: function(response) {
                console.log('Guardian status response:', response);
                if (response.success) {
                    updateGuardianStatus(guardianId, response.is_verified);
                }
            },
            error: function(xhr) {
                console.error('Guardian status error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    response: xhr.responseJSON,
                    responseText: xhr.responseText,
                    guardianId: guardianId,
                    studentId: API_ROUTES.studentId
                });
                
                $status.removeClass('badge-info badge-primary').addClass('badge-secondary')
                    .html('<i class="fas fa-exclamation-triangle"></i> <small>Error</small>');
                $actionBtn.prop('disabled', true);
            }
        });
    });
}

    function updateGuardianStatus(guardianId, isVerified) {
        const $status = $(`.verification-status[data-guardian-id="${guardianId}"]`);
        const $actionBtn = $(`.btn-email-action[data-guardian-id="${guardianId}"]`);
        const $btnText = $actionBtn.find('.btn-text');

        if (isVerified) {
            // Match student list: badge-primary for verified
            $status.removeClass('badge-info badge-secondary badge-warning').addClass('badge-primary')
                .html('<i class="fas fa-check-circle"></i> <small>Verified</small>');
            
            $actionBtn.data('action', 'access');
            $btnText.text('Resend Access Email');
        } else {
            // Match student list: badge-secondary for pending/not verified
            $status.removeClass('badge-info badge-primary badge-warning').addClass('badge-secondary')
                .html('<i class="fas fa-clock"></i> <small>Pending</small>');
            
            $actionBtn.data('action', 'verification');
            $btnText.text('Resend Verification');
        }

        $actionBtn.prop('disabled', false);
    }

    // ---------------------------------------------------------------------------
    //  Resend Email (Verification or Access)
    // ---------------------------------------------------------------------------

    $(document).on('click', '.btn-email-action', function() {
        const guardianId = $(this).data('guardian-id');
        const action = $(this).data('action');
        const $btn = $(this);
        const originalHtml = $btn.html();

        const endpoint = action === 'verification' 
            ? `/admin/profile/student/${API_ROUTES.studentId}/guardian/${guardianId}/resend-verification`
            : `/admin/profile/student/${API_ROUTES.studentId}/guardian/${guardianId}/resend-access`;

        const title = action === 'verification' 
            ? 'Resend Verification Email?' 
            : 'Resend Access Email?';

        const message = action === 'verification'
            ? 'A verification email will be sent to the guardian.'
            : 'An access email with student portal credentials will be sent to the guardian.';

        Swal.fire({
            title: title,
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#141d5c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, send email'
        }).then((result) => {
            if (result.isConfirmed) {
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');

                $.ajax({
                    url: endpoint,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Email Sent!',
                                text: response.message,
                                timer: 3000,
                                showConfirmButton: false
                            });

                            // Reload status if verification was sent
                            if (action === 'verification') {
                                setTimeout(() => loadGuardianStatuses(), 1000);
                            }
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Notice',
                                text: response.message
                            });
                        }
                        
                        $btn.prop('disabled', false).html(originalHtml);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to send email'
                        });
                        
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            }
        });
    });

    // ---------------------------------------------------------------------------
    //  Change Guardian Email
    // ---------------------------------------------------------------------------

    $(document).on('click', '.btn-change-email', function() {
        const guardianId = $(this).data('guardian-id');
        const $emailInput = $(`.guardian-card[data-guardian-id="${guardianId}"] .guardian-email`);
        const currentEmail = $emailInput.val();

        Swal.fire({
            title: 'Change Guardian Email',
            html: `
                <div class="form-group text-left">
                    <label for="currentEmail">Current Email:</label>
                    <input type="text" id="currentEmail" class="form-control" value="${currentEmail}" readonly>
                </div>
                <div class="form-group text-left">
                    <label for="newEmail">New Email:</label>
                    <input type="email" id="newEmail" class="form-control" placeholder="Enter new email address">
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#141d5c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Update Email',
            preConfirm: () => {
                const newEmail = document.getElementById('newEmail').value;
                
                if (!newEmail) {
                    Swal.showValidationMessage('Please enter an email address');
                    return false;
                }

                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(newEmail)) {
                    Swal.showValidationMessage('Please enter a valid email address');
                    return false;
                }

                if (newEmail === currentEmail) {
                    Swal.showValidationMessage('New email must be different from current email');
                    return false;
                }

                return { newEmail: newEmail };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const newEmail = result.value.newEmail;

                Swal.fire({
                    title: 'Updating...',
                    text: 'Please wait',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: `/admin/profile/student/${API_ROUTES.studentId}/guardian/${guardianId}/change-email`,
                    type: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        new_email: newEmail
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Email Updated!',
                                text: response.message,
                                timer: 3000,
                                showConfirmButton: false
                            });

                            // Update email in UI
                            $emailInput.val(newEmail);

                            // Reload status
                            setTimeout(() => loadGuardianStatuses(), 1000);
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Failed to update email';
                        
                        if (xhr.status === 422) {
                            errorMessage = xhr.responseJSON?.message || 'Validation error';
                        } else if (xhr.responseJSON?.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage
                        });
                    }
                });
            }
        });
    });

    // ---------------------------------------------------------------------------
    //  Load Enrolled Classes
    // ---------------------------------------------------------------------------

    function loadEnrolledClasses() {
        $.ajax({
            url: API_ROUTES.getEnrolledClasses,
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    populateEnrolledClasses(response.data);
                } else {
                    showEnrolledClassesError('Failed to load enrolled classes');
                }
            },
            error: function (xhr) {
                console.error('Error loading enrolled classes:', xhr);
                showEnrolledClassesError('Error loading enrolled classes');
            }
        });
    }

    function populateEnrolledClasses(classes) {
        const tbody = $('#enrolledClassesBody');
        tbody.empty();

        if (classes.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="4" class="text-center text-muted py-3">
                        <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                        No enrolled classes found
                    </td>
                </tr>
            `);
            return;
        }

        classes.forEach(function(cls) {
            const row = `
                <tr>
                    <td>${cls.class_code}</td>
                    <td>${cls.class_name}</td>
                    <td>${cls.year_start} - ${cls.year_end}</td>
                    <td>${cls.semester_name}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function showEnrolledClassesError(message) {
        const tbody = $('#enrolledClassesBody');
        tbody.empty();
        tbody.append(`
            <tr>
                <td colspan="4" class="text-center text-danger">
                    <i class="fas fa-exclamation-triangle"></i> ${message}
                </td>
            </tr>
        `);
    }

    loadEnrolledClasses();

    // ---------------------------------------------------------------------------
    //  Form Validation Helper
    // ---------------------------------------------------------------------------

    function validateForm() {
        const errors = [];

        // Validate first name
        const firstName = $('#firstName').val().trim();
        if (!firstName) {
            errors.push('First name is required');
        }

        // Validate last name
        const lastName = $('#lastName').val().trim();
        if (!lastName) {
            errors.push('Last name is required');
        }

        // Validate gender
        const gender = $('#gender').val();
        if (!gender) {
            errors.push('Gender is required');
        }

        // Validate email if provided
        const email = $('#email').val().trim();
        if (email) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                errors.push('Please enter a valid email address');
            }
        }

        // Validate guardian names
        $('.guardian-first-name').each(function(index) {
            const guardianFirstName = $(this).val().trim();
            if (!guardianFirstName) {
                errors.push(`Guardian ${index + 1} first name is required`);
            }
        });

        $('.guardian-last-name').each(function(index) {
            const guardianLastName = $(this).val().trim();
            if (!guardianLastName) {
                errors.push(`Guardian ${index + 1} last name is required`);
            }
        });

        return errors;
    }

    // ---------------------------------------------------------------------------
    //  Profile Form Submit
    // ---------------------------------------------------------------------------

    $('#profileForm').on('submit', function (e) {
        e.preventDefault();

        // Validate form
        const validationErrors = validateForm();
        if (validationErrors.length > 0) {
            let errorHtml = '<ul class="text-left mb-0">';
            validationErrors.forEach(error => {
                errorHtml += `<li>${error}</li>`;
            });
            errorHtml += '</ul>';

            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                html: errorHtml
            });
            return;
        }

        const formData = new FormData(this);

        Swal.fire({
            title: 'Processing...',
            text: 'Updating student profile',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: API_ROUTES.updateStudentProfile,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Profile updated successfully',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = API_ROUTES.redirectBack;
                    });
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = '<ul class="text-left mb-0">';

                    $.each(errors, function (key, value) {
                        errorMessage += '<li>' + value[0] + '</li>';
                    });
                    errorMessage += '</ul>';
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: errorMessage
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Something went wrong. Please try again.'
                    });
                }
            }
        });
    });

});