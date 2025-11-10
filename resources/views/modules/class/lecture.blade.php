@extends('modules.class.main', [
    'activeTab' => 'lessons', 
    'userType' => 'teacher', 
    'class' => $class
])

@section('tab-content')
<div class="row">
    <div class="col-md-12">
        <!-- Breadcrumb -->
        <div class="mb-3">
            <a href="{{ route('teacher.class.lessons', $class->id) }}" class="btn btn-default btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Lessons
            </a>
        </div>

        <!-- Main Card -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-{{ isset($lecture) ? 'edit' : 'plus-circle' }}"></i>
                    {{ isset($lecture) ? 'Edit Lecture' : 'Add New Lecture' }}
                </h3>
            </div>
            <form id="lectureForm">
                <input type="hidden" id="lectureId" value="{{ $lecture->id ?? '' }}">
                <input type="hidden" id="lessonId" value="{{ $lesson->id }}">
                
                <div class="card-body">
                    <!-- Lesson Info -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-book"></i> Lesson: {{ $lesson->title }}</h5>
                        @if($lesson->description)
                        <p class="mb-0"><small>{{ $lesson->description }}</small></p>
                        @endif
                    </div>

                    <!-- Lecture Title -->
                    <div class="form-group">
                        <label for="title">
                            Lecture Title <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="title" 
                               name="title"
                               value="{{ $lecture->title ?? '' }}"
                               placeholder="e.g., Introduction to Essay Structure"
                               required>
                        <small class="form-text text-muted">
                            Enter a clear and descriptive title for this lecture
                        </small>
                    </div>

                    <!-- Content Type -->
                    <div class="form-group">
                        <label for="contentType">
                            Content Type <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" id="contentType" name="content_type" required>
                            <option value="text" {{ (isset($lecture) && $lecture->content_type === 'text') ? 'selected' : '' }}>
                                Text Content
                            </option>
                            <option value="video" {{ (isset($lecture) && $lecture->content_type === 'video') ? 'selected' : '' }}>
                                Video (YouTube/Vimeo URL)
                            </option>
                            <option value="pdf" {{ (isset($lecture) && $lecture->content_type === 'pdf') ? 'selected' : '' }}>
                                PDF Document
                            </option>
                            <option value="file" {{ (isset($lecture) && $lecture->content_type === 'file') ? 'selected' : '' }}>
                                Other File
                            </option>
                        </select>
                    </div>

                    <!-- Text Content (shown when content_type is text) -->
                    <div class="form-group" id="textContentGroup" style="display: none;">
                        <label for="textContent">Text Content</label>
                        <textarea class="form-control" 
                                  id="textContent" 
                                  name="content" 
                                  rows="10"
                                  placeholder="Enter your lecture content here...">{{ isset($lecture) && $lecture->content_type === 'text' ? $lecture->content : '' }}</textarea>
                        <small class="form-text text-muted">
                            You can format this text with markdown or HTML
                        </small>
                    </div>

                    <!-- Video URL (shown when content_type is video) -->
                    <div class="form-group" id="videoUrlGroup" style="display: none;">
                        <label for="videoUrl">Video URL</label>
                        <input type="url" 
                               class="form-control" 
                               id="videoUrl" 
                               name="video_url"
                               value="{{ isset($lecture) && $lecture->content_type === 'video' ? $lecture->content : '' }}"
                               placeholder="https://www.youtube.com/watch?v=...">
                        <small class="form-text text-muted">
                            Paste a YouTube or Vimeo URL
                        </small>
                    </div>

                    <!-- File Upload (shown when content_type is pdf or file) -->
                    <div class="form-group" id="fileUploadGroup" style="display: none;">
                        <label for="fileUpload">Upload File</label>
                        
                        @if(isset($lecture) && $lecture->file_path)
                        <div class="alert alert-success mb-2">
                            <i class="fas fa-file"></i> Current file: 
                            <strong>{{ basename($lecture->file_path) }}</strong>
                            <a href="{{ asset('storage/' . $lecture->file_path) }}" 
                               target="_blank" 
                               class="btn btn-sm btn-outline-primary ml-2">
                                <i class="fas fa-download"></i> Download
                            </a>
                        </div>
                        <small class="form-text text-muted mb-2">
                            Upload a new file to replace the existing one (optional)
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
                            Max file size: 10MB. Accepted formats: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, ZIP
                        </small>
                    </div>

                    <!-- Order Number -->
                    <div class="form-group">
                        <label for="orderNumber">
                            Display Order
                        </label>
                        <input type="number" 
                               class="form-control" 
                               id="orderNumber" 
                               name="order_number"
                               value="{{ $lecture->order_number ?? 0 }}"
                               min="0">
                        <small class="form-text text-muted">
                            Lower numbers appear first (0 = first)
                        </small>
                    </div>

                    <!-- Status -->
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" 
                                   class="custom-control-input" 
                                   id="status" 
                                   name="status"
                                   {{ !isset($lecture) || $lecture->status ? 'checked' : '' }}>
                            <label class="custom-control-label" for="status">
                                Active (visible to students)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <div>
                            @if(isset($lecture))
                            <button type="button" class="btn btn-danger" id="deleteLectureBtn">
                                <i class="fas fa-trash-alt"></i> Delete Lecture
                            </button>
                            @endif
                        </div>
                        <div>
                            <a href="{{ route('teacher.class.lessons', $class->id) }}" class="btn btn-default">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> {{ isset($lecture) ? 'Update' : 'Create' }} Lecture
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
    const API_ROUTES = {
        @if(isset($lecture))
            submitUrl: "{{ route('teacher.class.lectures.update', ['classId' => $class->id, 'lessonId' => $lesson->id, 'lectureId' => $lecture->id]) }}",
            deleteUrl: "{{ route('teacher.class.lectures.delete', ['classId' => $class->id, 'lessonId' => $lesson->id, 'lectureId' => $lecture->id]) }}",
        @else
            submitUrl: "{{ route('teacher.class.lectures.store', ['classId' => $class->id, 'lessonId' => $lesson->id]) }}",
        @endif
        redirectUrl: "{{ route('teacher.class.lessons', $class->id) }}"
    };
    
    const IS_EDIT = {{ isset($lecture) ? 'true' : 'false' }};
    const CSRF_TOKEN = "{{ csrf_token() }}";
</script>

@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection