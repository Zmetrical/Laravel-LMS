$(document).ready(function() {
    loadLectureData();
    
    $('#backToLessons').on('click', function() {
        window.location.href = API_ROUTES.backToLessons;
    });
    
    // Mark as complete button handler
    $(document).on('click', '#markCompleteBtn', function() {
        markLectureComplete();
    });
    
    // Download button handler
    $(document).on('click', '#downloadBtn', function() {
        const fileUrl = $(this).data('file-url');
        window.open(fileUrl, '_blank');
    });
});

function loadLectureData() {
    $.ajax({
        url: API_ROUTES.getLectureData,
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        success: function(response) {
            if (response.success) {
                displayLecture(response.data);
                loadProgress();
            } else {
                showError(response.message || 'Failed to load lecture');
            }
        },
        error: function(xhr) {
            console.error('Error loading lecture:', xhr);
            showError('Failed to load lecture content');
        }
    });
}

function loadProgress() {
    $.ajax({
        url: API_ROUTES.getProgress,
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        success: function(response) {
            if (response.success && response.data.is_completed) {
                updateCompletionUI(true, response.data.completed_at);
            }
        },
        error: function(xhr) {
            console.error('Error loading progress:', xhr);
        }
    });
}

function markLectureComplete() {
    const btn = $('#markCompleteBtn');
    const originalHtml = btn.html();
    
    btn.html('<i class="fas fa-spinner fa-spin"></i> Marking...').prop('disabled', true);
    
    $.ajax({
        url: API_ROUTES.markComplete,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF_TOKEN
        },
        success: function(response) {
            if (response.success) {
                updateCompletionUI(true, new Date().toISOString());
                toastr.success('Lecture marked as complete!');
            } else {
                toastr.error(response.message || 'Failed to mark as complete');
                btn.html(originalHtml).prop('disabled', false);
            }
        },
        error: function(xhr) {
            console.error('Error marking complete:', xhr);
            toastr.error('Failed to mark lecture as complete');
            btn.html(originalHtml).prop('disabled', false);
        }
    });
}

function updateCompletionUI(isCompleted, completedAt) {
    if (isCompleted) {
        const date = new Date(completedAt);
        const formattedDate = date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        $('#markCompleteBtn').replaceWith(`
            <button type="button" class="btn btn-success" disabled>
                <i class="fas fa-check-circle"></i> Completed
            </button>
            <small class="text-muted ml-2">
                <i class="far fa-clock"></i> ${escapeHtml(formattedDate)}
            </small>
        `);
    }
}

function displayLecture(data) {
    $('#loadingState').hide();
    $('#lectureContent').show();
    
    $('#lessonTitle').text(data.lesson_title || 'Lesson');
    $('#lectureTitle').text(data.title);
    
    renderContent(data);
    buildNavigation(data.all_lectures, data.lecture_id, data.lesson_id);
}

function renderContent(data) {
    const contentArea = $('#contentArea');
    contentArea.empty();
    
    switch(data.content_type) {
        case 'text':
            renderTextContent(data.content, contentArea);
            break;
        case 'video':
            renderVideoContent(data.content, contentArea);
            break;
        case 'pdf':
            renderPdfContent(data.file_path, data.file_name, contentArea);
            break;
        case 'file':
            renderFileContent(data.file_path, data.file_name, contentArea);
            break;
        default:
            contentArea.html('<div class="alert alert-warning">Unknown content type</div>');
    }
}

function renderTextContent(content, container) {
    if (!content || !content.trim()) {
        container.html('<div class="alert alert-primary">No content available</div>');
        return;
    }
    
    container.html(`
        <div class="card">
            <div class="card-body lecture-text-content">
                ${content}
            </div>
            <div class="card-footer">
                <button type="button" id="markCompleteBtn" class="btn btn-primary">
                    <i class="fas fa-check-circle"></i> Mark as Done
                </button>
            </div>
        </div>
    `);
}

function renderVideoContent(videoUrl, container) {
    if (!videoUrl || !videoUrl.trim()) {
        container.html('<div class="alert alert-warning">No video URL provided</div>');
        return;
    }
    
    const embedUrl = getVideoEmbedUrl(videoUrl);
    
    if (!embedUrl) {
        container.html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> Invalid video URL
            </div>
            <div class="card">
                <div class="card-body">
                    <a href="${escapeHtml(videoUrl)}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> Open Video Link
                    </a>
                </div>
            </div>
        `);
        return;
    }
    
    container.html(`
        <div class="card">
            <div class="card-body p-0">
                <div class="embed-responsive embed-responsive-16by9">
                    <iframe class="embed-responsive-item" 
                            src="${embedUrl}" 
                            allowfullscreen>
                    </iframe>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <a href="${escapeHtml(videoUrl)}" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-external-link-alt"></i> Open in New Tab
                </a>
                <button type="button" id="markCompleteBtn" class="btn btn-primary btn-sm">
                    <i class="fas fa-check-circle"></i> Mark as Done
                </button>
            </div>
        </div>
    `);
}

function renderPdfContent(filePath, fileName, container) {
    if (!filePath || !fileName) {
        container.html('<div class="alert alert-warning">No PDF file available</div>');
        return;
    }
    
    const fileUrl = BASE_URL + '/storage/' + filePath;
    
    container.html(`
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-file-pdf text-danger"></i>
                        <strong>${escapeHtml(fileName)}</strong>
                    </div>
                    <div>
                        <button type="button" id="downloadBtn" class="btn btn-primary btn-sm" data-file-url="${fileUrl}">
                            <i class="fas fa-download"></i> Download PDF
                        </button>
                        <button type="button" id="markCompleteBtn" class="btn btn-success btn-sm ml-2">
                            <i class="fas fa-check-circle"></i> Mark as Done
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <iframe src="${fileUrl}" 
                        style="width: 100%; height: 600px; border: none;">
                    <p>Your browser does not support PDF viewing. 
                       <a href="${fileUrl}" download>Click here to download</a>
                    </p>
                </iframe>
            </div>
        </div>
    `);
}

function renderFileContent(filePath, fileName, container) {
    if (!filePath || !fileName) {
        container.html('<div class="alert alert-warning">No file available</div>');
        return;
    }
    
    const fileUrl = BASE_URL + '/storage/' + filePath;
    const fileExt = fileName.split('.').pop().toLowerCase();
    const fileIcon = getFileIcon(fileExt);
    
    container.html(`
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-${fileIcon} fa-5x text-primary"></i>
                </div>
                <h4 class="mb-3">${escapeHtml(fileName)}</h4>
                <p class="text-muted mb-4">
                    File Type: <span class="badge badge-primary">${fileExt.toUpperCase()}</span>
                </p>
                <button type="button" id="downloadBtn" class="btn btn-primary btn-lg" data-file-url="${fileUrl}">
                    <i class="fas fa-download"></i> Download File
                </button>
            </div>
            <div class="card-footer text-center">
                <button type="button" id="markCompleteBtn" class="btn btn-success">
                    <i class="fas fa-check-circle"></i> Mark as Done
                </button>
            </div>
        </div>
    `);
}

function buildNavigation(lectures, currentLectureId, lessonId) {
    const nav = $('#lectureNavigation');
    
    if (!lectures || lectures.length === 0) {
        nav.html('<div class="p-3 text-center text-muted"><small>No lectures</small></div>');
        return;
    }
    
    let html = '<div class="list-group list-group-flush">';
    
    lectures.forEach((lecture, index) => {
        const isActive = lecture.id == currentLectureId;
        const icon = getContentTypeIcon(lecture.content_type);
        const viewUrl = `${BASE_URL}/student/class/${CLASS_ID}/lesson/${lessonId}/lecture/${lecture.id}`;
        
        html += `
            <a href="${viewUrl}" 
               class="list-group-item list-group-item-action ${isActive ? 'active' : ''}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge ${isActive ? 'badge-light' : 'badge-primary'} mr-2">
                            ${index + 1}
                        </span>
                        <small>${escapeHtml(lecture.title)}</small>
                    </div>
                    <i class="fas fa-${icon}"></i>
                </div>
            </a>
        `;
    });
    
    html += '</div>';
    nav.html(html);
}

function getVideoEmbedUrl(url) {
    const youtubeRegex = /(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/;
    const youtubeMatch = url.match(youtubeRegex);
    
    if (youtubeMatch && youtubeMatch[1]) {
        return `https://www.youtube.com/embed/${youtubeMatch[1]}`;
    }
    
    const vimeoRegex = /(?:vimeo\.com\/)(\d+)/;
    const vimeoMatch = url.match(vimeoRegex);
    
    if (vimeoMatch && vimeoMatch[1]) {
        return `https://player.vimeo.com/video/${vimeoMatch[1]}`;
    }
    
    if (url.includes('youtube.com/embed/') || url.includes('player.vimeo.com/video/')) {
        return url;
    }
    
    return null;
}

function getContentTypeIcon(type) {
    const icons = {
        'video': 'video',
        'pdf': 'file-pdf',
        'file': 'file',
        'text': 'file-alt'
    };
    return icons[type] || 'file-alt';
}

function getFileIcon(extension) {
    const iconMap = {
        'doc': 'file-word',
        'docx': 'file-word',
        'txt': 'file-alt',
        'xls': 'file-excel',
        'xlsx': 'file-excel',
        'csv': 'file-csv',
        'ppt': 'file-powerpoint',
        'pptx': 'file-powerpoint',
        'zip': 'file-archive',
        'rar': 'file-archive',
        '7z': 'file-archive',
        'jpg': 'file-image',
        'jpeg': 'file-image',
        'png': 'file-image',
        'gif': 'file-image',
        'default': 'file'
    };
    return iconMap[extension] || iconMap['default'];
}

function showError(message) {
    $('#loadingState').hide();
    $('#lectureContent').show();
    $('#contentArea').html(`
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Error:</strong> ${escapeHtml(message)}
        </div>
    `);
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
    return text.replace(/[&<>"']/g, m => map[m]);
}