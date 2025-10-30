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
            url: `/profile/teacher/${id}/update`,
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
                        // window.location.href = `/profile/teacher/${id}`;
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

    // Change password form submission
    $('#changePasswordForm').on('submit', function (e) {
        e.preventDefault();

        // Validate password match
        const newPassword = $('#newPassword').val();
        const confirmPassword = $('#confirmPassword').val();

        if (newPassword !== confirmPassword) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'New passwords do not match'
            });
            return;
        }

        const teacherId = $('#teacherId').val();
        const formData = {
            current_password: $('#currentPassword').val(),
            new_password: newPassword,
            new_password_confirmation: confirmPassword,
            _token: $('input[name="_token"]').val()
        };

        // Disable submit button
        const $submitBtn = $('#changePasswordBtn');
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Changing...');

        $.ajax({
            url: `/profile/teacher/${teacherId}/change-password`,
            type: 'POST',
            data: formData,
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Close modal and reset form
                        $('#changePasswordModal').modal('hide');
                        $('#changePasswordForm')[0].reset();
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
                let errorMessage = 'Failed to change password';
                
                if (xhr.status === 422) {
                    // Validation errors or incorrect password
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
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
                        return;
                    }
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage
                });
            },
            complete: function () {
                // Re-enable submit button
                $submitBtn.prop('disabled', false).html('<i class="fas fa-key"></i> Change Password');
            }
        });
    });

    // Reset password form when modal is closed
    $('#changePasswordModal').on('hidden.bs.modal', function () {
        $('#changePasswordForm')[0].reset();
    });
});