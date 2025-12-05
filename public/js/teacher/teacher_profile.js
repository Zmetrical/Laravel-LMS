$(document).ready(function () {
    // Handle expand/collapse button clicks for classes
    $(document).on('click', '.expand-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const classId = $(this).data('class-id');
        const mainRow = $(this).closest('tr');
        const existingDetailRow = mainRow.next('.classes-detail-row');
        const isExpanded = $(this).hasClass('expanded');
        
        if (isExpanded) {
            // Collapse
            $(this).removeClass('expanded');
            existingDetailRow.remove();
        } else {
            // Expand - create and insert detail row
            $(this).addClass('expanded');
            
            // Get sections data from the main row
            const sectionsData = mainRow.data('sections');
            
            // Build sections HTML
            let sectionsHtml = '';
            if (sectionsData) {
                const sections = sectionsData.split(', ');
                sections.forEach(function(section) {
                    sectionsHtml += `<span class="section-badge">${section}</span>`;
                });
            } else {
                sectionsHtml = '<small class="text-muted"><i class="fas fa-info-circle mr-1"></i>No sections assigned</small>';
            }
            
            // Create detail row
            const detailRow = $(`
                <tr class="classes-detail-row">
                    <td colspan="4" class="classes-detail-cell">
                        <div>
                            ${sectionsHtml}
                        </div>
                    </td>
                </tr>
            `);
            
            // Insert after main row
            mainRow.after(detailRow);
        }
    });

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
            url: API_ROUTES.updateTeacherProfile,
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
                        window.location.href = API_ROUTES.redirectBack;
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
                $submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Changes');
            }
        });
    });
});