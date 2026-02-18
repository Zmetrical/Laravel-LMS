@extends('modules.class.main', [
    'activeTab' => 'lessons', 
    'userType' => 'teacher', 
    'class' => $class
])

@section('page-styles')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
<style>
.card-body {
    padding: 1.5rem;
}

.form-control-lg {
    font-size: 1.25rem;
    font-weight: 500;
}

#textContent {
    font-family: 'Courier New', monospace;
    font-size: 0.95rem;
}

.custom-file-label::after {
    content: "Browse";
}

.bg-light {
    background-color: #f8f9fa !important;
}
</style>
@endsection

@section('tab-content')
<div class="row">
    <!-- Main Content Area -->
    <div class="col-md-9">
        <!-- Breadcrumb -->
        <div class="mb-3">
            <a href="{{ route('teacher.class.lessons', $class->id) }}" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Lessons
            </a>
        </div>

        <!-- Lesson Context Card -->
        <div class="card card-primary mb-3">
            <div class="card-body">
                <div class="mb-2">
                    <small class="text-muted">
                        <i class="fas fa-book"></i> Lesson
                    </small>
                </div>
                <h4 class="mb-1">{{ $lesson->title }}</h4>
                @if($lesson->description)
                <p class="text-muted mb-0"><small>{{ $lesson->description }}</small></p>
                @endif
            </div>
        </div>

        <!-- Main Form Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-{{ isset($lecture) ? 'edit' : 'plus-circle' }}"></i>
                    {{ isset($lecture) ? 'Edit Lecture' : 'New Lecture' }}
                </h3>
            </div>
            
            <form id="lectureForm">
                <input type="hidden" id="lectureId" value="{{ $lecture->id ?? '' }}">
                <input type="hidden" id="lessonId" value="{{ $lesson->id }}">
                
                <div class="card-body">
                    <!-- Lecture Title -->
                    <div class="form-group">
                        <label for="title">
                            Lecture Title <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="title" 
                               name="title"
                               value="{{ $lecture->title ?? '' }}"
                               placeholder="Enter lecture title"
                               required>
                    </div>

                    <!-- Content Type -->
                    <div class="form-group">
                        <label for="contentType">
                            Content Type <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" id="contentType" name="content_type" required>
                            <option value="text" {{ (isset($lecture) && $lecture->content_type === 'text') ? 'selected' : '' }}>
                                <i class="fas fa-file-alt"></i> Text Content
                            </option>
                            <option value="video" {{ (isset($lecture) && $lecture->content_type === 'video') ? 'selected' : '' }}>
                                <i class="fas fa-video"></i> Video (URL)
                            </option>
                            <option value="pdf" {{ (isset($lecture) && $lecture->content_type === 'pdf') ? 'selected' : '' }}>
                                <i class="fas fa-file-pdf"></i> PDF Document
                            </option>
                            <option value="file" {{ (isset($lecture) && $lecture->content_type === 'file') ? 'selected' : '' }}>
                                <i class="fas fa-file"></i> Other File
                            </option>
                        </select>
                    </div>

                    <hr class="my-4">

                    <!-- Text Content -->
                    <div class="form-group" id="textContentGroup" style="display: none;">
                        <label for="textContent">
                            <i class="fas fa-align-left"></i> Text Content
                        </label>
                        <textarea class="form-control" 
                                  id="textContent" 
                                  name="content" 
                                  rows="15"
                                  placeholder="Enter your lecture content here...">{{ isset($lecture) && $lecture->content_type === 'text' ? $lecture->content : '' }}</textarea>
                    </div>

                    <!-- Video URL -->
                    <div class="form-group" id="videoUrlGroup" style="display: none;">
                        <label for="videoUrl">
                            <i class="fas fa-video"></i> Video URL
                        </label>
                        <input type="url" 
                               class="form-control" 
                               id="videoUrl" 
                               name="video_url"
                               value="{{ isset($lecture) && $lecture->content_type === 'video' ? $lecture->content : '' }}"
                               placeholder="https://www.youtube.com/watch?v=...">
                    </div>

                    <!-- File Upload -->
                    <div class="form-group" id="fileUploadGroup" style="display: none;">
                        <label for="fileUpload">
                            <i class="fas fa-upload"></i> Upload File
                        </label>
                        
                        @if(isset($lecture) && $lecture->file_path)
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-file text-primary"></i>
                                        <strong>{{ basename($lecture->file_path) }}</strong>
                                    </div>
                                    <a href="{{ asset('storage/' . $lecture->file_path) }}" 
                                       target="_blank" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                        <small class="form-text text-muted mb-2">
                            Upload a new file to replace the current one (optional)
                        </small>
                        @endif
                        
                        <div class="custom-file">
                            <input type="file" 
                                   class="custom-file-input" 
                                   id="fileUpload" 
                                   name="file"
                                   accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip">
                            <label class="custom-file-label" for="fileUpload">Choose file</label>
                        </div>
                        <small class="form-text text-muted">
                            Max 10MB • PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, ZIP
                        </small>
                    </div>
                </div>

                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            @if(isset($lecture))
                            <button type="button" class="btn btn-outline-danger" id="deleteLectureBtn">
                                <i class="fas fa-trash-alt"></i> Delete
                            </button>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('teacher.class.lessons', $class->id) }}" class="btn btn-default">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> {{ isset($lecture) ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Settings Sidebar -->
    <div class="col-md-3">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-cog"></i> Settings
                </h3>
            </div>
            <div class="card-body">
                <!-- Display Order -->
                <div class="form-group">
                    <label for="orderNumber">
                        <i class="fas fa-sort"></i> Display Order
                    </label>
                    <input type="number" 
                           class="form-control" 
                           id="orderNumber" 
                           name="order_number"
                           value="{{ $lecture->order_number ?? 0 }}"
                           min="0">
                </div>

                <hr>

                <!-- Status -->
                <div class="form-group mb-0">
                    <label class="d-block mb-3">
                        <i class="fas fa-eye"></i> Visibility
                    </label>
                    <div class="custom-control custom-switch">
                        <input type="checkbox" 
                               class="custom-control-input" 
                               id="status" 
                               name="status"
                               {{ !isset($lecture) || $lecture->status ? 'checked' : '' }}>
                        <label class="custom-control-label" for="status">
                            <span id="statusLabel">{{ !isset($lecture) || $lecture->status ? 'Visible to students' : 'Hidden from students' }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('page-scripts')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    // Lecture-specific constants
    const API_ROUTES = {
        @if(isset($lecture))
            submitUrl: "{{ route('teacher.class.lecture.update', ['classId' => $class->id, 'lessonId' => $lesson->id, 'lectureId' => $lecture->id]) }}",
            deleteUrl: "{{ route('teacher.class.lecture.delete', ['classId' => $class->id, 'lessonId' => $lesson->id, 'lectureId' => $lecture->id]) }}",
        @else
            submitUrl: "{{ route('teacher.class.lecture.store', ['classId' => $class->id, 'lessonId' => $lesson->id]) }}",
        @endif
        redirectUrl: "{{ route('teacher.class.lessons', $class->id) }}"
    };
    
    const IS_EDIT = {{ isset($lecture) ? 'true' : 'false' }};
</script>

@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif

<script>
$(document).ready(function() {
    $('#status').on('change', function() {
        const label = $(this).is(':checked') ? 'Visible to students' : 'Hidden from students';
        $('#statusLabel').text(label);
    });
});
</script>
@endsection