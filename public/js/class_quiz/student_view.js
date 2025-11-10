$(document).ready(function() {
    let currentQuestionIndex = 0;
    let answers = {};
    let timeRemaining = TIME_LIMIT * 60; // Convert to seconds
    let timerInterval = null;

    // Initialize
    initializeTimer();
    loadSavedAnswers();

    // Question Navigation Buttons
    $('.question-nav-btn').on('click', function() {
        const index = parseInt($(this).data('index'));
        navigateToQuestion(index);
    });

    // Next Button
    $('.next-question-btn').on('click', function() {
        const index = parseInt($(this).data('index'));
        navigateToQuestion(index + 1);
    });

    // Previous Button
    $('.prev-question-btn').on('click', function() {
        const index = parseInt($(this).data('index'));
        navigateToQuestion(index - 1);
    });

    // Track Answers
    $('.question-answer').on('change input', function() {
        const questionId = $(this).data('question-id');
        const questionIndex = $(this).data('question-index');
        let answer;

        if ($(this).is('textarea')) {
            // Essay answer
            answer = {
                question_id: questionId,
                answer_text: $(this).val().trim()
            };
        } else {
            // Multiple choice or true/false
            answer = {
                question_id: questionId,
                option_id: parseInt($(this).val())
            };
        }

        answers[questionId] = answer;
        saveAnswersToStorage();
        updateNavigationButton(questionIndex);
    });

    // Review Answers
    $('#reviewAnswersBtn, #submitQuizBtn').on('click', function() {
        showReviewModal();
    });

    // Confirm Submit
    $('#confirmSubmitBtn').on('click', function() {
        submitQuiz();
    });

    // Warn before leaving page
    window.addEventListener('beforeunload', function(e) {
        e.preventDefault();
        e.returnValue = '';
        return '';
    });

    function initializeTimer() {
        if (TIME_LIMIT === 0) return;

        // Check for saved time
        const savedTime = localStorage.getItem(`quiz_${QUIZ_ID}_time`);
        if (savedTime) {
            timeRemaining = parseInt(savedTime);
        }

        updateTimerDisplay();
        
        timerInterval = setInterval(function() {
            timeRemaining--;
            updateTimerDisplay();
            localStorage.setItem(`quiz_${QUIZ_ID}_time`, timeRemaining);

            // Warning at 5 minutes
            if (timeRemaining === 300) {
                $('.quiz-timer').addClass('warning');
                toastr.warning('5 minutes remaining!', 'Time Alert');
            }

            // Warning at 1 minute
            if (timeRemaining === 60) {
                toastr.error('1 minute remaining!', 'Time Alert');
            }

            // Time's up
            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                toastr.error('Time is up! Submitting quiz...', 'Time Expired');
                setTimeout(function() {
                    submitQuiz(true);
                }, 2000);
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        const display = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        $('#timerDisplay').text(display);

        // Add warning class if less than 5 minutes
        if (timeRemaining <= 300) {
            $('.quiz-timer').addClass('warning');
        }
    }

    function navigateToQuestion(index) {
        if (index < 0 || index >= QUESTIONS.length) return;

        // Hide current question
        $('.question-item').hide();
        
        // Show target question
        $(`#question-${index}`).show();
        
        // Update navigation
        $('.question-nav-btn').removeClass('active');
        $(`.question-nav-btn[data-index="${index}"]`).addClass('active');
        
        currentQuestionIndex = index;

        // Scroll to top
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    function updateNavigationButton(questionIndex) {
        const question = QUESTIONS[questionIndex];
        const hasAnswer = answers[question.id];
        
        if (hasAnswer) {
            if (question.question_type === 'essay') {
                // Check if essay has content
                if (hasAnswer.answer_text && hasAnswer.answer_text.length > 0) {
                    $(`.question-nav-btn[data-index="${questionIndex}"]`).addClass('answered');
                } else {
                    $(`.question-nav-btn[data-index="${questionIndex}"]`).removeClass('answered');
                }
            } else {
                // Multiple choice or true/false
                if (hasAnswer.option_id) {
                    $(`.question-nav-btn[data-index="${questionIndex}"]`).addClass('answered');
                }
            }
        } else {
            $(`.question-nav-btn[data-index="${questionIndex}"]`).removeClass('answered');
        }
    }

    function showReviewModal() {
        let unansweredCount = 0;
        let answeredCount = 0;

        let html = '<div class="table-responsive"><table class="table table-bordered">';
        html += '<thead><tr><th>Question</th><th>Status</th><th>Action</th></tr></thead><tbody>';

        QUESTIONS.forEach((question, index) => {
            const hasAnswer = answers[question.id];
            let status = '<span class="badge badge-warning">Not Answered</span>';
            
            if (hasAnswer) {
                if (question.question_type === 'essay') {
                    if (hasAnswer.answer_text && hasAnswer.answer_text.length > 0) {
                        status = '<span class="badge badge-success">Answered</span>';
                        answeredCount++;
                    } else {
                        unansweredCount++;
                    }
                } else {
                    if (hasAnswer.option_id) {
                        status = '<span class="badge badge-success">Answered</span>';
                        answeredCount++;
                    } else {
                        unansweredCount++;
                    }
                }
            } else {
                unansweredCount++;
            }

            html += `
                <tr>
                    <td>Question ${index + 1}</td>
                    <td>${status}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info review-go-to-question" 
                                data-index="${index}" data-dismiss="modal">
                            <i class="fas fa-arrow-right"></i> Go
                        </button>
                    </td>
                </tr>
            `;
        });

        html += '</tbody></table></div>';

        if (unansweredCount > 0) {
            html = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> You have ${unansweredCount} unanswered question(s).
                    Are you sure you want to submit?
                </div>
            ` + html;
        } else {
            html = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <strong>Great!</strong> You have answered all questions.
                </div>
            ` + html;
        }

        $('#reviewContent').html(html);
        $('#reviewModal').modal('show');

        // Handle go to question from review
        $('.review-go-to-question').on('click', function() {
            const index = parseInt($(this).data('index'));
            navigateToQuestion(index);
        });
    }

    function submitQuiz(timeExpired = false) {
        if (!timeExpired) {
            // Check if at least some questions are answered
            if (Object.keys(answers).length === 0) {
                toastr.warning('Please answer at least one question before submitting');
                return;
            }
        }

        // Prepare answers array
        const answersArray = Object.values(answers);

        if (!timeExpired) {
            if (!confirm('Are you sure you want to submit your quiz? You cannot change your answers after submission.')) {
                return;
            }
        }

        $('#reviewModal').modal('hide');
        
        const submitBtn = $('#confirmSubmitBtn, #submitQuizBtn');
        const originalHtml = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

        // Clear timer
        if (timerInterval) {
            clearInterval(timerInterval);
        }

        $.ajax({
            url: API_ROUTES.submitQuiz,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                answers: answersArray
            }),
            success: function(response) {
                if (response.success) {
                    // Clear saved data
                    clearSavedData();
                    
                    // Remove beforeunload warning
                    window.removeEventListener('beforeunload', function() {});

                    toastr.success(response.message);

                    // Show result summary
                    if (!response.data.has_essay) {
                        const percentage = response.data.percentage;
                        const passed = percentage >= {{ $quiz->passing_score ?? 75 }};
                        
                        Swal.fire({
                            title: passed ? 'Congratulations!' : 'Quiz Completed',
                            html: `
                                <p>Score: <strong>${response.data.score} / ${response.data.total_points}</strong></p>
                                <p>Percentage: <strong>${percentage}%</strong></p>
                                <p class="text-${passed ? 'success' : 'danger'}">
                                    <strong>${passed ? 'PASSED' : 'FAILED'}</strong>
                                </p>
                            `,
                            icon: passed ? 'success' : 'info',
                            confirmButtonText: 'View Results'
                        }).then(() => {
                            window.location.href = API_ROUTES.backToQuiz;
                        });
                    } else {
                        setTimeout(function() {
                            window.location.href = API_ROUTES.backToQuiz;
                        }, 1500);
                    }
                } else {
                    toastr.error(response.message || 'Failed to submit quiz');
                    submitBtn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr) {
                console.error('Error submitting quiz:', xhr);
                let errorMsg = 'Failed to submit quiz';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                
                toastr.error(errorMsg);
                submitBtn.prop('disabled', false).html(originalHtml);
            }
        });
    }

    function saveAnswersToStorage() {
        localStorage.setItem(`quiz_${QUIZ_ID}_answers`, JSON.stringify(answers));
    }

    function loadSavedAnswers() {
        const saved = localStorage.getItem(`quiz_${QUIZ_ID}_answers`);
        if (saved) {
            answers = JSON.parse(saved);
            
            // Restore answers to form
            QUESTIONS.forEach((question, index) => {
                const answer = answers[question.id];
                if (answer) {
                    if (question.question_type === 'essay') {
                        $(`#essay_${index}`).val(answer.answer_text || '');
                    } else {
                        $(`input[name="question_${question.id}"][value="${answer.option_id}"]`).prop('checked', true);
                    }
                    updateNavigationButton(index);
                }
            });
        }
    }

    function clearSavedData() {
        localStorage.removeItem(`quiz_${QUIZ_ID}_answers`);
        localStorage.removeItem(`quiz_${QUIZ_ID}_time`);
    }
});