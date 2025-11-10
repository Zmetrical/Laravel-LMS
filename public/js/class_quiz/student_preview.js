$(document).ready(function() {
    // View Results Button
    $('.view-results-btn').on('click', function() {
        const attemptId = $(this).data('attempt-id');
        loadResults(attemptId);
    });

    function loadResults(attemptId) {
        const url = API_ROUTES.getResults.replace(':attemptId', attemptId);
        
        $('#resultsModal').modal('show');
        $('#resultsContent').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-3">Loading results...</p>
            </div>
        `);

        $.ajax({
            url: url,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            success: function(response) {
                if (response.success) {
                    displayResults(response.data);
                } else {
                    $('#resultsContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${response.message || 'Failed to load results'}
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                console.error('Error loading results:', xhr);
                $('#resultsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Failed to load results. Please try again.
                    </div>
                `);
            }
        });
    }

    function displayResults(data) {
        const attempt = data.attempt;
        const quiz = data.quiz;
        const answers = data.answers;
        const correctAnswers = data.correct_answers;
        
        const percentage = attempt.total_points > 0 
            ? ((attempt.score / attempt.total_points) * 100).toFixed(2) 
            : 0;
        const isPassed = percentage >= quiz.passing_score;

        let html = `
            <!-- Score Summary -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="info-box ${isPassed ? 'bg-success' : 'bg-danger'}">
                        <span class="info-box-icon"><i class="fas fa-chart-bar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Your Score</span>
                            <span class="info-box-number">${attempt.score} / ${attempt.total_points}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box ${isPassed ? 'bg-success' : 'bg-danger'}">
                        <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Percentage</span>
                            <span class="info-box-number">${percentage}%</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-box ${isPassed ? 'bg-success' : 'bg-danger'}">
                        <span class="info-box-icon"><i class="fas fa-${isPassed ? 'check' : 'times'}-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Status</span>
                            <span class="info-box-number">${isPassed ? 'PASSED' : 'FAILED'}</span>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <!-- Question by Question Review -->
            <h5 class="mb-3">Answer Review</h5>
        `;

        answers.forEach((answer, index) => {
            const isCorrect = answer.is_correct === 1;
            const isEssay = answer.question_type === 'essay';
            const isPending = answer.is_correct === null;

            html += `
                <div class="card mb-3">
                    <div class="card-header ${isEssay ? 'bg-info' : (isCorrect ? 'bg-success' : 'bg-danger')}">
                        <strong>Question ${index + 1}</strong>
                        <span class="float-right">
                            ${answer.points_earned} / ${answer.question_points} points
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="mb-3"><strong>${escapeHtml(answer.question_text)}</strong></p>
            `;

            if (isEssay) {
                html += `
                    <div class="alert alert-info">
                        <strong>Your Answer:</strong>
                        <p class="mb-0 mt-2">${escapeHtml(answer.answer_text || 'No answer provided')}</p>
                    </div>
                    ${isPending ? '<p class="text-warning"><i class="fas fa-clock"></i> Pending manual grading</p>' : ''}
                `;
            } else {
                html += `
                    <p><strong>Your Answer:</strong> ${escapeHtml(answer.selected_option || 'No answer')}</p>
                `;

                if (quiz.show_results && correctAnswers[answer.question_id]) {
                    html += `
                        <p><strong>Correct Answer:</strong> 
                            <span class="text-success">${escapeHtml(correctAnswers[answer.question_id].option_text)}</span>
                        </p>
                    `;
                }

                if (isCorrect) {
                    html += '<p class="text-success"><i class="fas fa-check-circle"></i> Correct!</p>';
                } else {
                    html += '<p class="text-danger"><i class="fas fa-times-circle"></i> Incorrect</p>';
                }
            }

            html += `
                    </div>
                </div>
            `;
        });

        $('#resultsContent').html(html);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }
});