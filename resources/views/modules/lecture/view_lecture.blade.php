@extends('modules.class.main', [
    'activeTab' => 'lessons', 
    'userType' => 'student', 
    'class' => $class
])

@section('page-styles')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
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

#markCompleteContainer {
    min-width: 150px;
}
</style>
@endsection

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
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-book"></i> Lesson: <span id="lessonTitle"></span>
                                </small>
                            </div>
                            <h3 class="mb-0" id="lectureTitle"></h3>
                        </div>
                        <div id="markCompleteContainer">
                            <button type="button" id="markCompleteBtn" class="btn btn-primary">
                                <i class="fas fa-check-circle"></i> Mark as Done
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div id="contentArea" class="mt-3">
                <!-- Content will be dynamically loaded here -->
            </div>

            <!-- Download Footer (will be shown only for file-based content) -->
            <div id="downloadFooter" class="card mt-3" style="display: none;">
                <div class="card-footer text-center py-3">
                    <button type="button" id="downloadBtn" class="btn btn-primary btn-lg">
                        <i class="fas fa-download"></i> <span id="downloadBtnText">Download File</span>
                    </button>
                </div>
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
    </div>
</div>
@endsection

@section('page-scripts')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    const API_ROUTES = {
        getLectureData: "{{ route('student.class.lecture.data', ['classId' => $class->id, 'lessonId' => $lessonId, 'lectureId' => $lectureId]) }}",
        getProgress: "{{ route('student.class.lecture.progress', ['classId' => $class->id, 'lessonId' => $lessonId, 'lectureId' => $lectureId]) }}",
        markComplete: "{{ route('student.class.lecture.markComplete', ['classId' => $class->id, 'lessonId' => $lessonId, 'lectureId' => $lectureId]) }}",
        backToLessons: "{{ route('student.class.lessons', $class->id) }}"
    };
    
    const LESSON_ID = {{ $lessonId }};
    const LECTURE_ID = {{ $lectureId }};
    const BASE_URL = "{{ url('/') }}";
</script>

@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection