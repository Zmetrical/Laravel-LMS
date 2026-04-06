@extends('modules.class.main', [
    'activeTab' => 'lessons',
    'userType' => $userType,
    'class' => $class])

@section('page-styles')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
<style>
    .preview-banner {
        background: repeating-linear-gradient(
            45deg,
            #343a40,
            #343a40 10px,
            #495057 10px,
            #495057 20px
        );
    }

    .question-nav-sidebar {
        position: sticky;
        top: 100px;
    }

    .question-nav-btn {
        margin: 3px;
        min-width: 40px;
        height: 40px;
        font-weight: 600;
    }

    .question-nav-btn.answered {
        background-color: #6c757d !important;
        color: white !important;
        border-color: #6c757d !important;
    }

    .question-nav-btn.nav-current {
        background-color: #343a40 !important;
        color: white !important;
        border-color: #343a40 !important;
    }

    .options-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .option-card {
        cursor: pointer;
        border: 2px solid #dee2e6;
        border-radius: 6px;
        padding: 12px 15px;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
        margin-bottom: 0;
    }

    .option-card:hover {
        border-color: #6c757d;
        background-color: #f8f9fa;
    }

    .option-card.selected {
        border-color: #6c757d;
        background-color: #e9ecef;
    }

    .option-letter {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #f8f9fa;
        border: 2px solid #dee2e6;
        font-weight: bold;
        margin-right: 12px;
        flex-shrink: 0;
        transition: all 0.2s ease;
    }

    .option-card.selected .option-letter {
        background-color: #6c757d;
        border-color: #6c757d;
        color: white;
    }

    .option-card[data-type="checkbox"] .option-letter {
        border-radius: 4px;
    }

    .quiz-timer {
        position: sticky;
        top: 70px;
        z-index: 100;
    }
.all-questions-mode .card-footer {
    display: none !important;
}
    @media (max-width: 768px) {
        .options-container {
            grid-template-columns: 1fr;
        }
        .question-nav-sidebar {
            position: relative;
            top: 0;
        }
    }
</style>
@endsection

@section('tab-content')


<div class="row">
    <div class="col-lg-9 col-md-8">

        {{-- Quiz Header --}}
<div class="card bg-secondary mb-3">
    <div class="card-body py-3">


<div class="d-flex justify-content-between align-items-center">
    <h4 class="text-white mb-0">
        <i class="fas fa-clipboard-list"></i> {{ $preview['title'] }}
    </h4>
    <div class="d-flex align-items-center">
        <button type="button" class="btn btn-light btn-sm ml-2" id="viewModeToggle">
            <i class="fas fa-square"></i> <span id="viewModeText">Show All</span>
        </button>
        <button type="button" class="btn btn-light btn-sm ml-1" id="categorizeBtn">
            <i class="fas fa-layer-group"></i> <span id="categorizeBtnText">Categorize</span>
        </button>
    </div>
</div>

    </div>
</div>
        {{-- Questions --}}
        <div id="questionsContainer">
            @foreach($questions as $index => $question)
            <div class="card question-item" id="question-{{ $index }}"
                 style="{{ $index === 0 ? '' : 'display:none;' }}">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-question-circle text-secondary"></i>
                        Question {{ $index + 1 }}
                        <span class="badge badge-secondary float-right">
                            {{ $question['points'] }} {{ $question['points'] == 1 ? 'pt' : 'pts' }}
                        </span>
                    </h5>
                </div>
                <div class="card-body">
                    <p class="lead font-weight-normal mb-4">{{ $question['question_text'] }}</p>

                    @if(in_array($question['question_type'], ['multiple_choice', 'true_false']))
                        <div class="options-container">
                            @foreach($question['options'] as $optIndex => $option)
                            <div class="option-card"
                                 data-question-index="{{ $index }}"
                                 data-opt-index="{{ $optIndex }}"
                                 data-type="radio">
                                <div class="option-letter">
                                    @if($question['question_type'] === 'true_false')
                                        <i class="fas fa-{{ strtolower($option['text']) === 'true' ? 'check' : 'times' }}"></i>
                                    @else
                                        {{ chr(65 + $optIndex) }}
                                    @endif
                                </div>
                                <div>{{ $option['text'] }}</div>
                            </div>
                            @endforeach
                        </div>

                    @elseif($question['question_type'] === 'multiple_answer')
                        <div class="options-container">
                            @foreach($question['options'] as $optIndex => $option)
                            <div class="option-card"
                                 data-question-index="{{ $index }}"
                                 data-opt-index="{{ $optIndex }}"
                                 data-type="checkbox">
                                <div class="option-letter" style="border-radius:4px;">
                                    {{ chr(65 + $optIndex) }}
                                </div>
                                <div>{{ $option['text'] }}</div>
                            </div>
                            @endforeach
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="fas fa-info-circle"></i> Select all correct answers
                        </small>

                    @elseif($question['question_type'] === 'short_answer')
                        <input type="text"
                               class="form-control form-control-lg preview-short-answer"
                               data-question-index="{{ $index }}"
                               placeholder="Type your answer here..."
                               maxlength="500">
                    @endif
                </div>


<div class="card-footer bg-light">
    <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-default prev-btn"
                {{ $index === 0 ? 'disabled' : '' }}>
            <i class="fas fa-arrow-left"></i> Previous
        </button>
        <div>
            <button type="button" class="btn btn-secondary next-btn"
                    {{ $index === count($questions) - 1 ? 'style=display:none;' : '' }}>
                Next <i class="fas fa-arrow-right"></i>
            </button>
            <button type="button" class="btn btn-secondary finish-preview-btn"
                    {{ $index < count($questions) - 1 ? 'style=display:none;' : '' }}>
                <i class="fas fa-flag-checkered"></i> Finish Preview
            </button>
        </div>
    </div>
</div>


            </div>
            @endforeach
        </div>

    </div>

    {{-- Sidebar --}}
    <div class="col-lg-3 col-md-4">

        {{-- Timer --}}
        @if($preview['time_limit'])
        <div class="card card-secondary quiz-timer mb-3">
            <div class="card-body text-center">
                <h5 class="mb-1"><i class="fas fa-clock"></i> Time Remaining</h5>
                <h2 class="mb-0" id="timerDisplay">{{ $preview['time_limit'] }}:00</h2>
            </div>
        </div>
        @endif

        {{-- Question Navigation --}}
        <div class="card card-outline card-secondary question-nav-sidebar">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list"></i> Questions</h3>
            </div>
            <div class="card-body text-center">
                <div id="questionNavigation">
                    @foreach($questions as $index => $question)
                        <button type="button"
                                class="btn btn-outline-secondary question-nav-btn {{ $index === 0 ? 'nav-current' : '' }}"
                                data-index="{{ $index }}">
                            {{ $index + 1 }}
                        </button>
                    @endforeach
                </div>
                <hr>
                <div class="text-left small">
                    <div class="mb-1">
                        <span class="badge badge-secondary">●</span> Answered
                    </div>
                    <div>
                        <span class="badge badge-dark">●</span> Current
                    </div>
                </div>
            </div>
<div class="card-footer">
    <button type="button" class="btn btn-secondary btn-block" id="submitPreviewBtn">
        <i class="fas fa-paper-plane"></i> Submit Preview
    </button>
    <hr class="my-2">
    <button type="button" class="btn btn-default btn-block" id="exportPptBtn">
        <i class="fas fa-file-powerpoint"></i> Download PPT
    </button>
</div>
        </div>

    </div>
</div>

@endsection

@section('page-scripts')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    const QUESTIONS    = @json($questions);
    const TIME_LIMIT   = {{ $preview['time_limit'] ?? 0 }};
    const PASSING_SCORE = {{ $preview['passing_score'] ?? 75 }};
    const BACK_URL     = "{{ $backUrl }}";

const EXPORT_PPT_ROUTE = "{{ route('teacher.class.quiz.preview.export.ppt', ['classId' => $class->id, 'lessonId' => $lesson->id], false) }}";
const EXPORT_PPT_FILENAME = "{{ \Str::slug($preview['title']) }}.pptx";

document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('exportPptBtn').addEventListener('click', function(e) {
            e.preventDefault();
            triggerPptExport(this, false);
        });
    });
    
// A unified, robust export function
function triggerPptExport(btn, showSwalValidation = false) {
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Exporting...';
 
    // Build ordered questions using window.questionOrder (set by quiz_preview.js).
    // Falls back to original order if for some reason the array isn't ready yet.
    const orderedQuestions = (window.questionOrder || QUESTIONS.map((_, i) => i))
        .map(i => QUESTIONS[i]);
 
    fetch(EXPORT_PPT_ROUTE, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            title: {!! json_encode($preview['title']) !!},
            time_limit: TIME_LIMIT,
            passing_score: PASSING_SCORE,
            questions: orderedQuestions   // <-- respects categorize / original order
        })
    })
    .then(res => {
        if (!res.ok) {
            return res.text().then(text => {
                try {
                    const data = JSON.parse(text);
                    throw new Error(data.message || 'Server error.');
                } catch(e) {
                    throw new Error(`HTTP ${res.status}: Route mismatch or redirect error.`);
                }
            });
        }
        return res.blob();
    })
    .then(blob => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = EXPORT_PPT_FILENAME;
        a.click();
        URL.revokeObjectURL(url);
 
        btn.disabled = false;
        btn.innerHTML = originalText;
    })
    .catch(err => {
        if (showSwalValidation) {
            Swal.showValidationMessage('Export failed: ' + err.message);
        } else {
            Swal.fire('Error', err.message, 'error');
        }
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>
@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection