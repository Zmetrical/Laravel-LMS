$(document).ready(function() {
    // Initialize content type visibility
    toggleContentFields();
    
    // Content type change handler
    $('#contentType').on('change', function() {
        toggleContentFields();
    });
    
    // Custom file input label
    $('#fileUpload').on('change', function() {
        const fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Choose file');
    });
    
    // Form submission
    $('#lectureForm').on('submit', function(e) {
        e.preventDefault();
        submitLecture();
    });
    
    // Delete button
    if (IS_EDIT) {
        $('#deleteLectureBtn').on('click', function() {
            deleteLecture();
        });
    }
});

function toggleContentFields() {
    const contentType = $('#contentType').val();
    
    // Hide all content-specific fields
    $('#textContentGroup').hide();
    $('#videoUrlGroup').hide();
    $('#fileUploadGroup').hide();
    
    // Show relevant field based on content type
    switch(contentType) {
        case 'text':
            $('#textContentGroup').show();
            break;
        case 'video':
            $('#videoUrlGroup').show();
            break;
        case 'pdf':
        case 'file':
            $('#fileUploadGroup').show();
            break;
    }
}

function submitLecture() {
    const formData = new FormData();
    const contentType = $('#contentType').val();
    
    // Basic fields
    formData.append('title', $('#title').val().trim());
    formData.append('content_type', contentType);
    formData.append('order_number', $('#orderNumber').val() || 0);
    formData.append('status', $('#status').is(':checked') ? 1 : 0);
    
    // Add _method for Laravel method spoofing if editing
    if (IS_EDIT) {
        formData.append('_method', 'PUT');
    }
    
    // Content based on type
    if (contentType === 'text') {
        formData.append('content', $('#textContent').val());
    } else if (contentType === 'video') {
        formData.append('content', $('#videoUrl').val().trim());
    } else if (contentType === 'pdf' || contentType === 'file') {
        const fileInput = $('#fileUpload')[0];
        if (fileInput.files.length > 0) {
            formData.append('file', fileInput.files[0]);
        } else if (!IS_EDIT) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing File',
                text: 'Please select a file to upload'
            });
            return;
        }
    }
    
    // Validation
    if (!$('#title').val().trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Information',
            text: 'Please enter a lecture title'
        });
        return;
    }
    
    if (contentType === 'video' && !$('#videoUrl').val().trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Information',
            text: 'Please enter a video URL'
        });
        return;
    }
    
    if (contentType === 'text' && !$('#textContent').val().trim()) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Information',
            text: 'Please enter text content'
        });
        return;
    }
    
    // Disable submit button
    const submitBtn = $('#submitBtn');
    const originalHtml = submitBtn.html();
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    
    $.ajax({
        url: API_ROUTES.submitUrl,
        method: 'POST', // Always use POST for file uploads, Laravel handles _method
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message || (IS_EDIT ? 'Lecture updated successfully' : 'Lecture created successfully'),
                    timer: 2000,
                    showConfirmButton: false
                });
                setTimeout(function() {
                    window.location.href = API_ROUTES.redirectUrl;
                }, 1000);
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Failed to save lecture'
                });
                submitBtn.prop('disabled', false).html(originalHtml);
            }
        },
        error: function(xhr) {
            console.error('Error saving lecture:', xhr);
            let errorMsg = 'Failed to save lecture';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                errorMsg = Object.values(errors).flat().join('\n');
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: errorMsg
            });
            submitBtn.prop('disabled', false).html(originalHtml);
        }
    });
}

function deleteLecture() {
    const title = $('#title').val();
    
    Swal.fire({
        title: 'Delete Lecture?',
        html: `Are you sure you want to delete <strong>"${escapeHtml(title)}"</strong>?<br><br>This lecture will be hidden from students but can be recovered later.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const deleteBtn = $('#deleteLectureBtn');
            const originalHtml = deleteBtn.html();
            deleteBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
            
            $.ajax({
                url: API_ROUTES.deleteUrl,
                method: 'POST', // Use POST for compatibility
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                data: {
                    _method: 'DELETE' // Laravel method spoofing
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: response.message || 'Lecture deleted successfully',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        setTimeout(function() {
                            window.location.href = API_ROUTES.redirectUrl;
                        }, 1000);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to delete lecture'
                        });
                        deleteBtn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function(xhr) {
                    console.error('Error deleting lecture:', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to delete lecture'
                    });
                    deleteBtn.prop('disabled', false).html(originalHtml);
                }
            });
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}