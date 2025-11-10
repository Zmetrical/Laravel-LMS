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
            toastr.warning('Please select a file to upload');
            return;
        }
    }
    
    // Validation
    if (!$('#title').val().trim()) {
        toastr.warning('Please enter a lecture title');
        return;
    }
    
    if (contentType === 'video' && !$('#videoUrl').val().trim()) {
        toastr.warning('Please enter a video URL');
        return;
    }
    
    if (contentType === 'text' && !$('#textContent').val().trim()) {
        toastr.warning('Please enter text content');
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
                toastr.success(response.message || (IS_EDIT ? 'Lecture updated successfully' : 'Lecture created successfully'));
                setTimeout(function() {
                    window.location.href = API_ROUTES.redirectUrl;
                }, 1000);
            } else {
                toastr.error(response.message || 'Failed to save lecture');
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
                errorMsg = Object.values(errors).flat().join('<br>');
            }
            
            toastr.error(errorMsg);
            submitBtn.prop('disabled', false).html(originalHtml);
        }
    });
}

function deleteLecture() {
    const title = $('#title').val();
    
    if (!confirm(`Are you sure you want to delete "${title}"?\n\nThis lecture will be hidden from students but can be recovered later.\n\nContinue?`)) {
        return;
    }
    
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
                toastr.success(response.message || 'Lecture deleted successfully');
                setTimeout(function() {
                    window.location.href = API_ROUTES.redirectUrl;
                }, 1000);
            } else {
                toastr.error(response.message || 'Failed to delete lecture');
                deleteBtn.prop('disabled', false).html(originalHtml);
            }
        },
        error: function(xhr) {
            console.error('Error deleting lecture:', xhr);
            toastr.error('Failed to delete lecture');
            deleteBtn.prop('disabled', false).html(originalHtml);
        }
    });
}