$(document).ready(function() {
    let performanceChart = null;

    // Load all dashboard data
    loadDashboardStats();
    loadAvailableQuizzes();
    loadRecentGrades();
    loadPerformanceChart();

    // Refresh buttons
    $('#refreshQuizzes').on('click', function() {
        $(this).find('i').addClass('fa-spin');
        loadAvailableQuizzes();
    });

    $('#refreshGrades').on('click', function() {
        $(this).find('i').addClass('fa-spin');
        loadRecentGrades();
    });

    $('#refreshChart').on('click', function() {
        $(this).find('i').addClass('fa-spin');
        loadPerformanceChart();
    });

    /**
     * Load dashboard statistics
     */
    function loadDashboardStats() {
        $.ajax({
            url: API_ROUTES.getStats,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    updateStatistics(response.data);
                } else {
                    showStatsError();
                }
            },
            error: function(xhr) {
                showStatsError();
                toastr.error('Failed to load statistics');
            }
        });
    }

    /**
     * Update statistics display
     */
    function updateStatistics(data) {
        $('#enrolledClassesCount').html(data.enrolled_classes);
        $('#completedLessonsCount').html(data.completed_lessons);
        $('#pendingQuizzesCount').html(data.pending_quizzes);
        
        const avgGrade = data.average_grade > 0 ? data.average_grade : 'N/A';
        $('#averageGrade').html(avgGrade);
    }

    /**
     * Show error state for statistics
     */
    function showStatsError() {
        $('.info-box-number').html('<span class="text-danger">-</span>');
    }

    /**
     * Load available quizzes
     */
    function loadAvailableQuizzes() {
        $.ajax({
            url: API_ROUTES.getQuizzes,
            method: 'GET',
            success: function(response) {
                $('#refreshQuizzes i').removeClass('fa-spin');
                
                if (response.success) {
                    displayQuizzes(response.data);
                } else {
                    showQuizzesError(response.message);
                }
            },
            error: function(xhr) {
                $('#refreshQuizzes i').removeClass('fa-spin');
                const message = xhr.responseJSON?.message || 'Failed to load quizzes';
                showQuizzesError(message);
            }
        });
    }

    /**
     * Display quizzes list
     */
    function displayQuizzes(quizzes) {
        const container = $('#quizzesContainer');
        container.empty();

        if (quizzes.length === 0) {
            container.html(`
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No available quizzes at the moment</p>
                    <small class="text-muted">Check back later for new quizzes</small>
                </div>
            `);
            return;
        }

        quizzes.forEach(function(quiz) {
            const card = createQuizCard(quiz);
            container.append(card);
        });
    }

    /**
     * Create quiz card
     */
    function createQuizCard(quiz) {
        const now = new Date();
        const deadline = quiz.available_until ? new Date(quiz.available_until) : null;
        const timeLeft = deadline ? Math.ceil((deadline - now) / (1000 * 60 * 60 * 24)) : null;
        
        let urgentClass = '';
        let deadlineHtml = '';
        
        if (deadline) {
            if (timeLeft <= 1) {
                urgentClass = 'urgent';
                deadlineHtml = `
                    <small class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Due ${timeLeft === 0 ? 'today' : 'tomorrow'}
                    </small>
                `;
            } else if (timeLeft <= 3) {
                urgentClass = 'urgent';
                deadlineHtml = `
                    <small class="text-warning">
                        <i class="fas fa-clock"></i>
                        Due in ${timeLeft} days
                    </small>
                `;
            } else {
                deadlineHtml = `
                    <small class="text-muted">
                        <i class="fas fa-clock"></i>
                        Due in ${timeLeft} days
                    </small>
                `;
            }
        } else {
            deadlineHtml = `
                <small class="text-muted">
                    <i class="fas fa-infinity"></i>
                    No deadline
                </small>
            `;
        }

        const attemptsHtml = quiz.max_attempts > 0 
            ? `${quiz.attempts_taken}/${quiz.max_attempts} attempts`
            : `${quiz.attempts_taken} attempts`;

        const card = `
            <div class="quiz-card ${urgentClass} p-3 mb-2 bg-white rounded">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            <strong>${escapeHtml(quiz.title)}</strong>
                        </h6>
                        <p class="text-muted mb-1 small">
                            <i class="fas fa-book"></i> ${escapeHtml(quiz.class_name)}
                        </p>
                        <p class="text-muted mb-2 small">
                            <i class="fas fa-bookmark"></i> ${escapeHtml(quiz.lesson_title)}
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            ${deadlineHtml}
                            ${quiz.time_limit ? `
                                <small class="text-muted ml-3">
                                    <i class="fas fa-hourglass-half"></i>
                                    ${quiz.time_limit} mins
                                </small>
                            ` : ''}
                            <small class="text-muted ml-3">
                                <i class="fas fa-redo"></i>
                                ${attemptsHtml}
                            </small>
                        </div>
                    </div>
                    <div class="ml-2">
                        <button class="btn btn-primary btn-sm take-quiz-btn" 
                                data-quiz-id="${quiz.id}">
                            <i class="fas fa-play"></i> Take Quiz
                        </button>
                    </div>
                </div>
            </div>
        `;

        return card;
    }

    /**
     * Show quizzes error
     */
    function showQuizzesError(message) {
        $('#quizzesContainer').html(`
            <div class="text-center py-4">
                <i class="fas fa-exclamation-circle fa-2x text-danger mb-2"></i>
                <p class="text-muted mb-0">${escapeHtml(message)}</p>
            </div>
        `);
    }

    /**
     * Load recent grades
     */
    function loadRecentGrades() {
        $.ajax({
            url: API_ROUTES.getRecentGrades,
            method: 'GET',
            success: function(response) {
                $('#refreshGrades i').removeClass('fa-spin');
                
                if (response.success) {
                    displayRecentGrades(response.data);
                } else {
                    showGradesError(response.message);
                }
            },
            error: function(xhr) {
                $('#refreshGrades i').removeClass('fa-spin');
                const message = xhr.responseJSON?.message || 'Failed to load grades';
                showGradesError(message);
            }
        });
    }

    /**
     * Display recent grades
     */
    function displayRecentGrades(grades) {
        const container = $('#recentGradesContainer');
        container.empty();

        if (grades.length === 0) {
            container.html(`
                <div class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No recent grades</p>
                    <small class="text-muted">Complete quizzes to see your grades here</small>
                </div>
            `);
            return;
        }

        grades.forEach(function(grade) {
            const item = createGradeItem(grade);
            container.append(item);
        });
    }

    /**
     * Create grade item
     */
    function createGradeItem(grade) {
        const percentage = parseFloat(grade.percentage);
        let gradeClass = 'grade-item';
        let badgeClass = 'secondary';
        
        if (percentage >= 90) {
            gradeClass += ' excellent';
            badgeClass = 'success';
        } else if (percentage >= 80) {
            gradeClass += ' good';
            badgeClass = 'primary';
        } else if (percentage >= 75) {
            gradeClass += ' needs-improvement';
            badgeClass = 'warning';
        } else {
            gradeClass += ' poor';
            badgeClass = 'danger';
        }

        const submittedDate = new Date(grade.submitted_at);
        const formattedDate = submittedDate.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });

        const item = `
            <div class="${gradeClass} rounded">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">
                            <strong>${escapeHtml(grade.quiz_title)}</strong>
                        </h6>
                        <p class="text-muted mb-1 small">
                            <i class="fas fa-book"></i> ${escapeHtml(grade.class_name)}
                        </p>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> ${formattedDate}
                        </small>
                    </div>
                    <div class="text-right">
                        <h5 class="mb-0">
                            <span class="badge badge-${badgeClass}">
                                ${percentage.toFixed(2)}%
                            </span>
                        </h5>
                        <small class="text-muted">
                            ${grade.score}/${grade.total_points}
                        </small>
                    </div>
                </div>
            </div>
        `;

        return item;
    }

    /**
     * Show grades error
     */
    function showGradesError(message) {
        $('#recentGradesContainer').html(`
            <div class="text-center py-4">
                <i class="fas fa-exclamation-circle fa-2x text-danger mb-2"></i>
                <p class="text-muted mb-0">${escapeHtml(message)}</p>
            </div>
        `);
    }

    /**
     * Load performance chart
     */
    function loadPerformanceChart() {
        $.ajax({
            url: API_ROUTES.getPerformanceChart,
            method: 'GET',
            success: function(response) {
                $('#refreshChart i').removeClass('fa-spin');
                
                if (response.success) {
                    displayPerformanceChart(response.data);
                } else {
                    showChartError();
                }
            },
            error: function(xhr) {
                $('#refreshChart i').removeClass('fa-spin');
                showChartError();
                toastr.error('Failed to load performance chart');
            }
        });
    }

    /**
     * Display performance chart
     */
    function displayPerformanceChart(data) {
        if (data.length === 0) {
            $('#performanceChart').parent().html(`
                <div class="text-center py-4">
                    <i class="fas fa-chart-area fa-3x text-muted mb-2"></i>
                    <p class="text-muted mb-0">No performance data available</p>
                </div>
            `);
            return;
        }

        const labels = data.map(item => item.class_name);
        const wwData = data.map(item => item.ww_avg);
        const ptData = data.map(item => item.pt_avg);
        const qaData = data.map(item => item.qa_avg);

        const ctx = document.getElementById('performanceChart');
        
        // Destroy existing chart if it exists
        if (performanceChart) {
            performanceChart.destroy();
        }

        performanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Written Works',
                        data: wwData,
                        backgroundColor: 'rgba(108, 117, 125, 0.7)',
                        borderColor: 'rgb(108, 117, 125)',
                        borderWidth: 1
                    },
                    {
                        label: 'Performance Tasks',
                        data: ptData,
                        backgroundColor: 'rgba(0, 123, 255, 0.7)',
                        borderColor: 'rgb(0, 123, 255)',
                        borderWidth: 1
                    },
                    {
                        label: 'Quarterly Assessment',
                        data: qaData,
                        backgroundColor: 'rgba(108, 117, 125, 0.5)',
                        borderColor: 'rgb(108, 117, 125)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + 
                                       context.parsed.y.toFixed(2) + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Show chart error
     */
    function showChartError() {
        $('#performanceChart').parent().html(`
            <div class="text-center py-4">
                <i class="fas fa-exclamation-circle fa-2x text-danger mb-2"></i>
                <p class="text-muted mb-0">Failed to load performance chart</p>
            </div>
        `);
    }

    /**
     * Take quiz button handler
     */
    $(document).on('click', '.take-quiz-btn', function() {
        const quizId = $(this).data('quiz-id');
        // TODO: Navigate to quiz taking page
        toastr.info('Quiz taking feature will be implemented');
    });

    /**
     * Helper function to escape HTML
     */
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