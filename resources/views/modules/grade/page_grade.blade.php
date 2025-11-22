@extends('modules.class.main', [
    'activeTab' => 'grades', 
    'userType' => $userType, 
    'class' => $class])

@section('tab-content')
<div class="container-fluid p-0 m-0">
    <!-- Grades Card -->
    <!-- Loading State -->
    <div id="loadingState" class="text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Loading...</span>
        </div>
        <p class="mt-2 mb-0">Loading your grades...</p>
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="text-center py-5" style="display: none;">
        <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
        <h4>No Grades Yet</h4>
        <p class="text-muted">Your grades will appear here once you complete quizzes.</p>
    </div>

    <!-- Grades Container -->
    <div id="gradeTableContainer" style="display: none;">
        <!-- Mobile View -->
        <div class="d-md-none" id="mobileGradesContainer">
            <!-- Mobile cards will be inserted here -->
        </div>

        <!-- Desktop View -->
        <div class="d-none d-md-block table-responsive">
            <table class="table table-striped table-hover mb-0" id="gradeTable">
                <thead>
                    <tr>
                        <th>Quiz Title</th>
                        <th class="text-center">Score</th>
                        <th class="text-center">Percentage</th>
                        <th class="text-center">Passing Score</th>
                        <th class="text-center">Date Submitted</th>
                    </tr>
                </thead>
                <tbody id="gradeTableBody">
                    <!-- Rows will be added dynamically -->
                </tbody>
            </table>
        </div>
    </div>
</div>


@endsection

@section('page-scripts')
<script>
    // API Routes Configuration
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