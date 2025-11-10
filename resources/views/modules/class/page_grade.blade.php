@extends('modules.class.main', [
    'activeTab' => 'grades', 
    'userType' => $userType, 
    'class' => $class])

@section('tab-content')
<div class="row">
    <!-- Summary Cards -->
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 id="totalQuizzes">0</h3>
                <p>Quizzes Taken</p>
            </div>
            <div class="icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3 id="averageScore">0%</h3>
                <p>Average Score</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 id="passedCount">0</h3>
                <p>Quizzes Passed</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 id="failedCount">0</h3>
                <p>Needs Improvement</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <!-- Performance Overview -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie"></i> Performance Overview
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="progress-group">
                            <span class="progress-text">Overall Progress</span>
                            <span class="float-right"><b id="progressScored">0</b>/<span id="progressTotal">0</span></span>
                            <div class="progress">
                                <div class="progress-bar bg-primary" id="overallProgress" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grades Table -->
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> My Grades
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-info" id="refreshGrades">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" id="gradeTableContainer" style="display: none;">
                    <table class="table table-bordered table-hover" id="gradeTable">
                        <thead class="bg-info">
                            <tr>
                                <th>Lesson</th>
                                <th>Quiz</th>
                                <th class="text-center">Score</th>
                                <th class="text-center">Percentage</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Attempts</th>
                                <th class="text-center">Date Submitted</th>
                            </tr>
                        </thead>
                        <tbody id="gradeTableBody">
                            <!-- Rows will be added dynamically -->
                        </tbody>
                    </table>
                </div>

                <div id="loadingState" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                    <h5>Loading your grades...</h5>
                </div>

                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h4>No Grades Yet</h4>
                    <p class="text-muted">Your grades will appear here once you complete quizzes.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
$(document).ready(function() {
    const classId = {{ $class->id }};
    const studentNumber = "{{ $studentNumber }}";
    
function loadGrades() {
    $('#loadingState').show();
    $('#gradeTableContainer').hide();
    $('#emptyState').hide();
    
    console.log('Loading grades for class:', classId, 'student:', studentNumber);
    
    $.ajax({
        url: `/class/${classId}/student/${studentNumber}/grades`,
        method: 'GET',
        success: function(response) {
            console.log('Full Response:', response);
            console.log('Grades:', response.grades);
            console.log('Grades Length:', response.grades.length);
            console.log('Summary:', response.summary);
            
            $('#loadingState').hide();
            
            if (!response.grades || response.grades.length === 0) {
                console.log('No grades found - showing empty state');
                $('#emptyState').show();
                updateSummary(response.summary);
                return;
            }
            
            console.log('Rendering grades...');
            renderGrades(response.grades, response.summary);
            $('#gradeTableContainer').show();
        },
        error: function(xhr) {
            console.error('Error loading grades:', xhr);
            console.error('Status:', xhr.status);
            console.error('Response:', xhr.responseText);
            $('#loadingState').hide();
            toastr.error('Failed to load grades');
        }
    });
}
    
    function renderGrades(grades, summary) {
        const $tbody = $('#gradeTableBody');
        $tbody.empty();
        
        let passedCount = 0;
        let failedCount = 0;
        
        grades.forEach(grade => {
            const statusBadge = grade.passed ? 
                '<span class="badge badge-success"><i class="fas fa-check"></i> Passed</span>' :
                '<span class="badge badge-danger"><i class="fas fa-times"></i> Failed</span>';
            
            const percentageBadge = grade.percentage >= 75 ? 'badge-success' : 
                                   grade.percentage >= 60 ? 'badge-info' : 
                                   'badge-danger';
            
            if (grade.passed) passedCount++;
            else failedCount++;
            
            const date = new Date(grade.submitted_at);
            const formattedDate = date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            $tbody.append(`
                <tr>
                    <td>${grade.lesson_title}</td>
                    <td><strong>${grade.quiz_title}</strong></td>
                    <td class="text-center">
                        <strong>${grade.score} / ${grade.total_points}</strong>
                    </td>
                    <td class="text-center">
                        <span class="badge ${percentageBadge}">${grade.percentage}%</span>
                    </td>
                    <td class="text-center">${statusBadge}</td>
                    <td class="text-center">
                        <span class="badge badge-secondary">${grade.attempt_count}</span>
                    </td>
                    <td class="text-center">
                        <small class="text-muted">${formattedDate}</small>
                    </td>
                </tr>
            `);
        });
        
        updateSummary(summary, passedCount, failedCount);
    }
    
    function updateSummary(summary, passedCount = 0, failedCount = 0) {
        $('#totalQuizzes').text(summary.quiz_count || 0);
        $('#averageScore').text((summary.average_percentage || 0) + '%');
        $('#passedCount').text(passedCount);
        $('#failedCount').text(failedCount);
        
        // Update progress bar
        const totalPossible = summary.quiz_count || 1;
        const completed = passedCount + failedCount;
        const progressPercent = (completed / totalPossible) * 100;
        
        $('#progressScored').text(completed);
        $('#progressTotal').text(totalPossible);
        $('#overallProgress').css('width', progressPercent + '%');
    }
    
    $('#refreshGrades').click(function() {
        loadGrades();
    });
    
    // Initial load
    loadGrades();
});
</script>
@endsection