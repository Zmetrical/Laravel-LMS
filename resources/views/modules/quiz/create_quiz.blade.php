@extends('modules.class.main', [
    'activeTab' => 'lessons', 
    'userType' => $userType, 
    'class' => $class])

@section('page-styles')
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
    .option-correct-indicator {
        cursor: pointer;
    }
    .collapsed-card .card-body {
        display: none;
    }
</style>
@endsection

@section('tab-content')
<!-- Breadcrumb -->
<div class="row mb-3">
    <div class="col-12">
        <a href="{{ route('teacher.class.lessons', $class->id) }}" class="btn btn-default">
            <i class="fas fa-arrow-left"></i> Back to Lessons
        </a>
    </div>
</div>

<!-- Quiz Settings Card -->
<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-cog"></i> Quiz Settings
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <form id="quizSettingsForm">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Quiz Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quizTitle" 
                               placeholder="e.g., Chapter 1 Quiz" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Lesson</label>
                        <input type="text" class="form-control" value="{{ $lesson->title }}" readonly>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Description (Optional)</label>
                <textarea class="form-control" id="quizDescription" rows="2" 
                          placeholder="Brief description of the quiz..."></textarea>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Time Limit (Minutes)</label>
                        <input type="number" class="form-control" id="timeLimit" 
                               value="60" min="1" placeholder="Leave blank for no limit">
                        <small class="form-text text-muted">0 or blank = No time limit</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Max Attempts <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="maxAttempts" 
                               value="1" min="1" max="10" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Passing Score (%) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="passingScore" 
                               value="75" min="0" max="100" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Options</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="showResults" checked>
                            <label class="custom-control-label" for="showResults">Show Results</label>
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="shuffleQuestions">
                            <label class="custom-control-label" for="shuffleQuestions">Shuffle Questions</label>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Question Builder -->
<div class="row">
    <!-- Question List Sidebar -->
    <div class="col-md-3">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Questions
                </h3>
            </div>
            <div class="card-body p-0" style="max-height: 500px; overflow-y: auto;">
                <ul class="nav nav-pills nav-sidebar flex-column" id="questionNav">
                    <li class="nav-item text-center text-muted p-3">
                        <small>No questions added yet</small>
                    </li>
                </ul>
            </div>
            <div class="card-footer">
                <div class="text-center">
                    <strong>Total: <span id="totalQuestions">0</span> questions</strong><br>
                    <strong>Points: <span id="totalPoints">0</span></strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Question Builder Area -->
    <div class="col-md-9">
        <!-- Question Type Selection -->
        <div class="card card-primary card-outline card-outline-tabs">
            <div class="card-header p-0 border-bottom-0">
                <ul class="nav nav-tabs" id="questionTypeTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="mc-tab" data-toggle="tab" href="#multipleChoice" role="tab">
                            <i class="fas fa-check-circle"></i> Multiple Choice
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tf-tab" data-toggle="tab" href="#trueFalse" role="tab">
                            <i class="fas fa-toggle-on"></i> True/False
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="essay-tab" data-toggle="tab" href="#essay" role="tab">
                            <i class="fas fa-file-alt"></i> Essay
                        </a>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                <div class="tab-content" id="questionTypeTabsContent">
                    <!-- Multiple Choice Tab -->
                    <div class="tab-pane fade show active" id="multipleChoice" role="tabpanel">
                        <div class="form-group">
                            <label>Question Text <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="mcQuestion" rows="3" 
                                      placeholder="Enter your question here..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Points <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="mcPoints" 
                                   value="1" min="0.01" step="0.01">
                        </div>

                        <div class="form-group">
                            <label>Answer Options <small class="text-muted">(Check the correct answer)</small></label>
                            <div id="mcOptions">
                                <div class="input-group mb-2 mc-option-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">A</span>
                                    </div>
                                    <input type="text" class="form-control mc-option" placeholder="Option A">
                                    <div class="input-group-append">
                                        <div class="input-group-text option-correct-indicator">
                                            <input type="radio" name="mcCorrect" value="0">
                                        </div>
                                        <button class="btn btn-danger btn-sm remove-option" type="button" style="display:none;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addMCOption">
                                <i class="fas fa-plus"></i> Add Option
                            </button>
                        </div>

                        <button type="button" class="btn btn-primary" id="addMCQuestion">
                            <i class="fas fa-plus"></i> Add Question
                        </button>
                    </div>

                    <!-- True/False Tab -->
                    <div class="tab-pane fade" id="trueFalse" role="tabpanel">
                        <div class="form-group">
                            <label>Question Text <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="tfQuestion" rows="3" 
                                      placeholder="Enter your true/false statement..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Points <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="tfPoints" 
                                   value="1" min="0.01" step="0.01">
                        </div>

                        <div class="form-group">
                            <label>Correct Answer <span class="text-danger">*</span></label>
                            <div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="tfTrue" name="tfCorrect" 
                                           class="custom-control-input" value="true">
                                    <label class="custom-control-label" for="tfTrue">True</label>
                                </div>
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="tfFalse" name="tfCorrect" 
                                           class="custom-control-input" value="false">
                                    <label class="custom-control-label" for="tfFalse">False</label>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary" id="addTFQuestion">
                            <i class="fas fa-plus"></i> Add Question
                        </button>
                    </div>

                    <!-- Essay Tab -->
                    <div class="tab-pane fade" id="essay" role="tabpanel">
                        <div class="form-group">
                            <label>Question Text <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="essayQuestion" rows="3" 
                                      placeholder="Enter your essay prompt..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Points <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="essayPoints" 
                                   value="10" min="0.01" step="0.01">
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> Essay questions will be manually graded.
                        </div>

                        <button type="button" class="btn btn-primary" id="addEssayQuestion">
                            <i class="fas fa-plus"></i> Add Question
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="text-right mb-3">
            <button type="button" class="btn btn-success btn-lg" id="saveQuiz">
                <i class="fas fa-save"></i> {{ $isEdit ?? false ? 'Update Quiz' : 'Save & Publish Quiz' }}
            </button>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
    const LESSON_ID = {{ $lesson->id }};
    const IS_EDIT = {{ $isEdit ?? false ? 'true' : 'false' }};
    
    @if($isEdit ?? false)
        const QUIZ_ID = {{ $quiz->id ?? 0 }};
    @endif
    
    const API_ROUTES = {
        @if($isEdit ?? false)
            getQuizData: "{{ route('teacher.class.quiz.data', ['classId' => $class->id, 'lessonId' => $lesson->id, 'quizId' => $quiz->id ?? 0]) }}",
            submitQuiz: "{{ route('teacher.class.quiz.update', ['classId' => $class->id, 'lessonId' => $lesson->id, 'quizId' => $quiz->id ?? 0]) }}",
            deleteQuiz: "{{ route('teacher.class.quiz.delete', ['classId' => $class->id, 'lessonId' => $lesson->id, 'quizId' => $quiz->id ?? 0]) }}",
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