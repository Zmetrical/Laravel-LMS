$(document).ready(function() {
    let currentQuestionIndex = 0;
    let answers = {};
    let timeRemaining = TIME_LIMIT * 60;
    let timerInterval = null;
    let viewMode = 'oneByOne';
    let heartbeatInterval = null;
    let autoSaveInterval = null;
    let isSubmitting = false;
    let lastSavedAnswers = {};
    let pendingSave = false;
    let tabViolationCount = 0;
    const MAX_VIOLATIONS = 3;

    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    // Initialize
    initializeTimer();
    loadSavedAnswers();
    startHeartbeat();
    startAutoSave();
    preventBrowserManipulation();
    initTabSwitchDetection();
    
    // Show resume message if resuming
    if (typeof IS_RESUMING !== 'undefined' && IS_RESUMING) {
        Toast.fire({
            icon: 'info',
            title: 'Quiz resumed. Your previous answers have been restored.',
            timer: 4000
        });
    }

    // Tab Switch Detection
    function initTabSwitchDetection() {
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Tab switched away or minimized
                handleTabSwitch();
            }
        });

        // Also detect window blur (clicking outside browser)
        window.addEventListener('blur', function() {
            // Only count if page is visible but focus lost
            if (!document.hidden) {
                handleTabSwitch();
            }
        });
    }

    function handleTabSwitch() {
        // Don't trigger if already submitting
        if (isSubmitting) return;

        tabViolationCount++;
        
        console.log('Tab switch detected. Violation count:', tabViolationCount);

        // Update violation display
        updateViolationDisplay();

        if (tabViolationCount === 1) {
            showViolationWarning(1);
        } else if (tabViolationCount === 2) {
            showViolationWarning(2);
        } else if (tabViolationCount >= MAX_VIOLATIONS) {
            showViolationWarning(3);
            // Auto-submit after showing warning
            setTimeout(() => {
                submitQuiz(false, true); // true = violation submit
            }, 3000);
        }
    }

    function updateViolationDisplay() {
        const violationHtml = `
            <span class="badge badge-${tabViolationCount >= 2 ? 'danger' : 'warning'}">
                <i class="fas fa-exclamation-triangle"></i> 
                Violations: ${tabViolationCount}/${MAX_VIOLATIONS}
            </span>
        `;
        
        if ($('#violationBadge').length === 0) {
            $('.quiz-timer .card-body').append(`<div id="violationBadge" class="mt-2">${violationHtml}</div>`);
        } else {
            $('#violationBadge').html(violationHtml);
        }
    }

    function showViolationWarning(level) {
        let title, text, icon;

        if (level === 1) {
            title = 'Warning: Tab Switch Detected';
            text = 'Please stay on this tab during the quiz. You have 2 more warnings before your quiz is auto-submitted.';
            icon = 'warning';
        } else if (level === 2) {
            title = 'Warning: Tab Switch Detected!';
            text = 'This is your last warning. One more tab switch will result in automatic submission of your quiz.';
            icon = 'error';
        } else {
            title = 'Quiz Auto-Submitted';
            text = 'You have exceeded the maximum number of tab switches. Your quiz is being submitted automatically.';
            icon = 'error';
        }

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            confirmButtonColor: level === 3 ? '#dc3545' : '#007bff',
            confirmButtonText: 'I Understand',
            allowOutsideClick: false,
            allowEscapeKey: false
        });
    }

    // View Mode Toggle
    $('#viewModeToggle').on('click', function() {
        if (viewMode === 'oneByOne') {
            viewMode = 'showAll';
            $('#viewModeToggle').html('<i class="fas fa-th-list"></i> <span id="viewModeText">One by One</span>');
            $('#questionsContainer').addClass('all-questions-mode');
            $('.question-item').show();
        } else {
            viewMode = 'oneByOne';
            $('#viewModeToggle').html('<i class="fas fa-square"></i> <span id="viewModeText">Show All</span>');
            $('#questionsContainer').removeClass('all-questions-mode');
            $('.question-item').hide();
            $(`#question-${currentQuestionIndex}`).show();
        }
        $('html, body').animate({ scrollTop: 0 }, 300);
    });

    // Option Card Click Handler
    $(document).on('click', '.option-card', function() {
        const questionIndex = $(this).data('question-index');
        const optionId = $(this).data('option-id');
        const type = $(this).data('type');
        
        if (type === 'checkbox') {
            // Multiple answer - toggle checkbox
            $(this).toggleClass('selected');
            const checkbox = $(this).find('.option-checkbox');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        } else {
            // Single answer - radio behavior
            $(this).siblings('.option-card').removeClass('selected');
            $(this).addClass('selected');
            const radio = $(this).find('.option-radio');
            radio.prop('checked', true).trigger('change');
        }
    });

    // Question Navigation Buttons
    $('.question-nav-btn').on('click', function() {
        const index = parseInt($(this).data('index'));
        
        if (viewMode === 'showAll') {
            const questionCard = $(`#question-${index}`);
            $('html, body').animate({
                scrollTop: questionCard.offset().top - 80
            }, 300);
            $('.question-nav-btn').removeClass('active');
            $(`.question-nav-btn[data-index="${index}"]`).addClass('active');
        } else {
            navigateToQuestion(index);
        }
    });

    // Next/Previous Buttons
    $('.next-question-btn').on('click', function() {
        const index = parseInt($(this).data('index'));
        navigateToQuestion(index + 1);
    });

    $('.prev-question-btn').on('click', function() {
        const index = parseInt($(this).data('index'));
        navigateToQuestion(index - 1);
    });

    // Track Answers with debounce for text inputs
    let textDebounce = null;
    $('.question-answer').on('change input', function() {
        const questionId = $(this).data('question-id');
        const questionIndex = $(this).data('question-index');
        let answer;

        if ($(this).is('textarea')) {
            // Essay answer
            clearTimeout(textDebounce);
            textDebounce = setTimeout(() => {
                answer = {
                    question_id: questionId,
                    answer_text: $(this).val().trim()
                };
                answers[questionId] = answer;
                updateNavigationButton(questionIndex);
            }, 500);
        } else if ($(this).is('input[type="text"]')) {
            // Short answer
            clearTimeout(textDebounce);
            textDebounce = setTimeout(() => {
                answer = {
                    question_id: questionId,
                    answer_text: $(this).val().trim()
                };
                answers[questionId] = answer;
                updateNavigationButton(questionIndex);
            }, 500);
        } else if ($(this).is('.option-checkbox')) {
            // Multiple answer - collect all checked options
            const checkedOptions = [];
            $(`input[name="question_${questionId}[]"]:checked`).each(function() {
                checkedOptions.push(parseInt($(this).val()));
            });
            answer = {
                question_id: questionId,
                option_ids: checkedOptions
            };
            answers[questionId] = answer;
            updateNavigationButton(questionIndex);
        } else {
            // Single answer (radio)
            answer = {
                question_id: questionId,
                option_id: parseInt($(this).val())
            };
            answers[questionId] = answer;
            updateNavigationButton(questionIndex);
        }
    });

    // Review Answers
    $('#reviewAnswersBtn, #submitQuizBtn').on('click', function() {
        showReviewModal();
    });

    // Confirm Submit
    $('#confirmSubmitBtn').on('click', function() {
        submitQuiz();
    });

    // Keyboard navigation
    $(document).on('keydown', function(e) {
        if (viewMode === 'showAll') return;
        if ($(e.target).is('textarea')) return;

        if (e.keyCode === 37 && currentQuestionIndex > 0) {
            e.preventDefault();
            navigateToQuestion(currentQuestionIndex - 1);
        }
        
        if (e.keyCode === 39 && currentQuestionIndex < QUESTIONS.length - 1) {
            e.preventDefault();
            navigateToQuestion(currentQuestionIndex + 1);
        }
    });

    // Warn before leaving page
    window.addEventListener('beforeunload', function(e) {
        if (!isSubmitting) {
            if (Object.keys(answers).length > 0) {
                saveProgressSync();
            }
            e.preventDefault();
            e.returnValue = '';
            return '';
        }
    });

    // Prevent back/forward navigation
    function preventBrowserManipulation() {
        history.pushState(null, null, location.href);
        
        window.addEventListener('popstate', function(event) {
            history.pushState(null, null, location.href);
        });

        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                console.log('Tab hidden - saving progress...');
                saveProgressToServer(true);
            }
        });

        window.addEventListener('focus', function() {
            console.log('Tab focused - checking session...');
            checkSessionValidity();
        });
    }

    function checkSessionValidity() {
        $.ajax({
            url: API_ROUTES.heartbeat,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            data: {
                attempt_id: ATTEMPT_ID
            },
            error: function(xhr) {
                if (xhr.status === 401 || xhr.status === 419) {
                    handleSessionExpired();
                } else if (xhr.status === 410) {
                    handleTimeExpired();
                }
            }
        });
    }

    function startHeartbeat() {
        heartbeatInterval = setInterval(function() {
            $.ajax({
                url: API_ROUTES.heartbeat,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                data: {
                    attempt_id: ATTEMPT_ID,
                    answered_count: Object.keys(answers).length,
                    time_remaining: timeRemaining
                },
                error: function(xhr) {
                    if (xhr.status === 401 || xhr.status === 419) {
                        handleSessionExpired();
                    } else if (xhr.status === 410) {
                        handleTimeExpired();
                    }
                }
            });
        }, 120000);
    }

    function handleSessionExpired() {
        clearInterval(heartbeatInterval);
        clearInterval(timerInterval);
        clearInterval(autoSaveInterval);
        
        Swal.fire({
            title: 'Session Expired',
            text: 'Your session has expired. Your progress has been saved. Please log in again to continue.',
            icon: 'error',
            allowOutsideClick: false,
            confirmButtonColor: '#007bff'
        }).then(() => {
            window.location.href = '/login';
        });
    }

    function handleTimeExpired() {
        clearInterval(heartbeatInterval);
        clearInterval(timerInterval);
        clearInterval(autoSaveInterval);
        
        Swal.fire({
            title: 'Time Expired',
            text: 'Your quiz time has expired and your answers have been automatically submitted.',
            icon: 'warning',
            allowOutsideClick: false,
            confirmButtonColor: '#007bff'
        }).then(() => {
            window.location.href = API_ROUTES.backToQuiz;
        });
    }

    function startAutoSave() {
        autoSaveInterval = setInterval(function() {
            saveProgressToServer(false);
        }, 30000);
    }

    function saveProgressToServer(forceImmediate = false) {
        if (JSON.stringify(answers) === JSON.stringify(lastSavedAnswers)) {
            return;
        }

        if (pendingSave && !forceImmediate) {
            return;
        }

        pendingSave = true;

        $.ajax({
            url: API_ROUTES.saveProgress,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                attempt_id: ATTEMPT_ID,
                answers: answers
            }),
            success: function(response) {
                if (response.success) {
                    lastSavedAnswers = JSON.parse(JSON.stringify(answers));
                    console.log('Progress auto-saved at', new Date().toLocaleTimeString());
                }
                pendingSave = false;
            },
            error: function(xhr) {
                console.error('Auto-save failed:', xhr);
                pendingSave = false;
                
                if (xhr.status === 410 && xhr.responseJSON?.time_expired) {
                    handleTimeExpired();
                } else if (xhr.status === 401 || xhr.status === 419) {
                    handleSessionExpired();
                }
            }
        });
    }

    function saveProgressSync() {
        if (JSON.stringify(answers) === JSON.stringify(lastSavedAnswers)) {
            return;
        }

        const data = JSON.stringify({
            attempt_id: ATTEMPT_ID,
            answers: answers
        });

        const blob = new Blob([data], { type: 'application/json' });
        const sent = navigator.sendBeacon(
            API_ROUTES.saveProgress + '?_token=' + CSRF_TOKEN,
            blob
        );

        if (!sent) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', API_ROUTES.saveProgress, false);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('X-CSRF-TOKEN', CSRF_TOKEN);
            try {
                xhr.send(data);
            } catch (e) {
                console.error('Sync save failed:', e);
            }
        }
    }

    function initializeTimer() {
        if (TIME_LIMIT === 0) return;

        const totalTime = TIME_LIMIT * 60;
        const elapsed = Math.floor(Math.abs(ELAPSED_SECONDS));
        timeRemaining = Math.max(0, totalTime - elapsed);

        console.log('Timer initialized:', {
            timeLimit: TIME_LIMIT + ' minutes',
            totalSeconds: totalTime,
            elapsedSeconds: elapsed,
            remainingSeconds: timeRemaining,
            remainingMinutes: Math.floor(timeRemaining / 60)
        });

        if (timeRemaining <= 0) {
            Toast.fire({
                icon: 'error',
                title: 'Time has expired!'
            });
            setTimeout(() => {
                window.location.href = API_ROUTES.backToQuiz;
            }, 2000);
            return;
        }

        updateTimerDisplay();
        
        timerInterval = setInterval(function() {
            timeRemaining--;
            updateTimerDisplay();

            if (timeRemaining === 300) {
                $('.quiz-timer').addClass('warning');
                Toast.fire({
                    icon: 'warning',
                    title: '5 minutes remaining!'
                });
            }

            if (timeRemaining === 60) {
                Toast.fire({
                    icon: 'error',
                    title: '1 minute remaining!'
                });
            }

            if (timeRemaining <= 0) {
                clearInterval(timerInterval);
                clearInterval(heartbeatInterval);
                clearInterval(autoSaveInterval);
                
                Toast.fire({
                    icon: 'error',
                    title: 'Time is up! Submitting quiz...'
                });
                setTimeout(() => submitQuiz(true), 2000);
            }
        }, 1000);
    }

    function updateTimerDisplay() {
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = Math.floor(timeRemaining % 60);
        const display = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        $('#timerDisplay').text(display);
        $('#timerDisplayMobile').text(display);

        if (timeRemaining <= 300) {
            $('.quiz-timer').addClass('warning');
        }
    }

    function navigateToQuestion(index) {
        if (index < 0 || index >= QUESTIONS.length) return;

        $('.question-item').hide();
        $(`#question-${index}`).show();
        
        $('.question-nav-btn').removeClass('active');
        $(`.question-nav-btn[data-index="${index}"]`).addClass('active');
        
        currentQuestionIndex = index;
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    function updateNavigationButton(questionIndex) {
        const question = QUESTIONS[questionIndex];
        const hasAnswer = answers[question.id];
        const $navBtn = $(`.question-nav-btn[data-index="${questionIndex}"]`);
        
        if (hasAnswer) {
            if (question.question_type === 'essay') {
                if (hasAnswer.answer_text && hasAnswer.answer_text.trim().length > 0) {
                    $navBtn.addClass('answered');
                } else {
                    $navBtn.removeClass('answered');
                }
            } else {
                if (hasAnswer.option_id !== undefined && hasAnswer.option_id !== null) {
                    $navBtn.addClass('answered');
                } else {
                    $navBtn.removeClass('answered');
                }
            }
        } else {
            $navBtn.removeClass('answered');
        }
    }

    function showReviewModal() {
        let answeredCount = 0;

        QUESTIONS.forEach((question, index) => {
            const hasAnswer = answers[question.id];
            const $btn = $(`.question-review-btn[data-question-id="${question.id}"]`);
            
            let isAnswered = false;
            
            if (hasAnswer) {
                if (question.question_type === 'essay') {
                    isAnswered = hasAnswer.answer_text && hasAnswer.answer_text.length > 0;
                } else {
                    isAnswered = hasAnswer.option_id !== undefined;
                }
            }

            if (isAnswered) {
                $btn.removeClass('btn-outline-secondary btn-outline-primary')
                    .addClass('btn-success');
                answeredCount++;
            } else {
                $btn.removeClass('btn-success btn-outline-primary')
                    .addClass('btn-outline-secondary');
            }
        });

        $('#answeredCount').text(answeredCount);

        if (answeredCount < QUESTIONS.length) {
            $('#reviewWarning').show();
        } else {
            $('#reviewWarning').hide();
        }

        $('#reviewModal').modal('show');
    }

    $(document).on('click', '.question-review-btn', function() {
        const index = parseInt($(this).data('index'));
        
        if (viewMode === 'showAll') {
            $('#viewModeToggle').trigger('click');
            setTimeout(() => navigateToQuestion(index), 100);
        } else {
            navigateToQuestion(index);
        }
    });

    function submitQuiz(timeExpired = false, violationSubmit = false) {
        if (!timeExpired && !violationSubmit && Object.keys(answers).length === 0) {
            Toast.fire({
                icon: 'warning',
                title: 'Please answer at least one question'
            });
            return;
        }

        saveProgressToServer(true);

        const answersArray = Object.values(answers);

        if (!timeExpired && !violationSubmit) {
            Swal.fire({
                title: 'Submit Quiz?',
                text: "You cannot change your answers after submission.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#007bff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, submit it!',
                cancelButtonText: 'Continue answering'
            }).then((result) => {
                if (result.isConfirmed) {
                    performSubmit(answersArray, timeExpired, violationSubmit);
                }
            });
        } else {
            performSubmit(answersArray, timeExpired, violationSubmit);
        }
    }

    function performSubmit(answersArray, timeExpired, violationSubmit) {
        $('#reviewModal').modal('hide');
        
        isSubmitting = true;
        
        const submitBtn = $('#confirmSubmitBtn, #submitQuizBtn');
        const originalHtml = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');

        if (timerInterval) clearInterval(timerInterval);
        if (heartbeatInterval) clearInterval(heartbeatInterval);
        if (autoSaveInterval) clearInterval(autoSaveInterval);

        $.ajax({
            url: API_ROUTES.submitQuiz,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({ 
                answers: answersArray,
                violation_submit: violationSubmit
            }),
            success: function(response) {
                if (response.success) {
                    if (!response.data.has_essay) {
                        const percentage = response.data.percentage;
                        const passed = percentage >= PASSING_SCORE;
                        
                        let violationNote = violationSubmit ? 
                            '<p class="text-danger small"><i class="fas fa-exclamation-triangle"></i> Auto-submitted due to tab switch violations</p>' : '';
                        
                        Swal.fire({
                            title: passed ? 'Congratulations!' : 'Quiz Completed',
                            html: `
                                <div class="text-center">
                                    <i class="fas fa-${passed ? 'trophy' : 'clipboard-check'} fa-3x text-${passed ? 'success' : 'info'} mb-3"></i>
                                    <h4>Your Score</h4>
                                    <h2 class="text-primary">${response.data.score} / ${response.data.total_points}</h2>
                                    <h3 class="text-${passed ? 'success' : 'danger'}">${percentage}%</h3>
                                    <p class="mb-0">
                                        <strong class="text-${passed ? 'success' : 'danger'}">${passed ? 'PASSED' : 'FAILED'}</strong>
                                    </p>
                                    ${violationNote}
                                </div>
                            `,
                            icon: passed ? 'success' : 'info',
                            confirmButtonText: 'View Results',
                            confirmButtonColor: '#007bff',
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.href = API_ROUTES.backToQuiz;
                        });
                    } else {
                        let violationNote = violationSubmit ? 
                            '<p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Auto-submitted due to tab switch violations</p>' : '';
                        
                        Swal.fire({
                            title: 'Quiz Submitted!',
                            html: `
                                <div class="text-center">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <p>Your answers have been submitted successfully.</p>
                                    <p class="text-muted">Some questions require manual grading. Your final score will be available once grading is complete.</p>
                                    ${violationNote}
                                </div>
                            `,
                            icon: 'success',
                            confirmButtonText: 'Back to Quiz',
                            confirmButtonColor: '#007bff',
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.href = API_ROUTES.backToQuiz;
                        });
                    }
                } else {
                    isSubmitting = false;
                    Toast.fire({
                        icon: 'error',
                        title: response.message || 'Failed to submit quiz'
                    });
                    submitBtn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr) {
                console.error('Error submitting quiz:', xhr);
                isSubmitting = false;
                
                if (xhr.status === 410 && xhr.responseJSON?.time_expired) {
                    handleTimeExpired();
                } else {
                    let errorMsg = 'Failed to submit quiz';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    
                    Toast.fire({
                        icon: 'error',
                        title: errorMsg
                    });
                    submitBtn.prop('disabled', false).html(originalHtml);
                }
            }
        });
    }

    function loadSavedAnswers() {
        QUESTIONS.forEach((question, index) => {
            const savedAnswer = question.saved_answer;
            if (savedAnswer) {
                answers[question.id] = savedAnswer;
                
                if (question.question_type === 'essay') {
                    $(`#essay_${index}`).val(savedAnswer.answer_text || '');
                } else if (question.question_type === 'short_answer') {
                    $(`#short_${index}`).val(savedAnswer.answer_text || '');
                } else if (question.question_type === 'multiple_answer') {
                    if (savedAnswer.option_ids && Array.isArray(savedAnswer.option_ids)) {
                        savedAnswer.option_ids.forEach(optionId => {
                            const checkbox = $(`input[name="question_${question.id}[]"][value="${optionId}"]`);
                            checkbox.prop('checked', true);
                            checkbox.closest('.option-card').addClass('selected');
                        });
                    }
                } else if (savedAnswer.option_id) {
                    const radio = $(`input[name="question_${question.id}"][value="${savedAnswer.option_id}"]`);
                    radio.prop('checked', true);
                    radio.closest('.option-card').addClass('selected');
                }
                updateNavigationButton(index);
            }
        });
        
        lastSavedAnswers = JSON.parse(JSON.stringify(answers));
    }
});