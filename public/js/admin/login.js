$(document).ready(function() {
    // Get CSRF token
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    // Form submission handler
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        // Disable submit button to prevent double submission
        const $submitBtn = $('#submitBtn');
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('Signing in...');

        // Clear previous validation errors
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        // Collect form data
        const formData = {
            email: $('#email').val().trim(),
            password: $('#password').val(),
            remember: $('#remember').is(':checked') ? 1 : 0,
            _token: csrfToken
        };

        // Send AJAX request
        $.ajax({
            url: '/admin/auth',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        // Redirect to admin dashboard
                        window.location.href = response.redirect;
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: response.message || 'Invalid email or password.'
                    });
                    
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred. Please try again.';
                
                if (xhr.status === 401) {
                    // Unauthorized - invalid credentials
                    const response = xhr.responseJSON;
                    errorMessage = response?.message || 'Invalid email or password.';
                } else if (xhr.status === 422) {
                    // Validation error
                    const errors = xhr.responseJSON?.errors;
                    
                    if (errors) {
                        // Display validation errors
                        $.each(errors, function(field, messages) {
                            const $input = $(`#${field}`);
                            $input.addClass('is-invalid');
                            
                            const errorHtml = `<span class="invalid-feedback d-block">${messages[0]}</span>`;
                            $input.after(errorHtml);
                        });
                        
                        errorMessage = 'Please check your input and try again.';
                    }
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please contact administrator.';
                }

                // Show error message
                Swal.fire({
                    icon: 'error',
                    title: 'Login Failed',
                    text: errorMessage
                });
                
                // Re-enable submit button
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Clear validation error when user starts typing
    $('.form-control').on('input', function() {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
    });

    // Handle Enter key in form fields
    $('#email, #password').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            $('#loginForm').submit();
        }
    });
});