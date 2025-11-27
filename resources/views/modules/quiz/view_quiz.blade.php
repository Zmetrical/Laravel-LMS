@extends('modules.class.main', [
    'activeTab' => 'lessons', 
    'userType' => $userType, 
    'class' => $class])

@section('page-styles')
<style>

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
        <div class="card">
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
            <div class="card-header bg-primary">
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

</div>


@endsection

@section('page-scripts')
<script>
    const QUIZ_ID = {{ $quiz->id }};
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