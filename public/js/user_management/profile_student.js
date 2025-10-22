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
    }
    else {
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
    //  AJAX Functions
    // ---------------------------------------------------------------------------

    $('#profileForm').on('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = $('#submit_update');

        const id = $(this).data('student-id');

        const fileInput = document.getElementById('profileImageInput');
        if (fileInput && fileInput.files.length > 0) {
            console.log('File selected:', fileInput.files[0]);
            formData.append('profile_image', fileInput.files[0]);
        } else {
            console.log('No file selected');
        }
        if (!id) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Student ID not found'
            });
            return;
        }
        // Disable button and show loading
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: `/profile/student/${id}`,
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
                        // === Redirect  ===
                        // window.location.href = `/profile/student/${id}`;
                    });
                }
            },
            error: function (xhr) {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Changes');

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


// Data for enrolled classes table
const enrolledClasses = [
    { code: "MATH-101", name: "Business Mathematics" },
    { code: "ENG-101", name: "English for Academic Purposes" },
    { code: "SCI-101", name: "General Chemistry" },
    { code: "SOC-101", name: "Philippine Politics and Governance" },
    { code: "PE-101", name: "Physical Education" }
];


// Populate Enrolled Classes Table
function populateEnrolledClasses() {
    const tbody = document.getElementById('enrolledClassesBody');
    tbody.innerHTML = '';

    enrolledClasses.forEach(cls => {
        const row = document.createElement('tr');

        const codeCell = document.createElement('td');
        codeCell.textContent = cls.code;

        const nameCell = document.createElement('td');
        nameCell.textContent = cls.name;

        row.appendChild(codeCell);
        row.appendChild(nameCell);
        tbody.appendChild(row);
    });
}

$(document).ready(function () {
    populateEnrolledClasses();
})