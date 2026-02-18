$(document).ready(function () {
    // Profile image preview
    $('#profileImageInput').on('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#profileImagePreview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

// Toggle password visibility (Admin Type 1 only)
$('#togglePassword').on('click', function() {
    const $btn = $(this);
    const $icon = $btn.find('i');
    const $display = $('#passwordDisplay');
    const isVisible = $btn.data('visible');

    if (!isVisible) {
        // Show password - fetch from server
        $.ajax({
            url: API_ROUTES.getCredentials,
            type: 'GET',
            data: { type: 'password' },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $display.val(response.credential);
                    $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                    $btn.data('visible', true);
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to retrieve password'
                });
            }
        });
    } else {
        // Hide password
        $display.val('••••••••');
        $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        $btn.data('visible', false);
    }
});

// Toggle passcode visibility (Admin Type 1 only)
$('#togglePasscode').on('click', function() {
    const $btn = $(this);
    const $icon = $btn.find('i');
    const $display = $('#passcodeDisplay');
    const isVisible = $btn.data('visible');

    if (!isVisible) {
        // Show passcode - fetch from server
        $.ajax({
            url: API_ROUTES.getCredentials,
            type: 'GET',
            data: { type: 'passcode' },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $display.val(response.credential);
                    $icon.removeClass('fa-eye').addClass('fa-eye-slash');
                    $btn.data('visible', true);
                }
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to retrieve passcode'
                });
            }
        });
    } else {
        // Hide passcode
        $display.val('••••••');
        $icon.removeClass('fa-eye-slash').addClass('fa-eye');
        $btn.data('visible', false);
    }
});

    // Update profile form submission
    $('#profileForm').on('submit', function (e) {
        e.preventDefault();

        const id = $('#teacherId').val();
        const formData = new FormData();

        // Append form fields
        formData.append('first_name', $('#firstName').val());
        formData.append('last_name', $('#lastName').val());
        formData.append('middle_name', $('#middleName').val());
        formData.append('email', $('#email').val());
        formData.append('phone', $('#phone').val());
        formData.append('gender', $('#gender').val());

        // Append profile image if selected
        const profileImage = $('#profileImageInput')[0].files[0];
        if (profileImage) {
            formData.append('profile_image', profileImage);
        }

        // Add CSRF token
        formData.append('_token', $('input[name="_token"]').val());

        // Disable submit button
        const $submitBtn = $('#saveProfileBtn');
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: API_ROUTES.updateTeacherProfile,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Redirect to view mode
                        window.location.href = API_ROUTES.redirectBack;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message
                    });
                }
            },
            error: function (xhr) {
                let errorMessage = 'Failed to update profile';
                
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    let errorList = '<ul class="text-left">';
                    $.each(errors, function (key, value) {
                        errorList += '<li>' + value[0] + '</li>';
                    });
                    errorList += '</ul>';
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: errorList
                    });
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errorMessage
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errorMessage
                    });
                }
            },
            complete: function () {
                // Re-enable submit button
                $submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Changes');
            }
        });
    });
});