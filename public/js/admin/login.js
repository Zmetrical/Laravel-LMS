console.log("login_admin");

$(document).ready(function () {
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    $('#loginForm').on('submit', function (e) {
        e.preventDefault();

        const $submitBtn = $('#submitBtn');
        const originalText = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Signing in...');

        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        const formData = {
            email: $('#email').val().trim(),
            password: $('#password').val(),
            remember: $('#remember').is(':checked') ? 1 : 0,
            _token: csrfToken
        };

        $.ajax({
            url: '/admin/auth',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false,
                        timerProgressBar: true
                    }).then(() => {
                        window.location.href = response.redirect;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: response.message || 'Invalid email or password.'
                    });
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function (xhr) {
                $submitBtn.prop('disabled', false).html(originalText);

                if (xhr.status === 401) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Authentication Failed',
                        text: xhr.responseJSON?.message || 'Invalid email or password.',
                        confirmButtonColor: '#6c757d'
                    });

                } else if (xhr.status === 422) {
                    const errors = xhr.responseJSON?.errors;

                    if (errors) {
                        $.each(errors, function (field, messages) {
                            const $input = $(`#${field}`);
                            $input.addClass('is-invalid');
                            $input.after(`<span class="invalid-feedback d-block">${messages[0]}</span>`);
                        });
                    }

                    Swal.fire({
                        icon: 'warning',
                        title: 'Validation Error',
                        text: 'Please check your input and try again.',
                        confirmButtonColor: '#6c757d'
                    });

                } else if (xhr.status === 403) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Access Denied',
                        text: xhr.responseJSON?.message || 'Your account has been deactivated. Please contact the Super Admin.',
                        confirmButtonColor: '#6c757d'
                    });

                } else if (xhr.status === 500) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Please contact the administrator.',
                        confirmButtonColor: '#6c757d'
                    });

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.',
                        confirmButtonColor: '#6c757d'
                    });
                }
            }
        });
    });

    $('.form-control').on('input', function () {
        $(this).removeClass('is-invalid');
        $(this).next('.invalid-feedback').remove();
    });

    $('#email, #password').on('keypress', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#loginForm').submit();
        }
    });
});