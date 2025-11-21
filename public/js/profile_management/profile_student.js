$(document).ready(function () {

    // ---------------------------------------------------------------------------
    //  Page Mode
    // ---------------------------------------------------------------------------

    // Get mode from URL
    const pathSegments = window.location.pathname.split('/');
    const isEditMode = pathSegments.includes('edit');

    if (!isEditMode) {
        $('#profileImagePreview').css({
            'width': '200px',
            'height': '200px'
        });

        // Make all editable fields readonly in view mode
        $('[data-editable]').each(function () {
            if ($(this).is('input, textarea')) {
                $(this).prop('readonly', true);
            } else if ($(this).is('select')) {
                $(this).prop('disabled', true);
            } else {
                $(this).hide();
            }
        });
    } else {
        $('#profileImagePreview').css({
            'width': '150px',
            'height': '150px'
        });
    }

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
                    <td colspan="4" class="text-center text-muted">No enrolled classes found</td>
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

    // Load enrolled classes on page load
    loadEnrolledClasses();

    // ---------------------------------------------------------------------------
    //  AJAX Form Submit
    // ---------------------------------------------------------------------------

    $('#profileForm').on('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const id = $(this).data('student-id');

        // Show loading
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
                        text: xhr.responseJSON?.message || 'Something went wrong. Please try again.'
                    });
                }
            }
        });
    });

});