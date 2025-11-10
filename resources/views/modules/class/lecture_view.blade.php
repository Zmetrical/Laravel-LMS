@extends('modules.class.main', [
    'activeTab' => 'lessons', 
    'userType' => 'student', 
    'class' => $class
])

@section('tab-content')
<div class="row">
    <div class="col-md-9">
        <!-- Loading State -->
        <div id="loadingState" class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
            <p class="mt-3 text-muted">Loading lecture content...</p>
        </div>

        <!-- Lecture Content -->
        <div id="lectureContent" style="display: none;">
            <!-- Breadcrumb -->
            <div class="mb-3">
                <button type="button" id="backToLessons" class="btn btn-default btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to Lessons
                </button>
            </div>

            <!-- Lecture Header -->
            <div class="card card-primary card-outline">
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-book"></i> Lesson: <span id="lessonTitle"></span>
                        </small>
                    </div>
                    <h3 class="mb-0" id="lectureTitle"></h3>
                </div>
            </div>

            <!-- Content Area -->
            <div id="contentArea" class="mt-3">
                <!-- Content will be dynamically loaded here -->
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <!-- Navigation Sidebar -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Navigation
                </h3>
            </div>
            <div class="card-body p-0" id="lectureNavigation">
                <div class="text-center py-3 text-muted">
                    <small>Loading navigation...</small>
                </div>
            </div>
        </div>

        <!-- Study Tips Card -->
        <div class="card card-outline card-info mt-3">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-lightbulb"></i> Study Tips
                </h3>
            </div>
            <div class="card-body">
                <ul class="mb-0 pl-3">
                    <li><small>Take notes while studying</small></li>
                    <li><small>Review material regularly</small></li>
                    <li><small>Complete all lectures before quizzes</small></li>
                    <li><small>Ask questions if unclear</small></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-styles')
<style>
.lecture-text-content {
    font-size: 1.1rem;
    line-height: 1.8;
    color: #333;
}

.lecture-text-content p {
    margin-bottom: 1rem;
}

.list-group-item.active {
    background-color: #007bff;
    border-color: #007bff;
}

.embed-responsive {
    position: relative;
    display: block;
    width: 100%;
    padding: 0;
    overflow: hidden;
}

.embed-responsive::before {
    display: block;
    content: "";
}

.embed-responsive .embed-responsive-item,
.embed-responsive iframe,
.embed-responsive embed,
.embed-responsive object,
.embed-responsive video {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
}

.embed-responsive-16by9::before {
    padding-top: 56.25%;
}
</style>
@endsection

@section('page-scripts')
<script>
    const API_ROUTES = {
        getLectureData: "{{ route('student.class.lectures.view.data', ['classId' => $class->id, 'lessonId' => $lessonId, 'lectureId' => $lectureId]) }}",
        downloadFile: "{{ route('student.class.lectures.download', ['classId' => $class->id]) }}",
        backToLessons: "{{ route('student.class.lessons', $class->id) }}"
    };
    
    const CSRF_TOKEN = "{{ csrf_token() }}";
    const BASE_URL = "{{ url('/') }}";
</script>

@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection