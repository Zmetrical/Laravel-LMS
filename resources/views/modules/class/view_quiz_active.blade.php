@extends('modules.class.main', [
    'activeTab' => 'lessons', 
    'userType' => $userType, 
    'class' => $class])

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">

<style>
    .quiz-timer {
        position: fixed;
        top: 80px;
        right: 20px;
        z-index: 1000;
        min-width: 200px;
    }
    .quiz-timer.warning {
        animation: pulse 1s infinite;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    .question-card {
        border-left: 4px solid #007bff;
        margin-bottom: 20px;
    }
    .question-nav-btn {
        margin: 5px;
        min-width: 45px;
    }
    .question-nav-btn.answered {
        background-color: #28a745 !important;
        border-color: #28a745 !important;
        color: white !important;
    }
    .question-nav-btn.active {
        background-color: #007bff !important;
        border-color: #007bff !important;
        color: white !important;
    }
    .option-radio {
        transform: scale(1.2);
        margin-right: 10px;
    }
</style>
@endsection

@section('tab-content')
<!-- Timer Card (if time limit exists) -->
@if($quiz->time_limit)
<div class="card card-warning quiz-timer">
    <div class="card-body text-center">
        <h5 class="mb-2">Time Remaining</h5>
        <h2 class="mb-0" id="timerDisplay">{{ $quiz->time_limit }}:00</h2>
    </div>
</div>
@endif

<div class="row">
    <div class="col-md-9">
        <!-- Quiz Header -->
        <div class="card bg-primary">
            <div class="card-body">
                <h4 class="text-white mb-1">
                    <i class="fas fa-clipboard-list"></i> {{ $quiz->title }}
                </h4>
                <p class="text-white mb-0">
                    Attempt #{{ $attemptNumber }} | {{ count($questions) }} Questions | Total: {{ array_sum(array_column($questions, 'points')) }} Points
                </p>
            </div>
        </div>

        <!-- Questions Container -->
        <div id="questionsContainer">
            @foreach($questions as $index => $question)
            <div class="card question-card question-item" id="question-{{ $index }}" 
                 data-question-index="{{ $index }}" 
                 style="{{ $index === 0 ? '' : 'display: none;' }}">
                <div class="card-header">
                    <h5 class="mb-0">
                        Question {{ $index + 1 }} of {{ count($questions) }}
                        <span class="badge badge-info float-right">{{ $question['points'] }} {{ $question['points'] == 1 ? 'point' : 'points' }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <p class="lead">{{ $question['question_text'] }}</p>

                    @if($question['question_type'] === 'multiple_choice')
                        <!-- Multiple Choice Options -->
                        <div class="options-container">
                            @foreach($question['options'] as $optIndex => $option)
                            <div class="custom-control custom-radio mb-3">
                                <input type="radio" 
                                       id="q{{ $index }}_opt{{ $optIndex }}" 
                                       name="question_{{ $question['id'] }}" 
                                       class="custom-control-input option-radio question-answer" 
                                       value="{{ $option->id }}"
                                       data-question-id="{{ $question['id'] }}"
                                       data-question-index="{{ $index }}">
                                <label class="custom-control-label" for="q{{ $index }}_opt{{ $optIndex }}">
                                    <strong>{{ chr(65 + $optIndex) }}.</strong> {{ $option->option_text }}
                                </label>
                            </div>
                            @endforeach
                        </div>

                    @elseif($question['question_type'] === 'true_false')
                        <!-- True/False Options -->
                        <div class="options-container">
                            @foreach($question['options'] as $optIndex => $option)
                            <div class="custom-control custom-radio mb-3">
                                <input type="radio" 
                                       id="q{{ $index }}_opt{{ $optIndex }}" 
                                       name="question_{{ $question['id'] }}" 
                                       class="custom-control-input option-radio question-answer" 
                                       value="{{ $option->id }}"
                                       data-question-id="{{ $question['id'] }}"
                                       data-question-index="{{ $index }}">
                                <label class="custom-control-label" for="q{{ $index }}_opt{{ $optIndex }}">
                                    <strong>{{ $option->option_text }}</strong>
                                </label>
                            </div>
                            @endforeach
                        </div>

                    @elseif($question['question_type'] === 'essay')
                        <!-- Essay Answer -->
                        <div class="form-group">
                            <textarea class="form-control question-answer" 
                                      id="essay_{{ $index }}"
                                      rows="8" 
                                      placeholder="Type your answer here..."
                                      data-question-id="{{ $question['id'] }}"
                                      data-question-index="{{ $index }}"></textarea>
                            <small class="form-text text-muted">
                                This question requires manual grading. Write a clear and complete answer.
                            </small>
                        </div>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-default prev-question-btn" 
                                data-index="{{ $index }}"
                                {{ $index === 0 ? 'disabled' : '' }}>
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        @if($index < count($questions) - 1)
                            <button type="button" class="btn btn-primary next-question-btn" 
                                    data-index="{{ $index }}">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        @else
                            <button type="button" class="btn btn-success" id="reviewAnswersBtn">
                                <i class="fas fa-check"></i> Review Answers
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Question Navigation Sidebar -->
    <div class="col-md-3">
        <div class="card card-outline card-primary sticky-top">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Questions
                </h3>
            </div>
            <div class="card-body text-center">
                <div id="questionNavigation">
                    @foreach($questions as $index => $question)
                        <button type="button" 
                                class="btn btn-outline-primary question-nav-btn {{ $index === 0 ? 'active' : '' }}" 
                                data-index="{{ $index }}">
                            {{ $index + 1 }}
                        </button>
                    @endforeach
                </div>
                <hr>
                <div class="text-left">
                    <small>
                        <span class="badge badge-success">●</span> Answered<br>
                        <span class="badge badge-primary">●</span> Current<br>
                        <span class="badge badge-secondary">●</span> Not answered
                    </small>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-success btn-block" id="submitQuizBtn">
                    <i class="fas fa-paper-plane"></i> Submit Quiz
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Review Your Answers
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="reviewContent">
                <!-- Will be populated by JS -->
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fas fa-arrow-left"></i> Continue Answering
                </button>
                <button type="button" class="btn btn-success" id="confirmSubmitBtn">
                    <i class="fas fa-check"></i> Confirm & Submit
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    const QUIZ_ID = {{ $quiz->id }};
    const LESSON_ID = {{ $lesson->id }};
    const TIME_LIMIT = {{ $quiz->time_limit ?? 0 }};
    const PASSING_SCORE = {{ $quiz->passing_score ?? 75 }};
    const QUESTIONS = @json($questions);
    
    const API_ROUTES = {
        submitQuiz: "{{ route('student.class.quiz.submit', ['classId' => $class->id, 'lessonId' => $lesson->id, 'quizId' => $quiz->id]) }}",
        backToQuiz: "{{ route('student.class.quiz.view', ['classId' => $class->id, 'lessonId' => $lesson->id, 'quizId' => $quiz->id]) }}"
    };

</script>
@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection