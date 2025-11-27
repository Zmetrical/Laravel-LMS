@extends('modules.class.main', [
    'activeTab' => 'lessons', 
    'userType' => $userType, 
    'class' => $class])

@section('page-styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">

<style>
    .question-item {
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }
    .question-item.active {
        border-left: 3px solid #007bff;
        background-color: #f8f9fa;
    }
    .question-item:hover {
        background-color: #f8f9fa;
    }
    .option-correct-indicator { cursor: pointer; }
    .question-type-card {
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid #dee2e6;
    }
    .question-type-card:hover {
        border-color: #007bff;
        transform: translateY(-2px);
    }
    .question-type-card.selected {
        border-color: #007bff;
        background-color: #e7f3ff;
    }
    .question-type-card .card-body { padding: 15px; }
    .question-type-icon {
        font-size: 2rem;
        margin-bottom: 10px;
    }
    .option-item {
        background: #f8f9fa;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 8px;
    }
    .question-preview {
        border-left: 3px solid #17a2b8;
        padding-left: 15px;
        margin-bottom: 10px;
    }
    .badge-question-type {
        font-size: 0.7rem;
        text-transform: uppercase;
    }
</style>
@endsection

@section('tab-content')
<div class="row mb-3">
    <div class="col-12">
        <a href="{{ route('teacher.class.lessons', $class->id) }}" class="btn btn-default">
            <i class="fas fa-arrow-left"></i> Back to Lessons
        </a>
    </div>
</div>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cog"></i> Quiz Settings</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
        </div>
    </div>
    <div class="card-body">
        <form id="quizSettingsForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Quiz Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quizTitle" placeholder="e.g., Chapter 1 Quiz" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Lesson</label>
                        <input type="text" class="form-control" value="{{ $lesson->title }}" readonly>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Time Limit (Minutes)</label>
                        <input type="number" class="form-control" id="timeLimit" value="60" min="0">
                        <small class="form-text text-muted">0 = No limit</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Max Attempts <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="maxAttempts" value="1" min="1" max="5" required>
                        <small class="form-text text-muted">Maximum: 5 attempts</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Passing Score (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="passingScore" value="75" min="0" max="100" required>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list"></i> Questions</h3>
            </div>
            <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                <ul class="nav nav-pills nav-sidebar flex-column" id="questionNav">
                    <li class="nav-item text-center text-muted p-3">
                        <small>No questions added yet</small>
                    </li>
                </ul>
            </div>
            <div class="card-footer text-center">
                <strong>Total: <span id="totalQuestions">0</span> questions</strong><br>
                <strong>Points: <span id="totalPoints">0</span></strong>
            </div>
        </div>
    </div>

    <div class="col-md-9">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle"></i> Add New Question</h3>
            </div>
            <div class="card-body">
                <div id="questionTypeSelector">
                    <h5 class="mb-3">Select Question Type</h5>
                    <div class="row">
                        <div class="col-md-4 col-6 mb-3">
                            <div class="card question-type-card text-center" data-type="multiple_choice">
                                <div class="card-body">
                                    <i class="fas fa-check-circle question-type-icon text-primary"></i>
                                    <h6>Multiple Choice</h6>
                                    <small class="text-muted">Single correct answer</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <div class="card question-type-card text-center" data-type="multiple_answer">
                                <div class="card-body">
                                    <i class="fas fa-check-double question-type-icon text-primary"></i>
                                    <h6>Multiple Answer</h6>
                                    <small class="text-muted">Multiple correct answers</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <div class="card question-type-card text-center" data-type="true_false">
                                <div class="card-body">
                                    <i class="fas fa-toggle-on question-type-icon text-primary"></i>
                                    <h6>True/False</h6>
                                    <small class="text-muted">Binary choice</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-6 mb-3">
                            <div class="card question-type-card text-center" data-type="short_answer">
                                <div class="card-body">
                                    <i class="fas fa-font question-type-icon text-primary"></i>
                                    <h6>Short Answer</h6>
                                    <small class="text-muted">Identification type</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="questionFormContainer" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <span id="selectedTypeIcon"></span>
                            <span id="selectedTypeName"></span>
                        </h5>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="changeTypeBtn">
                            <i class="fas fa-exchange-alt"></i> Change Type
                        </button>
                    </div>

                    <div class="form-group">
                        <label>Question Text <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="questionText" rows="3" placeholder="Enter your question here..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Points <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="questionPoints" value="1" min="0.01" step="0.01">
                            </div>
                        </div>
                    </div>

                    <div id="optionsContainer" style="display: none;">
                        <label>Answer Options <span class="text-danger">*</span></label>
                        <div id="optionsList"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addOptionBtn" style="display: none;">
                            <i class="fas fa-plus"></i> Add Option
                        </button>
                        <small class="text-muted d-block mt-1" id="optionLimitHint"></small>
                    </div>

                    <div id="shortAnswerContainer" style="display: none;">
                        <div class="form-group">
                            <label>Accepted Answers <span class="text-danger">*</span></label>
                            <small class="text-muted d-block mb-2">Add all acceptable answers. Matching is case-insensitive.</small>
                            <div id="acceptedAnswersList"></div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addAcceptedAnswerBtn">
                                <i class="fas fa-plus"></i> Add Alternative Answer
                            </button>
                        </div>
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="exactMatch" checked>
                            <label class="custom-control-label" for="exactMatch">Require exact match (uncheck for partial matching)</label>
                        </div>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-secondary" id="cancelQuestionBtn">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-primary" id="addQuestionBtn">
                            <i class="fas fa-plus"></i> Add Question
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-right mb-3">
            <button type="button" class="btn btn-success btn-lg" id="saveQuiz">
                <i class="fas fa-save"></i> {{ $isEdit ?? false ? 'Update Quiz' : 'Save & Publish Quiz' }}
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="editQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white"><i class="fas fa-edit"></i> Edit Question</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="editQuestionBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEditBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')    

<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
    const LESSON_ID = {{ $lesson->id }};
    const IS_EDIT = {{ $isEdit ?? false ? 'true' : 'false' }};
    const MAX_OPTIONS = 10;
    const QUARTER_ID = {{ $quarterId ?? 'null' }};
    
    @if($isEdit ?? false)
        const QUIZ_ID = {{ $quiz->id ?? 0 }};
    @endif
    
    const API_ROUTES = {
        @if($isEdit ?? false)
            getQuizData: "{{ route('teacher.class.quiz.data', ['classId' => $class->id, 'lessonId' => $lesson->id, 'quizId' => $quiz->id ?? 0]) }}",
            submitQuiz: "{{ route('teacher.class.quiz.update', ['classId' => $class->id, 'lessonId' => $lesson->id, 'quizId' => $quiz->id ?? 0]) }}",
        @else
            submitQuiz: "{{ route('teacher.class.quiz.store', ['classId' => $class->id, 'lessonId' => $lesson->id]) }}",
        @endif
        backToLessons: "{{ route('teacher.class.lessons', $class->id) }}"
    };
</script>
@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection