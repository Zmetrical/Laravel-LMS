console.log("login_student");

$(document).ready(function() {
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        const $submitBtn = $('#submitBtn');
        const originalText = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Signing in...');
        
        const formData = {
            student_number: $('#student_number').val(),
            password: $('#password').val(),
            remember: $('#remember').is(':checked') ? 1 : 0,
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        $.ajax({
            url: '/student/auth',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Login successful! Redirecting...',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.href = response.redirect || '/student';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: response.message || 'Login failed. Please try again.'
                    });
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                $submitBtn.prop('disabled', false).html(originalText);
                
                if (xhr.status === 422) {
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
                    const message = xhr.responseJSON?.message || 'Invalid student number or password.';
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Authentication Failed',
                        text: message
                    });
                } else if (xhr.status === 403) {
                    const message = xhr.responseJSON?.message || 'Access denied.';
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'Access Denied',
                        text: message,
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.'
                    });
                }
            }
        });
    });
    
    $('.form-control').on('focus', function() {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
    });
});