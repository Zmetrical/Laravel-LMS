@extends('modules.class.main', [
    'activeTab' => 'lessons', 
    'userType' => $userType, 
    'class' => $class])

@section('page-styles')
<style>
    .lesson-nav-link.active {
        background-color: #007bff !important;
        color: white !important;
    }
    .lesson-nav-link {
        cursor: pointer;
    }
</style>

@endsection

@section('tab-content')
<div class="row">
    <!-- Main Content -->
    <div class="col-lg-9">
        @if($userType === 'teacher')
        <!-- Action Bar (Teacher Only) -->
        <div class="row mb-3">
            <div class="col-md-12">
                <button class="btn btn-primary" data-toggle="modal" data-target="#addLessonModal">
                    <i class="fas fa-plus-circle"></i> Create New Lesson
                </button>
            </div>
        </div>
        @endif

        <!-- Loading State -->
        <div id="loadingState" class="text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading lessons...</p>
        </div>

        <!-- Empty State -->
        <div id="emptyState" style="display: none;">
            <div class="callout callout-info">
                <h5><i class="fas fa-info-circle"></i> No Lessons Yet</h5>
                <p>
                    @if($userType === 'teacher')
                        Get started by creating your first lesson using the "Create New Lesson" button above.
                    @else
                        No lessons have been added to this class yet.
                    @endif
                </p>
            </div>
        </div>

        <!-- Lessons List -->
        <div id="lessonsContainer"></div>
    </div>

    <!-- Sidebar Navigation -->
    <div class="col-lg-3">
        <div class="card card-primary card-outline sticky-top">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Quick Navigation
                </h3>
            </div>
            <div class="card-body p-0" id="lessonNavigation">
                <div class="p-3 text-center text-muted">
                    <small>Loading...</small>
                </div>
            </div>
        </div>
    </div>
</div>

@if($userType === 'teacher')
<!-- Add Lesson Modal -->
<div class="modal fade" id="addLessonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-plus-circle"></i> Create New Lesson
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="lessonForm">
                    <div class="form-group">
                        <label for="lessonTitle">
                            Lesson Title <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="lessonTitle" 
                               placeholder="e.g., Introduction to Academic Writing" required>
                        <small class="form-text text-muted">Enter a clear and descriptive title for this lesson</small>
                    </div>
                    <div class="form-group">
                        <label for="lessonDescription">Description (Optional)</label>
                        <textarea class="form-control" id="lessonDescription" rows="3" 
                                  placeholder="Brief description of what this lesson covers..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveLessonBtn">
                    <i class="fas fa-save"></i> Create Lesson
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Lesson Modal -->
<div class="modal fade" id="editLessonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-edit"></i> Edit Lesson
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editLessonForm">
                    <input type="hidden" id="editLessonId">
                    <div class="form-group">
                        <label for="editLessonTitle">
                            Lesson Title <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="editLessonTitle" 
                               placeholder="Enter lesson title" required>
                    </div>
                    <div class="form-group">
                        <label for="editLessonDescription">Description (Optional)</label>
                        <textarea class="form-control" id="editLessonDescription" rows="3" 
                                  placeholder="Brief description..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-danger" id="deleteLessonBtn">
                    <i class="fas fa-trash-alt"></i> Delete Lesson
                </button>
                <div>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="updateLessonBtn">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('page-scripts')
<script>
    const API_ROUTES = {
        getLessons: "{{ route($userType === 'teacher' ? 'teacher.class.lessons.list' : 'student.class.lessons.list', $class->id ?? 0) }}",
        
        @if($userType === 'teacher')
        // Lesson
            createLesson: "{{ route('teacher.class.lessons.store', $class->id ?? 0) }}",
            updateLesson: "{{ route('teacher.class.lessons.update', ['classId' => $class->id ?? 0, 'lessonId' => ':lessonId']) }}",
            deleteLesson: "{{ route('teacher.class.lessons.delete', ['classId' => $class->id ?? 0, 'lessonId' => ':lessonId']) }}",
        // Lecture 
            createLecture: "{{ route('teacher.class.lectures.create', ['classId' => $class->id ?? 0, 'lessonId' => ':lessonId']) }}",
            editLecture: "{{ route('teacher.class.lectures.edit', ['classId' => $class->id ?? 0, 'lessonId' => ':lessonId', 'lectureId' => ':lectureId']) }}",
        // Quiz
            createQuiz: "{{ route('teacher.class.quiz.create', ['classId' => $class->id ?? 0, 'lessonId' => ':lessonId']) }}",
            editQuiz: "{{ route('teacher.class.quiz.edit', ['classId' => $class->id ?? 0, 'lessonId' => ':lessonId', 'quizId' => ':quizId']) }}"
        
        @else
            viewLecture: "{{ route('student.class.lectures.view', ['classId' => $class->id ?? 0, 'lessonId' => ':lessonId', 'lectureId' => ':lectureId']) }}",
            viewQuiz: "{{ route('student.class.quiz.view', ['classId' => $class->id, 'lessonId' => ':lessonId', 'quizId' => ':quizId']) }}"
        @endif
    };
    
</script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection