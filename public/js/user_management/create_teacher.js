console.log("insert teacher");


$(document).ready(function () {

    // ---------------------------------------------------------------------------
    //  AJAX Functions
    // ---------------------------------------------------------------------------

    // Form submission
    $('#insert_teacher').on('submit', function (e) {
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
                text: 'Please fill in the required fields'
            });
            return false;
        }

        // Show loading
        Swal.fire({
            title: 'Processing...',
            text: 'Creating teacher record',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '/user_management/insert_teacher',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Redirect to appropriate page
                        // window.location.href = '/user_management/create_teacher';
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