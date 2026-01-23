@extends('modules.class.main', [
    'activeTab' => 'lessons', 
    'userType' => $userType, 
    'class' => $class])

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">

<style>
    #badge-current{
        background-color: var(--trinity-blue) !important;
    }
    
    /* Timer Styles */
    .quiz-timer {
        position: fixed;
        top: 70px;
        right: 20px;
        z-index: 1050;
        min-width: 180px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .quiz-timer.warning {
        animation: pulse 1s infinite;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    
    /* Question Navigation Sidebar */
    .question-nav-sidebar {
        position: sticky;
        top: 100px;
        z-index: 1040;
    }
    .question-nav-btn {
        margin: 3px;
        min-width: 40px;
        height: 40px;
        font-weight: 600;
    }
    .question-nav-btn.answered {
        background-color: #28a745 !important;
        color: white !important;
        border-color: #28a745 !important;
    }
    
    /* Option Card Styles - Two Column Layout */
    .options-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .option-card {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid #dee2e6;
        margin-bottom: 0;
    }
    .option-card:hover {
        border-color: #007bff;
        background-color: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .option-card.selected {
        border-color: #007bff;
        background-color: #e7f3ff;
        box-shadow: 0 4px 8px rgba(0,123,255,0.2);
    }
    .option-card .card-body {
        padding: 15px 20px;
        display: flex;
        align-items: center;
    }
    .option-letter {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background-color: #f8f9fa;
        border: 2px solid #dee2e6;
        font-weight: bold;
        font-size: 16px;
        margin-right: 15px;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }
    .option-card.selected .option-letter {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }
    .option-text {
        flex: 1;
        font-size: 15px;
        line-height: 1.5;
    }
    .option-radio {
        display: none;
    }
    
    /* View Mode Toggle */
    #viewModeToggle {
        white-space: nowrap;
        font-weight: 600;
        transition: all 0.2s ease;
    }
    
    #viewModeToggle:hover {
        background-color: #e9ecef;
    }
    
    /* All Questions View */
    .all-questions-mode .question-item {
        margin-bottom: 20px;
    }
    
    .all-questions-mode .card-footer {
        display: none !important;
    }
    
    /* Mobile Optimizations */
    @media (max-width: 768px) {
        .quiz-timer {
            position: relative;
            top: 0;
            right: 0;
            margin-bottom: 15px;
            width: 100%;
        }
        .quiz-timer .card-body {
            padding: 12px;
        }
        .quiz-timer h5 {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .quiz-timer h2 {
            font-size: 24px;
        }
        .question-nav-sidebar {
            position: relative;
            top: 0;
            margin-top: 20px;
        }
        .question-nav-btn-mobile {
            min-width: 42px;
            height: 42px;
            font-size: 14px;
            margin: 4px;
        }
        .card-header h5 {
            font-size: 15px;
        }
        .card-header .badge {
            font-size: 11px;
            padding: 4px 8px;
        }
        .lead {
            font-size: 15px;
        }
        
        /* Single column on mobile */
        .options-container {
            grid-template-columns: 1fr;
        }
        
        .option-card .card-body {
            padding: 12px 15px;
        }
        .option-letter {
            width: 30px;
            height: 30px;
            font-size: 14px;
            margin-right: 12px;
        }
        .option-text {
            font-size: 14px;
        }
        .btn {
            padding: 8px 12px;
            font-size: 14px;
        }
        
        body {
            padding-bottom: 100px;
        }
        
        .progress-text {
            font-size: 12px;
        }
    }
    
    /* Extra small devices */
    @media (max-width: 576px) {
        .quiz-timer h2 {
            font-size: 20px;
        }
        .question-nav-btn {
            min-width: 35px;
            height: 35px;
            font-size: 12px;
        }
        .badge {
            font-size: 11px;
        }
        .option-letter {
            width: 28px;
            height: 28px;
            font-size: 13px;
            margin-right: 10px;
        }
        .option-text {
            font-size: 13px;
        }
    }
    
    /* Essay textarea */
    .essay-textarea {
        border: 2px solid #dee2e6;
        transition: all 0.2s ease;
    }
    .essay-textarea:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }

    /* Multiple Answer Checkboxes */
    .option-card[data-type="checkbox"] .option-letter {
        border-radius: 4px;
    }

    .option-card[data-type="checkbox"].selected .option-letter {
        background-color: #007bff;
        border-color: #007bff;
        color: white;
    }

    .option-checkbox {
        display: none;
    }

    /* Short Answer Input */
    .short-answer-input {
        border: 2px solid #dee2e6;
        transition: all 0.2s ease;
        padding: 12px 15px;
    }

    .short-answer-input:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
    }
</style>
@endsection

@section('tab-content')
<div class="row">
    <div class="col-lg-9 col-md-8">
        <!-- Timer Card (Mobile View) -->
        @if($quiz->time_limit)
        <div class="card card-warning quiz-timer d-md-none">
            <div class="card-body text-center">
                <h5 class="mb-2">Time Remaining</h5>
                <h2 class="mb-0" id="timerDisplayMobile">{{ $quiz->time_limit }}:00</h2>
            </div>
        </div>
        @endif

        <!-- Quiz Header -->
        <div class="card bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div class="flex-grow-1 mb-2 mb-md-0">
                        <h4 class="text-white mb-1">
                            <i class="fas fa-clipboard-list"></i> {{ $quiz->title }}
                        </h4>
                        <small class="text-white d-block d-md-none">
                            <i class="fas fa-info-circle"></i> Stay on this tab during the quiz
                        </small>
                    </div>
                    <div>
                        <button type="button" class="btn btn-light btn-sm" id="viewModeToggle">
                            <i class="fas fa-square"></i> <span id="viewModeText">Show All</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Questions Container -->
        <div id="questionsContainer">
            @foreach($questions as $index => $question)
            <div class="card question-card question-item" id="question-{{ $index }}" 
                 data-question-index="{{ $index }}" 
                 style="{{ $index === 0 ? '' : 'display: none;' }}">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle text-primary"></i> Question {{ $index + 1 }}
                        <span class="badge badge-primary float-right">{{ $question['points'] }} {{ $question['points'] == 1 ? 'pt' : 'pts' }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <p class="lead font-weight-normal mb-4">{{ $question['question_text'] }}</p>

                    @if($question['question_type'] === 'multiple_choice')
                        <div class="options-container">
                            @foreach($question['options'] as $optIndex => $option)
                            <div class="card option-card" data-option-id="{{ $option->id }}" data-question-index="{{ $index }}">
                                <div class="card-body">
                                    <div class="option-letter">{{ chr(65 + $optIndex) }}</div>
                                    <div class="option-text">{{ $option->option_text }}</div>
                                    <input type="radio" 
                                           id="q{{ $index }}_opt{{ $optIndex }}" 
                                           name="question_{{ $question['id'] }}" 
                                           class="option-radio question-answer" 
                                           value="{{ $option->id }}"
                                           data-question-id="{{ $question['id'] }}"
                                           data-question-index="{{ $index }}">
                                </div>
                            </div>
                            @endforeach
                        </div>

                    @elseif($question['question_type'] === 'true_false')
                        <div class="options-container">
                            @foreach($question['options'] as $optIndex => $option)
                            <div class="card option-card" data-option-id="{{ $option->id }}" data-question-index="{{ $index }}">
                                <div class="card-body">
                                    <div class="option-letter">
                                        <i class="fas fa-{{ strtolower($option->option_text) === 'true' ? 'check' : 'times' }}"></i>
                                    </div>
                                    <div class="option-text"><strong>{{ $option->option_text }}</strong></div>
                                    <input type="radio" 
                                           id="q{{ $index }}_opt{{ $optIndex }}" 
                                           name="question_{{ $question['id'] }}" 
                                           class="option-radio question-answer" 
                                           value="{{ $option->id }}"
                                           data-question-id="{{ $question['id'] }}"
                                           data-question-index="{{ $index }}">
                                </div>
                            </div>
                            @endforeach
                        </div>

                    @elseif($question['question_type'] === 'multiple_answer')
                        <div class="options-container">
                            @foreach($question['options'] as $optIndex => $option)
                            <div class="card option-card" data-option-id="{{ $option->id }}" data-question-index="{{ $index }}" data-type="checkbox">
                                <div class="card-body">
                                    <div class="option-letter">{{ chr(65 + $optIndex) }}</div>
                                    <div class="option-text">{{ $option->option_text }}</div>
                                    <input type="checkbox" 
                                        id="q{{ $index }}_opt{{ $optIndex }}" 
                                        name="question_{{ $question['id'] }}[]" 
                                        class="option-checkbox question-answer" 
                                        value="{{ $option->id }}"
                                        data-question-id="{{ $question['id'] }}"
                                        data-question-index="{{ $index }}">
                                </div>
                            </div>
                            @endforeach
                        </div>
                        <small class="form-text text-muted mt-2">
                            <i class="fas fa-info-circle"></i> Select all correct answers
                        </small>

                    @elseif($question['question_type'] === 'short_answer')
                        <div class="form-group">
                            <input type="text" 
                                class="form-control form-control-lg short-answer-input question-answer" 
                                id="short_{{ $index }}"
                                placeholder="Type your answer here..."
                                data-question-id="{{ $question['id'] }}"
                                data-question-index="{{ $index }}"
                                maxlength="500">
                        </div>

                    @elseif($question['question_type'] === 'essay')
                        <div class="form-group">
                            <textarea class="form-control essay-textarea question-answer" 
                                      id="essay_{{ $index }}"
                                      rows="10" 
                                      placeholder="Type your answer here..."
                                      data-question-id="{{ $question['id'] }}"
                                      data-question-index="{{ $index }}"></textarea>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> This question requires manual grading. Write a clear and complete answer.
                            </small>
                        </div>
                    @endif
                </div>
                <div class="card-footer bg-light">
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary prev-question-btn" 
                                data-index="{{ $index }}"
                                {{ $index === 0 ? 'disabled' : '' }}>
                            <i class="fas fa-arrow-left"></i> <span class="d-none d-sm-inline">Previous</span>
                        </button>
                        @if($index < count($questions) - 1)
                            <button type="button" class="btn btn-primary next-question-btn" 
                                    data-index="{{ $index }}">
                                <span class="d-none d-sm-inline">Next</span> <i class="fas fa-arrow-right"></i>
                            </button>
                        @else
                            <button type="button" class="btn btn-success" id="reviewAnswersBtn">
                                <i class="fas fa-check"></i> <span class="d-none d-sm-inline">Review</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Question Navigation Sidebar -->
    <div class="col-lg-3 col-md-4 d-none d-md-block">
        <!-- Timer Card (Desktop View) -->
        @if($quiz->time_limit)
        <div class="card card-warning quiz-timer mb-3">
            <div class="card-body text-center">
                <h5 class="mb-2"><i class="fas fa-clock"></i> Time Remaining</h5>
                <h2 class="mb-0" id="timerDisplay">{{ $quiz->time_limit }}:00</h2>
            </div>
        </div>
        @endif

        <!-- Question Navigation -->
        <div class="card card-outline card-primary question-nav-sidebar">
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
                                data-index="{{ $index }}"
                                title="Question {{ $index + 1 }}">
                            {{ $index + 1 }}
                        </button>
                    @endforeach
                </div>
                <hr>
                <div class="text-left small">
                    <div class="mb-1">
                        <span class="badge badge-success">●</span> Answered
                    </div>
                    <div class="mb-1">
                        <span class="badge badge-primary" id="badge-current">●</span> Current
                    </div>
                    <div>
                        <span class="badge badge-secondary">●</span> Not answered
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-primary btn-block" id="submitQuizBtn">
                    <i class="fas fa-paper-plane"></i> Submit Quiz
                </button>
            </div>
        </div>
    </div>
    
    <!-- Mobile Question Navigation -->
    <div class="col-12 d-md-none mb-3">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Jump to Question
                </h3>
            </div>
            <div class="card-body text-center">
                <div id="questionNavigationMobile">
                    @foreach($questions as $index => $question)
                        <button type="button" 
                                class="btn btn-outline-primary question-nav-btn question-nav-btn-mobile {{ $index === 0 ? 'active' : '' }}" 
                                data-index="{{ $index }}"
                                title="Question {{ $index + 1 }}">
                            {{ $index + 1 }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">Review Answers</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3" id="reviewSummary">
                    <span id="answeredCount" class="font-weight-bold">0</span> / {{ count($questions) }} answered
                </div>
                
                <div id="reviewQuestionNav" class="text-center">
                    @foreach($questions as $index => $question)
                        <button type="button" 
                                class="btn btn-outline-secondary question-review-btn" 
                                data-index="{{ $index }}"
                                data-question-id="{{ $question['id'] }}"
                                data-dismiss="modal"
                                style="margin: 5px; min-width: 45px; height: 45px; font-weight: 600;">
                            {{ $index + 1 }}
                        </button>
                    @endforeach
                </div>

                <div id="reviewWarning" class="alert alert-warning mt-3" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i> You have unanswered questions
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    Continue Answering
                </button>
                <button type="button" class="btn btn-success" id="confirmSubmitBtn">
                    Submit Quiz
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
    const QUIZ_ID = {{ $quiz->id }};
    const LESSON_ID = {{ $lesson->id }};
    const ATTEMPT_ID = {{ $attemptId }};
    const TIME_LIMIT = {{ $quiz->time_limit ?? 0 }};
    const PASSING_SCORE = {{ $quiz->passing_score ?? 75 }};
    const QUESTIONS = @json($questions);
    const IS_RESUMING = {{ $isResuming ? 'true' : 'false' }};
    const ELAPSED_SECONDS = {{ $elapsedSeconds ?? 0 }};
    const SAVED_ANSWERS = {{ $isResuming ? 'true' : 'false' }};

    const API_ROUTES = {
        submitQuiz: "{{ route('student.class.quiz.submit', ['classId' => $class->id, 'lessonId' => $lesson->id, 'quizId' => $quiz->id]) }}",
        backToQuiz: "{{ route('student.class.quiz.view', ['classId' => $class->id, 'lessonId' => $lesson->id, 'quizId' => $quiz->id]) }}",
        heartbeat: "{{ route('student.class.quiz.heartbeat', ['classId' => $class->id, 'lessonId' => $lesson->id, 'quizId' => $quiz->id]) }}",
        saveProgress: "{{ route('student.class.quiz.save-progress', ['classId' => $class->id, 'lessonId' => $lesson->id, 'quizId' => $quiz->id]) }}"
    };

    console.log('Quiz initialized with:', {
        quizId: QUIZ_ID,
        attemptId: ATTEMPT_ID,
        timeLimit: TIME_LIMIT,
        isResuming: IS_RESUMING,
        elapsedSeconds: ELAPSED_SECONDS,
        questionsCount: QUESTIONS.length
    });
</script>

@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection