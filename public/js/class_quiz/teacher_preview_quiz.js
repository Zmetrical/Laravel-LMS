$(document).ready(function () {
    let currentIndex = 0;
    let answers = {};
    let timeRemaining = TIME_LIMIT * 60;
    let timerInterval = null;
    let startedAt = Date.now();

    // ── Init ────────────────────────────────────────────────────────────────
    if (TIME_LIMIT > 0) {
        updateTimerDisplay();
        timerInterval = setInterval(function () {
            timeRemaining--;
            updateTimerDisplay();

            if (timeRemaining === 300) {
                showToast('warning', '5 minutes remaining!');
                $('#timerDisplay').closest('.card').addClass('card-warning').removeClass('card-secondary');
            }
            if (timeRemaining === 60) {
                showToast('warning', '1 minute remaining!');
            }
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                showToast('warning', 'Time is up! Submitting preview...');
                setTimeout(() => submitPreview(true), 1500);
            }
        }, 1000);
    }

    // ── Timer ────────────────────────────────────────────────────────────────
    function updateTimerDisplay() {
        const m = Math.floor(timeRemaining / 60);
        const s = timeRemaining % 60;
        $('#timerDisplay').text(`${m}:${s.toString().padStart(2, '0')}`);
    }

    // ── Option Card Clicks ────────────────────────────────────────────────────
    $(document).on('click', '.option-card', function () {
        const qIdx  = parseInt($(this).data('question-index'));
        const oIdx  = parseInt($(this).data('opt-index'));
        const type  = $(this).data('type');

        if (type === 'radio') {
            $(`.option-card[data-question-index="${qIdx}"]`).removeClass('selected');
            $(this).addClass('selected');
            answers[qIdx] = { type: 'radio', selected: [oIdx] };
        } else {
            $(this).toggleClass('selected');
            const selected = [];
            $(`.option-card[data-question-index="${qIdx}"].selected`).each(function () {
                selected.push(parseInt($(this).data('opt-index')));
            });
            if (selected.length > 0) {
                answers[qIdx] = { type: 'checkbox', selected };
            } else {
                delete answers[qIdx];
            }
        }
        updateNavBtn(qIdx);
    });

    // ── Short Answer ──────────────────────────────────────────────────────────
    $(document).on('input', '.preview-short-answer', function () {
        const qIdx = parseInt($(this).data('question-index'));
        const val  = $(this).val().trim();
        if (val) {
            answers[qIdx] = { type: 'text', value: val };
        } else {
            delete answers[qIdx];
        }
        updateNavBtn(qIdx);
    });

    // ── Navigation ────────────────────────────────────────────────────────────
    $(document).on('click', '.next-btn', function () {
        navigateTo(parseInt($(this).data('index')) + 1);
    });

    $(document).on('click', '.prev-btn', function () {
        navigateTo(parseInt($(this).data('index')) - 1);
    });

    $(document).on('click', '.question-nav-btn', function () {
        navigateTo(parseInt($(this).data('index')));
    });

    function navigateTo(index) {
        if (index < 0 || index >= QUESTIONS.length) return;
        $('.question-item').hide();
        $(`#question-${index}`).show();
        $('.question-nav-btn').removeClass('nav-current');
        $(`.question-nav-btn[data-index="${index}"]`).addClass('nav-current');
        currentIndex = index;
        $('html, body').animate({ scrollTop: 0 }, 200);
    }

    function updateNavBtn(qIdx) {
        const btn = $(`.question-nav-btn[data-index="${qIdx}"]`);
        if (answers[qIdx]) {
            btn.addClass('answered');
        } else {
            btn.removeClass('answered');
        }
    }

    // ── Keyboard ──────────────────────────────────────────────────────────────
    $(document).on('keydown', function (e) {
        if ($(e.target).is('input, textarea')) return;
        if (e.keyCode === 37 && currentIndex > 0) navigateTo(currentIndex - 1);
        if (e.keyCode === 39 && currentIndex < QUESTIONS.length - 1) navigateTo(currentIndex + 1);
    });

    // ── Submit Triggers ───────────────────────────────────────────────────────
    $('#finishPreviewBtn, #submitPreviewBtn').on('click', function () {
        const answeredCount = Object.keys(answers).length;
        const unanswered    = QUESTIONS.length - answeredCount;

        Swal.fire({
            title: 'Submit Preview?',
            html: unanswered > 0
                ? `<p>You have <strong>${unanswered}</strong> unanswered question${unanswered > 1 ? 's' : ''}.</p>
                   <p class="text-muted small">Results are for preview only — nothing will be saved.</p>`
                : `<p class="text-muted small">Results are for preview only — nothing will be saved.</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Submit Preview',
            cancelButtonText: 'Keep Answering'
        }).then(result => {
            if (result.isConfirmed) submitPreview(false);
        });
    });

    // ── Grading ───────────────────────────────────────────────────────────────
    function submitPreview(timeExpired) {
        clearInterval(timerInterval);

        const elapsedSecs    = Math.floor((Date.now() - startedAt) / 1000);
        const timeTakenSecs  = TIME_LIMIT > 0
            ? (TIME_LIMIT * 60) - timeRemaining
            : elapsedSecs;
        const timeTakenLabel = formatTime(timeTakenSecs);

        let totalPoints  = 0;
        let earnedPoints = 0;
        let correctCount = 0;

        QUESTIONS.forEach((q, index) => {
            const pts = parseFloat(q.points);
            totalPoints += pts;

            const ans = answers[index];
            let correct = false;

            if (q.question_type === 'multiple_choice' || q.question_type === 'true_false') {
                if (ans && ans.selected.length > 0) {
                    const opt = q.options[ans.selected[0]];
                    correct = opt && parseInt(opt.is_correct) === 1;
                }
            } else if (q.question_type === 'multiple_answer') {
                if (ans && ans.selected.length > 0) {
                    const correctSet  = new Set(
                        q.options.map((o, i) => parseInt(o.is_correct) === 1 ? i : -1).filter(i => i >= 0)
                    );
                    const selectedSet = new Set(ans.selected);
                    const allCorrect  = [...correctSet].every(i => selectedSet.has(i));
                    const noWrong     = [...selectedSet].every(i => correctSet.has(i));
                    correct = allCorrect && noWrong;
                }
            } else if (q.question_type === 'short_answer') {
                if (ans && ans.value) {
                    const exactMatch = q.exact_match !== false && q.exact_match !== 0;
                    const userVal    = exactMatch ? ans.value.trim() : ans.value.trim().toLowerCase();
                    correct = (q.accepted_answers || []).some(a => {
                        const accepted = exactMatch ? a.trim() : a.trim().toLowerCase();
                        return exactMatch
                            ? userVal === accepted
                            : userVal.includes(accepted) || accepted.includes(userVal);
                    });
                }
            }

            if (correct) {
                earnedPoints += pts;
                correctCount++;
            }
        });

        const answeredCount = Object.keys(answers).length;
        const percentage    = totalPoints > 0 ? Math.round((earnedPoints / totalPoints) * 100) : 0;
        const passed        = percentage >= PASSING_SCORE;

        Swal.fire({
            title: 'Preview Results',
            html: `
                <div class="text-center">
                    <div class="mb-3">
                        <span class="badge badge-${passed ? 'success' : 'danger'}" 
                              style="font-size:1rem; padding:8px 20px;">
                            ${passed ? 'PASSED' : 'FAILED'}
                        </span>
                    </div>
                    <h2 class="mb-0">${earnedPoints % 1 === 0 ? earnedPoints : earnedPoints.toFixed(2)} / ${totalPoints % 1 === 0 ? totalPoints : totalPoints.toFixed(2)}</h2>
                    <h4 class="text-muted mb-3">${percentage}%</h4>
                    <table class="table table-sm table-bordered text-left">
                        <tbody>
                            <tr>
                                <td>Questions</td>
                                <td><strong>${QUESTIONS.length}</strong></td>
                            </tr>
                            <tr>
                                <td>Answered</td>
                                <td><strong>${answeredCount} / ${QUESTIONS.length}</strong></td>
                            </tr>
                            <tr>
                                <td>Correct</td>
                                <td><strong>${correctCount} / ${QUESTIONS.length}</strong></td>
                            </tr>
                            <tr>
                                <td>Passing Score</td>
                                <td><strong>${PASSING_SCORE}%</strong></td>
                            </tr>
                            <tr>
                                <td>Time Taken</td>
                                <td><strong>${timeTakenLabel}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                    ${timeExpired ? '<p class="text-warning"><i class="fas fa-clock"></i> Time expired</p>' : ''}
                </div>
            `,
            icon: passed ? 'success' : 'info',
            confirmButtonText: '<i class="fas fa-arrow-left"></i> Back to Quiz Builder',
            confirmButtonColor: '#6c757d',
            allowOutsideClick: false
        }).then(() => {
            window.location.href = BACK_URL;
        });
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function formatTime(secs) {
        if (TIME_LIMIT === 0) {
            const m = Math.floor(secs / 60);
            const s = secs % 60;
            return `${m}m ${s}s`;
        }
        const m = Math.floor(secs / 60);
        const s = secs % 60;
        return `${m}m ${s.toString().padStart(2, '0')}s`;
    }

    function showToast(icon, title) {
        Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        }).fire({ icon, title });
    }
});