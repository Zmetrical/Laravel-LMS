console.log("login_guardian");

$(document).ready(function() {
    // Handle form submission
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        // Disable submit button and show loading state
        const $submitBtn = $('#submitBtn');
        const originalText = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Signing in...');
        
        // Get form data
        const formData = {
            email: $('#email').val(),
            password: $('#password').val(),
            remember: $('#remember').is(':checked') ? 1 : 0,
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        // Send AJAX request
        $.ajax({
            url: '/guardian/auth',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Show success message with SweetAlert2
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Login successful! Redirecting...',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.href = response.redirect || '/guardian';
                    });
                } else {
                    // Show error message
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: response.message || 'Login failed. Please try again.'
                    });
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                // Re-enable submit button
                $submitBtn.prop('disabled', false).html(originalText);
                
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    
                    $.each(errors, function(field, messages) {
                        const $input = $('#' + field);
                        $input.addClass('is-invalid');
                        $input.after('<span class="invalid-feedback d-block">' + messages[0] + '</span>');
                    });
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Please correct the errors below.'
                    });
                } else if (xhr.status === 401) {
                    // Authentication failed
                    const message = xhr.responseJSON?.message || 'Invalid email or password.';
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Authentication Failed',
                        text: message
                    });
                } else {
                    // Other errors
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.'
                    });
                }
            }
        });
    });
    
    // Clear error on input focus
    $('.form-control').on('focus', function() {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
    });
});