@extends('modules.class.main', [
    'activeTab' => 'lessons', 
    'userType' => $userType, 
    'class' => $class])

@section('page-styles')
<style>
    .quiz-info-card {
        border-left: 4px solid #007bff;
    }
    .attempt-row:hover {
        background-color: #f8f9fa;
    }
    .badge-passed {
        background-color: #28a745;
    }
    .badge-failed {
        background-color: #dc3545;
    }
    .badge-pending {
        background-color: #ffc107;
    }
</style>
@endsection

@section('tab-content')
<!-- Breadcrumb -->
<div class="row mb-3">
    <div class="col-12">
        <a href="{{ route('student.class.lessons', $class->id) }}" class="btn btn-default">
            <i class="fas fa-arrow-left"></i> Back to Lessons
        </a>
    </div>
</div>

<div class="row">
    <!-- Quiz Information -->
    <div class="col-md-8">
        <div class="card quiz-info-card">
            <div class="card-header bg-primary">
                <h3 class="card-title text-white">
                    <i class="fas fa-clipboard-list"></i> {{ $quiz->title }}
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Lesson:</strong> {{ $lesson->title }}</p>
                        <p><strong>Total Questions:</strong> {{ $questionCount }}</p>
                        <p><strong>Total Points:</strong> {{ $totalPoints }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Time Limit:</strong> 
                            @if($quiz->time_limit)
                                {{ $quiz->time_limit }} minutes
                            @else
                                <span class="badge badge-info">No limit</span>
                            @endif
                        </p>
                        <p><strong>Passing Score:</strong> {{ $quiz->passing_score }}%</p>
                        <p><strong>Max Attempts:</strong> {{ $quiz->max_attempts }}</p>
                    </div>
                </div>

                @if($quiz->description)
                <div class="mt-3">
                    <strong>Description:</strong>
                    <p class="text-muted">{{ $quiz->description }}</p>
                </div>
                @endif

                <hr>

                <!-- Attempt Status -->
                <div class="row">
                    <div class="col-md-6">
                        <h5>Your Progress</h5>
                        <p>
                            <strong>Attempts Used:</strong> 
                            {{ $attempts->count() }} / {{ $quiz->max_attempts }}
                        </p>
                        @if($attempts->count() > 0)
                            @php
                                $bestAttempt = $attempts->sortByDesc('score')->first();
                                $percentage = $bestAttempt->total_points > 0 
                                    ? round(($bestAttempt->score / $bestAttempt->total_points) * 100, 2) 
                                    : 0;
                            @endphp
                            <p>
                                <strong>Best Score:</strong> 
                                {{ $bestAttempt->score }} / {{ $bestAttempt->total_points }}
                                ({{ $percentage }}%)
                                @if($percentage >= $quiz->passing_score)
                                    <span class="badge badge-passed">Passed</span>
                                @else
                                    <span class="badge badge-failed">Failed</span>
                                @endif
                            </p>
                        @endif
                    </div>
                    <div class="col-md-6 text-right">
                        @if($canAttempt)
                            <a href="{{ route('student.class.quiz.start', ['classId' => $class->id, 'lessonId' => $lesson->id, 'quizId' => $quiz->id]) }}" 
                               class="btn btn-primary btn-lg">
                                <i class="fas fa-play"></i> 
                                @if($attempts->count() > 0)
                                    Take Quiz Again
                                @else
                                    Start Quiz
                                @endif
                            </a>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                You have used all available attempts.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Previous Attempts -->
        @if($attempts->count() > 0)
        <div class="card mt-3">
            <div class="card-header bg-info">
                <h3 class="card-title text-white">
                    <i class="fas fa-history"></i> Previous Attempts
                </h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Attempt</th>
                            <th>Date & Time</th>
                            <th>Score</th>
                            <th>Percentage</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attempts as $attempt)
                            @php
                                $percentage = $attempt->total_points > 0 
                                    ? round(($attempt->score / $attempt->total_points) * 100, 2) 
                                    : 0;
                                $isPassed = $percentage >= $quiz->passing_score;
                            @endphp
                            <tr class="attempt-row">
                                <td><strong>#{{ $attempt->attempt_number }}</strong></td>
                                <td>{{ date('M d, Y h:i A', strtotime($attempt->submitted_at)) }}</td>
                                <td>{{ $attempt->score }} / {{ $attempt->total_points }}</td>
                                <td>
                                    <strong>{{ $percentage }}%</strong>
                                </td>
                                <td>
                                    @if($attempt->status === 'graded')
                                        @if($isPassed)
                                            <span class="badge badge-passed">Passed</span>
                                        @else
                                            <span class="badge badge-failed">Failed</span>
                                        @endif
                                    @else
                                        <span class="badge badge-pending">Pending Review</span>
                                    @endif
                                </td>
                                <td>
                                    @if($quiz->show_results && $attempt->status === 'graded')
                                        <button class="btn btn-sm btn-info view-results-btn" 
                                                data-attempt-id="{{ $attempt->id }}">
                                            <i class="fas fa-eye"></i> View Results
                                        </button>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Sidebar Instructions -->
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle"></i> Instructions
                </h3>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        Read all questions carefully
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        Answer all questions before submitting
                    </li>
                    @if($quiz->time_limit)
                    <li class="mb-2">
                        <i class="fas fa-clock text-warning"></i>
                        Complete within {{ $quiz->time_limit }} minutes
                    </li>
                    @endif
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        You can navigate between questions
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-check text-success"></i>
                        Review your answers before submitting
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                        Once submitted, you cannot change answers
                    </li>
                </ul>

                @if($quiz->shuffle_questions)
                <div class="alert alert-info mt-3">
                    <i class="fas fa-random"></i>
                    <small>Questions will appear in random order</small>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Results Modal -->
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white">
                    <i class="fas fa-chart-bar"></i> Quiz Results
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="resultsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
    const QUIZ_ID = {{ $quiz->id }};
    const CLASS_ID = {{ $class->id }};
    const LESSON_ID = {{ $lesson->id }};
    
    const API_ROUTES = {
        getResults: "{{ route('student.class.quiz.results', ['classId' => $class->id, 'lessonId' => $lesson->id, 'quizId' => $quiz->id, 'attemptId' => ':attemptId']) }}"
    };
</script>
@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection