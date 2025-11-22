@extends('modules.class.main', [
    'activeTab' => 'grades', 
    'userType' => $userType, 
    'class' => $class])

@section('tab-content')
<br>
<div class="container-fluid">

    <!-- Grades Table Card -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-graduation-cap mr-2"></i>My Quiz Grades
            </h3>
            <div class="card-tools">
                <button type="button" class="btn btn-sm btn-outline-primary" id="refreshGrades">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
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

            <!-- Table Container -->
            <div class="table-responsive" id="gradeTableContainer" style="display: none;">
                <table class="table table-striped table-hover mb-0" id="gradeTable">
                    <thead>
                        <tr>
                            <th>Lesson</th>
                            <th>Quiz Title</th>
                            <th class="text-center">Score</th>
                            <th class="text-center">Percentage</th>
                            <th class="text-center">Passing Score</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Attempts</th>
                            <th class="text-center">Date Submitted</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="gradeTableBody">
                        <!-- Rows will be added dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Grade Details Modal -->
<div class="modal fade" id="gradeDetailsModal" tabindex="-1" role="dialog" aria-labelledby="gradeDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title" id="gradeDetailsModalLabel">
                    <i class="fas fa-info-circle"></i> Quiz Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted">Quiz Information</h6>
                        <dl class="row">
                            <dt class="col-sm-5">Lesson:</dt>
                            <dd class="col-sm-7" id="detailLesson">-</dd>
                            
                            <dt class="col-sm-5">Quiz:</dt>
                            <dd class="col-sm-7" id="detailQuiz">-</dd>
                            
                            <dt class="col-sm-5">Attempts:</dt>
                            <dd class="col-sm-7" id="detailAttempts">-</dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Performance</h6>
                        <dl class="row">
                            <dt class="col-sm-5">Score:</dt>
                            <dd class="col-sm-7" id="detailScore">-</dd>
                            
                            <dt class="col-sm-5">Percentage:</dt>
                            <dd class="col-sm-7" id="detailPercentage">-</dd>
                            
                            <dt class="col-sm-5">Status:</dt>
                            <dd class="col-sm-7" id="detailStatus">-</dd>
                        </dl>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-muted">Submission Details</h6>
                        <dl class="row">
                            <dt class="col-sm-3">Date Submitted:</dt>
                            <dd class="col-sm-9" id="detailSubmitted">-</dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="viewQuizBtn">
                    <i class="fas fa-external-link-alt"></i> View Quiz
                </button>
            </div>
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