$(document).ready(function() {
    let questions = [];
    let selectedType = null;
    let editingIndex = null;

    const questionTypes = {
        multiple_choice: { name: 'Multiple Choice', icon: 'fa-check-circle', color: 'primary' },
        multiple_answer: { name: 'Multiple Answer', icon: 'fa-check-double', color: 'primary' },
        true_false: { name: 'True/False', icon: 'fa-toggle-on', color: 'primary' },
        short_answer: { name: 'Short Answer', icon: 'fa-font', color: 'primary' },
        essay: { name: 'Essay', icon: 'fa-file-alt', color: 'primary' }
    };

    // Initialize
    if (IS_EDIT) loadQuizData();

    // Question Type Selection
    $('.question-type-card').on('click', function() {
        selectedType = $(this).data('type');
        $('.question-type-card').removeClass('selected');
        $(this).addClass('selected');
        showQuestionForm(selectedType);
    });

    $('#changeTypeBtn').on('click', function() {
        resetQuestionForm();
    });

    $('#cancelQuestionBtn').on('click', function() {
        resetQuestionForm();
    });

    // Add Option Button
    $('#addOptionBtn').on('click', function() {
        const count = $('#optionsList .option-item').length;
        if (count >= MAX_OPTIONS) {
            toastr.warning(`Maximum ${MAX_OPTIONS} options allowed`);
            return;
        }
        addOptionRow(selectedType);
        updateOptionLimitHint();
    });

    // Add Accepted Answer Button
    $('#addAcceptedAnswerBtn').on('click', function() {
        addAcceptedAnswerRow();
    });

    // Remove Option
    $(document).on('click', '.remove-option-btn', function() {
        const container = $(this).closest('.option-item');
        const minOptions = selectedType === 'true_false' ? 2 : 2;
        if ($('#optionsList .option-item').length > minOptions) {
            container.remove();
            updateOptionLetters();
            updateOptionLimitHint();
        } else {
            toastr.warning(`Minimum ${minOptions} options required`);
        }
    });

    // Remove Accepted Answer
    $(document).on('click', '.remove-answer-btn', function() {
        if ($('#acceptedAnswersList .accepted-answer-item').length > 1) {
            $(this).closest('.accepted-answer-item').remove();
        } else {
            toastr.warning('At least one answer is required');
        }
    });

    // Add Question Button
    $('#addQuestionBtn').on('click', function() {
        addQuestion();
    });

    // Edit Question
    $(document).on('click', '.edit-question-btn', function(e) {
        e.stopPropagation();
        const index = $(this).data('index');
        openEditModal(index);
    });

    // Delete Question
    $(document).on('click', '.delete-question-btn', function(e) {
        e.stopPropagation();
        const index = $(this).data('index');
        if (confirm('Delete this question?')) {
            questions.splice(index, 1);
            updateQuestionList();
            toastr.success('Question deleted');
        }
    });

    // Save Edit
    $('#saveEditBtn').on('click', function() {
        saveEditedQuestion();
    });

    // Save Quiz
    $('#saveQuiz').on('click', saveQuiz);

    function showQuestionForm(type) {
        $('#questionTypeSelector').hide();
        $('#questionFormContainer').show();

        const typeInfo = questionTypes[type];
        $('#selectedTypeIcon').html(`<i class="fas ${typeInfo.icon} text-${typeInfo.color}"></i>`);
        $('#selectedTypeName').text(typeInfo.name);

        // Reset containers
        $('#optionsContainer, #shortAnswerContainer, #essayNote').hide();
        $('#optionsList, #acceptedAnswersList').empty();

        switch(type) {
            case 'multiple_choice':
                setupMultipleChoice();
                break;
            case 'multiple_answer':
                setupMultipleAnswer();
                break;
            case 'true_false':
                setupTrueFalse();
                break;
            case 'short_answer':
                setupShortAnswer();
                break;
            case 'essay':
                $('#essayNote').show();
                $('#questionPoints').val(10);
                break;
        }
    }

    function setupMultipleChoice() {
        $('#optionsContainer').show();
        $('#addOptionBtn').show();p
        $('#questionPoints').val(1);

        for (let i = 0; i < 4; i++) addOptionRow('multiple_choice');
        updateOptionLimitHint();
    }

    function setupMultipleAnswer() {
        $('#optionsContainer').show();
        $('#addOptionBtn').show();
        $('#questionPoints').val(1);

        for (let i = 0; i < 4; i++) addOptionRow('multiple_answer');
        updateOptionLimitHint();
    }

    function setupTrueFalse() {
        $('#optionsContainer').show();
        $('#addOptionBtn').hide();
        $('#optionsHint').text('Select the correct answer');
        $('#questionPoints').val(1);

        const html = `
            <div class="option-item d-flex align-items-center">
                <div class="input-group-text mr-2"><input type="radio" name="correctOption" value="0"></div>
                <input type="text" class="form-control option-text" value="True" readonly>
            </div>
            <div class="option-item d-flex align-items-center">
                <div class="input-group-text mr-2"><input type="radio" name="correctOption" value="1"></div>
                <input type="text" class="form-control option-text" value="False" readonly>
            </div>
        `;
        $('#optionsList').html(html);
    }

    function setupShortAnswer() {
        $('#shortAnswerContainer').show();
        $('#questionPoints').val(1);
        addAcceptedAnswerRow();
    }

    function addOptionRow(type, text = '', isCorrect = false) {
        const index = $('#optionsList .option-item').length;
        const letter = String.fromCharCode(65 + index);
        const inputType = type === 'multiple_answer' ? 'checkbox' : 'radio';
        const inputName = type === 'multiple_answer' ? `correctOption${index}` : 'correctOption';

        const html = `
            <div class="option-item d-flex align-items-center">
                <span class="badge badge-secondary mr-2" style="width:25px;">${letter}</span>
                <div class="input-group-text mr-2">
                    <input type="${inputType}" name="${inputName}" value="${index}" ${isCorrect ? 'checked' : ''}>
                </div>
                <input type="text" class="form-control option-text" placeholder="Option ${letter}" value="${escapeHtml(text)}">
                <button type="button" class="btn btn-sm btn-danger ml-2 remove-option-btn"><i class="fas fa-times"></i></button>
            </div>
        `;
        $('#optionsList').append(html);
    }

    function addAcceptedAnswerRow(text = '') {
        const index = $('#acceptedAnswersList .accepted-answer-item').length;
        const html = `
            <div class="accepted-answer-item input-group mb-2">
                <div class="input-group-prepend"><span class="input-group-text">${index + 1}</span></div>
                <input type="text" class="form-control accepted-answer-text" placeholder="Accepted answer..." value="${escapeHtml(text)}">
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger remove-answer-btn"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `;
        $('#acceptedAnswersList').append(html);
    }

    function updateOptionLetters() {
        $('#optionsList .option-item').each(function(i) {
            const letter = String.fromCharCode(65 + i);
            $(this).find('.badge').text(letter);
            $(this).find('.option-text').attr('placeholder', `Option ${letter}`);
            $(this).find('input[type="radio"], input[type="checkbox"]').val(i);
        });
    }

    function updateOptionLimitHint() {
        const count = $('#optionsList .option-item').length;
        $('#optionLimitHint').text(`${count}/${MAX_OPTIONS} options`);
    }

    function validateOptions() {
        const options = [];
        let hasDuplicate = false;
        
        $('#optionsList .option-text').each(function() {
            const text = $(this).val().trim().toLowerCase();
            if (text && options.includes(text)) {
                hasDuplicate = true;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
                if (text) options.push(text);
            }
        });

        if (hasDuplicate) {
            toastr.error('Duplicate options are not allowed');
            return false;
        }
        return true;
    }

    function validateAcceptedAnswers() {
        const answers = [];
        let hasDuplicate = false;

        $('#acceptedAnswersList .accepted-answer-text').each(function() {
            const text = $(this).val().trim().toLowerCase();
            if (text && answers.includes(text)) {
                hasDuplicate = true;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
                if (text) answers.push(text);
            }
        });

        if (hasDuplicate) {
            toastr.error('Duplicate answers are not allowed');
            return false;
        }
        return true;
    }

    function addQuestion() {
        const questionText = $('#questionText').val().trim();
        const points = parseFloat($('#questionPoints').val());

        if (!questionText) {
            toastr.warning('Please enter a question');
            return;
        }
        if (isNaN(points) || points <= 0) {
            toastr.warning('Please enter valid points');
            return;
        }

        let question = {
            question_text: questionText,
            question_type: selectedType,
            points: points
        };

        // Validate and collect data based on type
        switch(selectedType) {
            case 'multiple_choice':
            case 'multiple_answer':
                if (!validateOptions()) return;
                
                const options = [];
                let hasCorrect = false;
                const isMultiple = selectedType === 'multiple_answer';

                $('#optionsList .option-item').each(function(i) {
                    const text = $(this).find('.option-text').val().trim();
                    if (!text) return;
                    
                    const isCorrect = isMultiple 
                        ? $(this).find('input[type="checkbox"]').is(':checked')
                        : $(this).find('input[type="radio"]').is(':checked');
                    
                    if (isCorrect) hasCorrect = true;
                    options.push({ text, is_correct: isCorrect ? 1 : 0 });
                });

                if (options.length < 2) {
                    toastr.warning('Add at least 2 options');
                    return;
                }
                if (!hasCorrect) {
                    toastr.warning('Select at least one correct answer');
                    return;
                }
                question.options = options;
                break;

            case 'true_false':
                const tfCorrect = $('input[name="correctOption"]:checked').val();
                if (tfCorrect === undefined) {
                    toastr.warning('Select the correct answer');
                    return;
                }
                question.options = [
                    { text: 'True', is_correct: tfCorrect === '0' ? 1 : 0 },
                    { text: 'False', is_correct: tfCorrect === '1' ? 1 : 0 }
                ];
                break;

            case 'short_answer':
                if (!validateAcceptedAnswers()) return;
                
                const acceptedAnswers = [];
                $('#acceptedAnswersList .accepted-answer-text').each(function() {
                    const text = $(this).val().trim();
                    if (text) acceptedAnswers.push(text);
                });

                if (acceptedAnswers.length === 0) {
                    toastr.warning('Add at least one accepted answer');
                    return;
                }
                question.accepted_answers = acceptedAnswers;
                question.exact_match = $('#exactMatch').is(':checked');
                break;
        }

        questions.push(question);
        updateQuestionList();
        resetQuestionForm();
        toastr.success('Question added');
    }

    function updateQuestionList() {
        const nav = $('#questionNav');
        
        if (questions.length === 0) {
            nav.html('<li class="nav-item text-center text-muted p-3"><small>No questions added yet</small></li>');
            $('#totalQuestions').text('0');
            $('#totalPoints').text('0');
            return;
        }

        let html = '';
        let totalPts = 0;

        questions.forEach((q, i) => {
            totalPts += parseFloat(q.points);
            const type = questionTypes[q.question_type];
            const preview = q.question_text.substring(0, 40) + (q.question_text.length > 40 ? '...' : '');

            html += `
                <li class="nav-item question-item p-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div style="flex:1; cursor:pointer;" class="edit-question-btn" data-index="${i}">
                            <span class="badge badge-${type.color} mr-1">${i + 1}</span>
                            <i class="fas ${type.icon} text-${type.color} mr-1"></i>
                            <small>${escapeHtml(preview)}</small>
                            <span class="badge badge-light ml-1">${q.points}pts</span>
                        </div>
                        <div>
                            <button class="btn btn-xs btn-info edit-question-btn mr-1" data-index="${i}"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-xs btn-danger delete-question-btn" data-index="${i}"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </li>
            `;
        });

        nav.html(html);
        $('#totalQuestions').text(questions.length);
        $('#totalPoints').text(totalPts.toFixed(2));
    }

    function openEditModal(index) {
        editingIndex = index;
        const q = questions[index];
        const type = questionTypes[q.question_type];

        let html = `
            <div class="form-group">
                <label>Question Type</label>
                <p><i class="fas ${type.icon} text-${type.color}"></i> ${type.name}</p>
            </div>
            <div class="form-group">
                <label>Question Text <span class="text-danger">*</span></label>
                <textarea class="form-control" id="editQuestionText" rows="3">${escapeHtml(q.question_text)}</textarea>
            </div>
            <div class="form-group">
                <label>Points</label>
                <input type="number" class="form-control" id="editQuestionPoints" value="${q.points}" min="0.01" step="0.01">
            </div>
        `;

        if (q.question_type === 'multiple_choice' || q.question_type === 'multiple_answer') {
            const isMultiple = q.question_type === 'multiple_answer';
            html += `<div class="form-group"><label>Options</label><div id="editOptionsList">`;
            q.options.forEach((opt, i) => {
                const letter = String.fromCharCode(65 + i);
                const inputType = isMultiple ? 'checkbox' : 'radio';
                html += `
                    <div class="option-item d-flex align-items-center edit-option-item">
                        <span class="badge badge-secondary mr-2">${letter}</span>
                        <div class="input-group-text mr-2">
                            <input type="${inputType}" name="editCorrectOption" value="${i}" ${opt.is_correct ? 'checked' : ''}>
                        </div>
                        <input type="text" class="form-control edit-option-text" value="${escapeHtml(opt.text)}">
                        <button type="button" class="btn btn-sm btn-danger ml-2 edit-remove-option"><i class="fas fa-times"></i></button>
                    </div>
                `;
            });
            html += `</div><button type="button" class="btn btn-sm btn-outline-primary mt-2" id="editAddOption"><i class="fas fa-plus"></i> Add</button></div>`;
        } else if (q.question_type === 'true_false') {
            html += `
                <div class="form-group"><label>Correct Answer</label>
                    <div class="option-item"><input type="radio" name="editTFCorrect" value="0" ${q.options[0].is_correct ? 'checked' : ''}> True</div>
                    <div class="option-item"><input type="radio" name="editTFCorrect" value="1" ${q.options[1].is_correct ? 'checked' : ''}> False</div>
                </div>
            `;
        } else if (q.question_type === 'short_answer') {
            html += `<div class="form-group"><label>Accepted Answers</label><div id="editAcceptedList">`;
            q.accepted_answers.forEach((ans, i) => {
                html += `
                    <div class="input-group mb-2 edit-accepted-item">
                        <input type="text" class="form-control edit-accepted-text" value="${escapeHtml(ans)}">
                        <div class="input-group-append"><button type="button" class="btn btn-danger edit-remove-accepted"><i class="fas fa-times"></i></button></div>
                    </div>
                `;
            });
            html += `</div><button type="button" class="btn btn-sm btn-outline-primary" id="editAddAccepted"><i class="fas fa-plus"></i> Add</button>
                <div class="custom-control custom-switch mt-2">
                    <input type="checkbox" class="custom-control-input" id="editExactMatch" ${q.exact_match ? 'checked' : ''}>
                    <label class="custom-control-label" for="editExactMatch">Require exact match</label>
                </div></div>
            `;
        }

        $('#editQuestionBody').html(html);
        $('#editQuestionModal').modal('show');

        // Bind edit modal events
        $('#editAddOption').off('click').on('click', function() {
            const count = $('#editOptionsList .edit-option-item').length;
            if (count >= MAX_OPTIONS) { toastr.warning(`Max ${MAX_OPTIONS} options`); return; }
            const letter = String.fromCharCode(65 + count);
            const inputType = q.question_type === 'multiple_answer' ? 'checkbox' : 'radio';
            $('#editOptionsList').append(`
                <div class="option-item d-flex align-items-center edit-option-item">
                    <span class="badge badge-secondary mr-2">${letter}</span>
                    <div class="input-group-text mr-2"><input type="${inputType}" name="editCorrectOption" value="${count}"></div>
                    <input type="text" class="form-control edit-option-text" placeholder="Option ${letter}">
                    <button type="button" class="btn btn-sm btn-danger ml-2 edit-remove-option"><i class="fas fa-times"></i></button>
                </div>
            `);
        });

        $('#editAddAccepted').off('click').on('click', function() {
            $('#editAcceptedList').append(`
                <div class="input-group mb-2 edit-accepted-item">
                    <input type="text" class="form-control edit-accepted-text" placeholder="Accepted answer">
                    <div class="input-group-append"><button type="button" class="btn btn-danger edit-remove-accepted"><i class="fas fa-times"></i></button></div>
                </div>
            `);
        });

        $(document).off('click', '.edit-remove-option').on('click', '.edit-remove-option', function() {
            if ($('#editOptionsList .edit-option-item').length > 2) $(this).closest('.edit-option-item').remove();
            else toastr.warning('Minimum 2 options required');
        });

        $(document).off('click', '.edit-remove-accepted').on('click', '.edit-remove-accepted', function() {
            if ($('#editAcceptedList .edit-accepted-item').length > 1) $(this).closest('.edit-accepted-item').remove();
            else toastr.warning('At least one answer required');
        });
    }

    function saveEditedQuestion() {
        const q = questions[editingIndex];
        q.question_text = $('#editQuestionText').val().trim();
        q.points = parseFloat($('#editQuestionPoints').val());

        if (!q.question_text) { toastr.warning('Enter question text'); return; }

        if (q.question_type === 'multiple_choice' || q.question_type === 'multiple_answer') {
            const opts = [];
            let hasCorrect = false;
            $('#editOptionsList .edit-option-item').each(function(i) {
                const text = $(this).find('.edit-option-text').val().trim();
                if (!text) return;
                const isCorrect = $(this).find('input').is(':checked');
                if (isCorrect) hasCorrect = true;
                opts.push({ text, is_correct: isCorrect ? 1 : 0 });
            });
            if (opts.length < 2) { toastr.warning('Add at least 2 options'); return; }
            if (!hasCorrect) { toastr.warning('Select correct answer'); return; }
            q.options = opts;
        } else if (q.question_type === 'true_false') {
            const val = $('input[name="editTFCorrect"]:checked').val();
            q.options = [
                { text: 'True', is_correct: val === '0' ? 1 : 0 },
                { text: 'False', is_correct: val === '1' ? 1 : 0 }
            ];
        } else if (q.question_type === 'short_answer') {
            const answers = [];
            $('#editAcceptedList .edit-accepted-text').each(function() {
                const t = $(this).val().trim();
                if (t) answers.push(t);
            });
            if (answers.length === 0) { toastr.warning('Add at least one answer'); return; }
            q.accepted_answers = answers;
            q.exact_match = $('#editExactMatch').is(':checked');
        }

        updateQuestionList();
        $('#editQuestionModal').modal('hide');
        toastr.success('Question updated');
    }

    function resetQuestionForm() {
        selectedType = null;
        $('#questionFormContainer').hide();
        $('#questionTypeSelector').show();
        $('.question-type-card').removeClass('selected');
        $('#questionText').val('');
        $('#questionPoints').val(1);
        $('#optionsList, #acceptedAnswersList').empty();
    }

    function saveQuiz() {
        const title = $('#quizTitle').val().trim();
        if (!title) { toastr.warning('Enter quiz title'); return; }
        if (questions.length === 0) { toastr.warning('Add at least one question'); return; }

        const data = {
            title,
            description: $('#quizDescription').val().trim(),
            time_limit: parseInt($('#timeLimit').val()) || null,
            passing_score: parseFloat($('#passingScore').val()),
            max_attempts: parseInt($('#maxAttempts').val()),
            show_results: $('#showResults').is(':checked') ? 1 : 0,
            shuffle_questions: $('#shuffleQuestions').is(':checked') ? 1 : 0,
            questions
        };

        const btn = $('#saveQuiz');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

        $.ajax({
            url: API_ROUTES.submitQuiz,
            method: IS_EDIT ? 'PUT' : 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'Content-Type': 'application/json' },
            data: JSON.stringify(data),
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message || 'Quiz saved');
                    setTimeout(() => window.location.href = API_ROUTES.backToLessons, 1000);
                } else {
                    toastr.error(res.message || 'Failed');
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Quiz');
                }
            },
            error: function(xhr) {
                let msg = 'Failed to save';
                if (xhr.responseJSON?.message) msg = xhr.responseJSON.message;
                toastr.error(msg);
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Quiz');
            }
        });
    }

    function loadQuizData() {
        $.ajax({
            url: API_ROUTES.getQuizData,
            method: 'GET',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
            success: function(res) {
                if (res.success) populateForm(res.data);
                else toastr.error(res.message || 'Failed to load');
            },
            error: () => toastr.error('Failed to load quiz')
        });
    }

    function populateForm(data) {
        $('#quizTitle').val(data.quiz.title);
        $('#quizDescription').val(data.quiz.description || '');
        $('#timeLimit').val(data.quiz.time_limit || '');
        $('#passingScore').val(data.quiz.passing_score);
        $('#maxAttempts').val(data.quiz.max_attempts);
        $('#showResults').prop('checked', data.quiz.show_results == 1);
        $('#shuffleQuestions').prop('checked', data.quiz.shuffle_questions == 1);

        questions = data.questions.map(q => {
            const mapped = {
                question_text: q.question_text,
                question_type: q.question_type,
                points: parseFloat(q.points)
            };
            if (q.options) {
                mapped.options = q.options.map(o => ({ text: o.option_text, is_correct: o.is_correct }));
            }
            if (q.accepted_answers) {
                mapped.accepted_answers = q.accepted_answers;
                mapped.exact_match = q.exact_match;
            }
            return mapped;
        });
        updateQuestionList();
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text.toString().replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
    }
});