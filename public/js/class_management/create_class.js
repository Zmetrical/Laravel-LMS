console.log("create_class");

$(document).ready(function() {
    // Validate weight percentages
    function validateWeights() {
        const ww = parseFloat($('#ww_perc').val()) || 0;
        const pt = parseFloat($('#pt_perc').val()) || 0;
        const qa = parseFloat($('#qa_perce').val()) || 0;
        const total = ww + pt + qa;

        const alertDiv = $('#weightAlert');
        const statusSpan = $('#weightStatus');
        const messageSpan = $('#weightMessage');

        if (total === 100) {
            alertDiv.removeClass('alert-danger alert-warning').addClass('alert-success').show();
            statusSpan.text('Perfect!');
            messageSpan.text(' Total weight is 100%');
            return true;
        } else if (total < 100) {
            alertDiv.removeClass('alert-success alert-danger').addClass('alert-warning').show();
            statusSpan.text('Warning:');
            messageSpan.text(' Total weight is ' + total + '%. Need ' + (100 - total) + '% more.');
            return false;
        } else {
            alertDiv.removeClass('alert-success alert-warning').addClass('alert-danger').show();
            statusSpan.text('Error:');
            messageSpan.text(' Total weight is ' + total + '%. Exceeds 100% by ' + (total - 100) + '%.');
            return false;
        }
    }

    // Real-time validation on input
    $('.weight-input').on('input', function() {
        validateWeights();
    });

    // Initial validation
    validateWeights();

    // Form submission
    $('#insert_class').on('submit', function(e) {
        e.preventDefault();

        // Validate weights before submission
        if (!validateWeights()) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Weights',
                text: 'Grade weight distribution must total exactly 100%'
            });
            return false;
        }

        const formData = new FormData(this);

        // Check if form has required data
        if (!formData.get('class_code') || !formData.get('class_name')) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Data',
                text: 'Please fill in all required fields'
            });
            return false;
        }

        // Show loading
        Swal.fire({
            title: 'Processing...',
            text: 'Creating class record',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: '/class_management/insert_class',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Redirect or reset form
                        $('#insert_class')[0].reset();
                        validateWeights();
                        // window.location.href = '/class_management/view_classes';
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
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = '';

                    $.each(errors, function(key, value) {
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