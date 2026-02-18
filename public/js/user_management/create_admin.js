console.log("Create Admin");

$(document).ready(function () {

    // ---------------------------------------------------------------------------
    //  Form Submission
    // ---------------------------------------------------------------------------

    $('#insert_admin').on('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Check if form has data
        let hasData = false;
        for (let pair of formData.entries()) {
            if (pair[1]) {
                hasData = true;
                break;
            }
        }

        if (!hasData) {
            Swal.fire({
                icon: 'warning',
                title: 'No Data',
                text: 'Please fill in all required fields'
            });
            return false;
        }

        // Show loading
        Swal.fire({
            title: 'Processing...',
            text: 'Creating admin account',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: API_ROUTES.insertAdmin,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        html: `
                            <p>${response.message}</p>
                            <hr>
                            <div class="text-left">
                                <p><strong>Admin Details:</strong></p>
                                <p><strong>Name:</strong> ${response.data.admin_name}</p>
                                <p><strong>Email:</strong> ${response.data.email}</p>
                                <p><strong>Default Password:</strong> <code>${response.data.default_password}</code></p>
                            </div>

                        `,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = API_ROUTES.redirectAfterSubmit;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message,
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = '';

                    $.each(errors, function (key, value) {
                        errorMessage += value[0] + '<br>';
                    });

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: errorMessage
                    });
                } else if (xhr.status === 403) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Unauthorized',
                        text: 'You do not have permission to create admins.'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Something went wrong. Please try again.'
                    });
                }
            }
        });
    });
});