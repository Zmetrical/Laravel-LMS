@extends('modules.class.main', [
    'activeTab' => 'grades', 
    'userType' => $userType, 
    'class' => $class])

@section('tab-content')

<div class="container-fluid p-0">
    
    <!-- Loading State -->
    <div id="loadingState" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <p class="mt-3 mb-0 text-muted">Loading your grades...</p>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="text-center py-5" style="display: none;">
        <i class="fas fa-graduation-cap fa-4x text-muted mb-3"></i>
        <h4 class="text-secondary">No Grades Available</h4>
        <p class="text-muted">Your grades will appear here once you complete quizzes.</p>
    </div>

    <!-- Main Content -->
    <div id="mainContent" style="display: none;">
        <!-- Quarters Container -->
        <div id="quartersContainer"></div>
    </div>
</div>

@endsection

@section('page-scripts')
<script>
    const API_ROUTES = {
        getGrades: "{{ route('student.class.grades.student', ['classId' => $class->id, 'studentNumber' => $studentNumber]) }}",
        viewQuiz: "{{ route('student.class.quiz.view', ['classId' => $class->id, 'lessonId' => ':lessonId', 'quizId' => ':quizId']) }}",
        classId: {{ $class->id }},
        studentNumber: "{{ $studentNumber }}"
    };
</script>
@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection