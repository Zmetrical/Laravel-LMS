$(document).ready(function() {
    let questions = [];
    let editingQuestionIndex = null;
    let optionCounter = 4; // Start with D for multiple choice

    // Initialize
    if (IS_EDIT) {
        loadQuizData();
    }

    initializeMultipleChoiceOptions();

    // Add Multiple Choice Question
    $('#addMCQuestion').on('click', function() {
        const questionText = $('#mcQuestion').val().trim();
        const points = parseFloat($('#mcPoints').val());
        const options = [];
        let correctIndex = null;

        $('.mc-option').each(function(index) {
            const text = $(this).val().trim();
            if (text) {
                options.push({
                    text: text,
                    is_correct: $('input[name="mcCorrect"]:checked').val() == index ? 1 : 0
                });
                if ($('input[name="mcCorrect"]:checked').val() == index) {
                    correctIndex = index;
                }
            }
        });

        // Validation
        if (!questionText) {
            toastr.warning('Please enter a question');
            return;
        }
        if (options.length < 2) {
            toastr.warning('Please add at least 2 options');
            return;
        }
        if (correctIndex === null) {
            toastr.warning('Please select the correct answer');
            return;
        }

        const question = {
            question_text: questionText,
            question_type: 'multiple_choice',
            points: points,
            options: options
        };

        questions.push(question);
        updateQuestionList();
        clearMCForm();
        toastr.success('Question added successfully');
    });

    // Add True/False Question
    $('#addTFQuestion').on('click', function() {
        const questionText = $('#tfQuestion').val().trim();
        const points = parseFloat($('#tfPoints').val());
        const correctAnswer = $('input[name="tfCorrect"]:checked').val();

        // Validation
        if (!questionText) {
            toastr.warning('Please enter a question');
            return;
        }
        if (!correctAnswer) {
            toastr.warning('Please select the correct answer');
            return;
        }

        const question = {
            question_text: questionText,
            question_type: 'true_false',
            points: points,
            options: [
                { text: 'True', is_correct: correctAnswer === 'true' ? 1 : 0 },
                { text: 'False', is_correct: correctAnswer === 'false' ? 1 : 0 }
            ]
        };

        questions.push(question);
        updateQuestionList();
        clearTFForm();
        toastr.success('Question added successfully');
    });

    // Add Essay Question
    $('#addEssayQuestion').on('click', function() {
        const questionText = $('#essayQuestion').val().trim();
        const points = parseFloat($('#essayPoints').val());

        // Validation
        if (!questionText) {
            toastr.warning('Please enter a question');
            return;
        }

        const question = {
            question_text: questionText,
            question_type: 'essay',
            points: points
        };

        questions.push(question);
        updateQuestionList();
        clearEssayForm();
        toastr.success('Question added successfully');
    });

    // Add MC Option
    $('#addMCOption').on('click', function() {
        const letter = String.fromCharCode(65 + optionCounter); // A=65
        const optionIndex = optionCounter;
        
        const optionHtml = `
            <div class="input-group mb-2 mc-option-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">${letter}</span>
                </div>
                <input type="text" class="form-control mc-option" placeholder="Option ${letter}">
                <div class="input-group-append">
                    <div class="input-group-text option-correct-indicator">
                        <input type="radio" name="mcCorrect" value="${optionIndex}">
                    </div>
                    <button class="btn btn-danger btn-sm remove-option" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        
        $('#mcOptions').append(optionHtml);
        optionCounter++;
        updateRemoveButtons();
    });

    // Remove MC Option
    $(document).on('click', '.remove-option', function() {
        $(this).closest('.mc-option-group').remove();
        updateOptionLetters();
        updateRemoveButtons();
    });

    // Save Quiz
    $('#saveQuiz').on('click', function() {
        saveQuiz();
    });

    // Question navigation click
    $(document).on('click', '.question-nav-item', function() {
        const index = $(this).data('index');
        viewQuestion(index);
    });

    // Delete question from list
    $(document).on('click', '.delete-question-btn', function(e) {
        e.stopPropagation();
        const index = $(this).data('index');
        deleteQuestion(index);
    });

    function initializeMultipleChoiceOptions() {
        // Add initial 4 options (A, B, C, D)
        const letters = ['A', 'B', 'C', 'D'];
        $('#mcOptions').empty();
        
        letters.forEach((letter, index) => {
            const optionHtml = `
                <div class="input-group mb-2 mc-option-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">${letter}</span>
                    </div>
                    <input type="text" class="form-control mc-option" placeholder="Option ${letter}">
                    <div class="input-group-append">
                        <div class="input-group-text option-correct-indicator">
                            <input type="radio" name="mcCorrect" value="${index}">
                        </div>
                        <button class="btn btn-danger btn-sm remove-option" type="button" style="display:none;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
            $('#mcOptions').append(optionHtml);
        });
        
        optionCounter = 4;
        updateRemoveButtons();
    }

    function updateOptionLetters() {
        $('.mc-option-group').each(function(index) {
            const letter = String.fromCharCode(65 + index);
            $(this).find('.input-group-text').first().text(letter);
            $(this).find('input[type="radio"]').val(index);
            $(this).find('.mc-option').attr('placeholder', 'Option ' + letter);
        });
        optionCounter = $('.mc-option-group').length;
    }

    function updateRemoveButtons() {
        const optionCount = $('.mc-option-group').length;
        if (optionCount <= 2) {
            $('.remove-option').hide();
        } else {
            $('.remove-option').show();
        }
    }

    function updateQuestionList() {
        const nav = $('#questionNav');
        const totalQuestionsSpan = $('#totalQuestions');
        const totalPointsSpan = $('#totalPoints');

        if (questions.length === 0) {
            nav.html(`
                <li class="nav-item text-center text-muted p-3">
                    <small>No questions added yet</small>
                </li>
            `);
            totalQuestionsSpan.text('0');
            totalPointsSpan.text('0');
            return;
        }

        let html = '';
        let totalPoints = 0;

        questions.forEach((q, index) => {
            totalPoints += parseFloat(q.points);
            const icon = getQuestionTypeIcon(q.question_type);
            const preview = q.question_text.substring(0, 50) + (q.question_text.length > 50 ? '...' : '');

            html += `
                <li class="nav-item">
                    <a href="#" class="nav-link question-nav-item" data-index="${index}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div style="flex: 1;">
                                <span class="badge badge-primary mr-1">${index + 1}</span>
                                <i class="fas fa-${icon} mr-1"></i>
                                <small>${escapeHtml(preview)}</small>
                            </div>
                            <div>
                                <span class="badge badge-info">${q.points}pts</span>
                                <button class="btn btn-xs btn-danger delete-question-btn ml-1" data-index="${index}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </a>
                </li>
            `;
        });

        nav.html(html);
        totalQuestionsSpan.text(questions.length);
        totalPointsSpan.text(totalPoints.toFixed(2));
    }

    function viewQuestion(index) {
        const question = questions[index];
        
        // Highlight in navigation
        $('.question-nav-item').removeClass('active');
        $(`.question-nav-item[data-index="${index}"]`).addClass('active');

        // Show question details (could open in a modal or separate panel)
        let detailsHtml = `
            <div class="alert alert-info">
                <strong>Question ${index + 1}</strong> (${question.points} points)
                <button class="btn btn-sm btn-danger float-right delete-question-btn" data-index="${index}">
                    <i class="fas fa-trash"></i> Delete
                </button>
                <p class="mt-2 mb-0">${escapeHtml(question.question_text)}</p>
        `;

        if (question.options) {
            detailsHtml += '<ul class="mt-2 mb-0">';
            question.options.forEach((opt, i) => {
                const correctBadge = opt.is_correct ? '<span class="badge badge-success ml-2">Correct</span>' : '';
                detailsHtml += `<li>${escapeHtml(opt.text)}${correctBadge}</li>`;
            });
            detailsHtml += '</ul>';
        }

        detailsHtml += '</div>';
        
        // You could display this in a preview area
        // For now, just show as toast
        toastr.info(`Question ${index + 1}: ${question.question_text.substring(0, 100)}...`);
    }

    function deleteQuestion(index) {
        if (!confirm('Are you sure you want to delete this question?')) {
            return;
        }

        questions.splice(index, 1);
        updateQuestionList();
        toastr.success('Question deleted');
    }

    function saveQuiz() {
        // Validate quiz settings
        const title = $('#quizTitle').val().trim();
        const passingScore = parseFloat($('#passingScore').val());
        const maxAttempts = parseInt($('#maxAttempts').val());

        if (!title) {
            toastr.warning('Please enter a quiz title');
            return;
        }

        if (questions.length === 0) {
            toastr.warning('Please add at least one question');
            return;
        }

        if (isNaN(passingScore) || passingScore < 0 || passingScore > 100) {
            toastr.warning('Passing score must be between 0 and 100');
            return;
        }

        const timeLimit = $('#timeLimit').val() ? parseInt($('#timeLimit').val()) : null;

        const quizData = {
            title: title,
            description: $('#quizDescription').val().trim(),
            time_limit: timeLimit,
            passing_score: passingScore,
            max_attempts: maxAttempts,
            show_results: $('#showResults').is(':checked') ? 1 : 0,
            shuffle_questions: $('#shuffleQuestions').is(':checked') ? 1 : 0,
            questions: questions
        };

        const saveBtn = $('#saveQuiz');
        const originalHtml = saveBtn.html();
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: API_ROUTES.submitQuiz,
            method: IS_EDIT ? 'PUT' : 'POST',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify(quizData),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Quiz saved successfully');
                    setTimeout(function() {
                        window.location.href = API_ROUTES.backToLessons;
                    }, 1000);
                } else {
                    toastr.error(response.message || 'Failed to save quiz');
                    saveBtn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr) {
                console.error('Error saving quiz:', xhr);
                let errorMsg = 'Failed to save quiz';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMsg = Object.values(errors).flat().join('<br>');
                }
                
                toastr.error(errorMsg);
                saveBtn.prop('disabled', false).html(originalHtml);
            }
        });
    }

    function loadQuizData() {
        $.ajax({
            url: API_ROUTES.getQuizData,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            success: function(response) {
                if (response.success) {
                    populateQuizForm(response.data);
                } else {
                    toastr.error(response.message || 'Failed to load quiz data');
                }
            },
            error: function(xhr) {
                console.error('Error loading quiz:', xhr);
                toastr.error('Failed to load quiz data');
            }
        });
    }

    function populateQuizForm(data) {
        // Populate settings
        $('#quizTitle').val(data.quiz.title);
        $('#quizDescription').val(data.quiz.description || '');
        $('#timeLimit').val(data.quiz.time_limit || '');
        $('#passingScore').val(data.quiz.passing_score);
        $('#maxAttempts').val(data.quiz.max_attempts);
        $('#showResults').prop('checked', data.quiz.show_results == 1);
        $('#shuffleQuestions').prop('checked', data.quiz.shuffle_questions == 1);

        // Populate questions
        questions = data.questions.map(q => ({
            question_text: q.question_text,
            question_type: q.question_type,
            points: parseFloat(q.points),
            options: q.options ? q.options.map(opt => ({
                text: opt.option_text,
                is_correct: opt.is_correct
            })) : undefined
        }));

        updateQuestionList();
    }

    function clearMCForm() {
        $('#mcQuestion').val('');
        $('#mcPoints').val('1');
        initializeMultipleChoiceOptions();
    }

    function clearTFForm() {
        $('#tfQuestion').val('');
        $('#tfPoints').val('1');
        $('input[name="tfCorrect"]').prop('checked', false);
    }

    function clearEssayForm() {
        $('#essayQuestion').val('');
        $('#essayPoints').val('10');
    }

    function getQuestionTypeIcon(type) {
        const icons = {
            'multiple_choice': 'check-circle',
            'true_false': 'toggle-on',
            'essay': 'file-alt'
        };
        return icons[type] || 'question';
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