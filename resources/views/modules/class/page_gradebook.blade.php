@extends('modules.class.main', [
    'activeTab' => 'grades', 
    'userType' => $userType, 
    'class' => $class])

@section('tab-content')
<div class="row">
    <div class="col-12">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-table"></i> Grade Book
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
                        <thead class="bg-primary">
                            <tr>
                                <th style="position: sticky; left: 0; z-index: 10; background-color: #007bff; color: white;">Student Name</th>
                                <!-- Quiz columns will be added dynamically -->
                            </tr>
                        </thead>
                        <tbody id="gradeTableBody">
                            <!-- Rows will be added dynamically -->
                        </tbody>
                    </table>
                </div>

                <div id="loadingState" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                    <h5>Loading grades...</h5>
                </div>

                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h4>No Grades Available</h4>
                    <p class="text-muted">Grades will appear here once students complete quizzes.</p>
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
    
    function loadGrades() {
        $('#loadingState').show();
        $('#gradeTableContainer').hide();
        $('#emptyState').hide();
        
        console.log('Loading grades for class:', classId);
        
        $.ajax({
            url: `/class/${classId}/get_grades`,
            method: 'GET',
            success: function(response) {
                console.log('Full Response:', response);
                console.log('Grades:', response.grades);
                console.log('Grades Length:', response.grades.length);
                console.log('Quizzes:', response.quizzes);
                console.log('Quizzes Length:', response.quizzes.length);
                
                $('#loadingState').hide();
                
                if (!response.grades || response.grades.length === 0 || !response.quizzes || response.quizzes.length === 0) {
                    console.log('No data found - showing empty state');
                    $('#emptyState').show();
                    return;
                }
                
                console.log('Rendering grade table...');
                renderGradeTable(response.grades, response.quizzes, response.class);
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
    
    function renderGradeTable(grades, quizzes, classInfo) {
        const $thead = $('#gradeTable thead tr');
        const $tbody = $('#gradeTableBody');
        
        // Clear existing content
        $thead.find('th:not(:first)').remove();
        $tbody.empty();
        
        // Add quiz columns
        quizzes.forEach(quiz => {
            $thead.append(`
                <th class="text-center" title="${quiz.lesson_title}">
                    ${quiz.title}
                </th>
            `);
        });
        
        // Add summary columns
        $thead.append(`
            <th class="text-center bg-info">Total Score</th>
            <th class="text-center bg-info">Average %</th>
        `);
        
        // Calculate statistics
        let totalStudents = grades.length;
        let totalQuizzes = quizzes.length;
        let classTotal = 0;
        let classCount = 0;
        let passingCount = 0;
        
        // Add student rows
        grades.forEach(student => {
            let row = `
                <tr>
                    <td style="position: sticky; left: 0; z-index: 5; background-color: white;">
                        <strong>${student.full_name}</strong>
                    </td>
            `;
            
            let studentTotal = 0;
            let studentMax = 0;
            let scoredQuizzes = 0;
            
            quizzes.forEach(quiz => {
                const quizGrade = student.quizzes[quiz.id];
                
                if (quizGrade.score !== null) {
                    const percentage = quizGrade.percentage;
                    const badgeClass = percentage >= 75 ? 'badge-success' : 
                                      percentage >= 60 ? 'badge-info' : 
                                      'badge-danger';
                    
                    row += `
                        <td class="text-center">
                            <span class="badge ${badgeClass}">
                                ${quizGrade.score} / ${quizGrade.total}
                            </span>
                            <br>
                            <small class="text-muted">${percentage}%</small>
                        </td>
                    `;
                    
                    studentTotal += parseFloat(quizGrade.score);
                    studentMax += parseFloat(quizGrade.total);
                    scoredQuizzes++;
                    
                    classTotal += percentage;
                    classCount++;
                    
                    if (percentage >= 60) passingCount++;
                } else {
                    row += `<td class="text-center text-muted">-</td>`;
                }
            });
            
            const studentAvg = studentMax > 0 ? ((studentTotal / studentMax) * 100).toFixed(2) : 0;
            const avgBadge = studentAvg >= 75 ? 'badge-success' : 
                           studentAvg >= 60 ? 'badge-info' : 
                           'badge-danger';
            
            row += `
                    <td class="text-center bg-light">
                        <strong>${studentTotal.toFixed(2)} / ${studentMax.toFixed(2)}</strong>
                    </td>
                    <td class="text-center bg-light">
                        <span class="badge ${avgBadge}">${studentAvg}%</span>
                    </td>
                </tr>
            `;
            
            $tbody.append(row);
        });
        
        // Update summary
        const classAverage = classCount > 0 ? (classTotal / classCount).toFixed(2) : 0;
        const passingRate = classCount > 0 ? ((passingCount / classCount) * 100).toFixed(2) : 0;
        
    }
    

    
    $('#refreshGrades').click(function() {
        loadGrades();
    });
    
    // Initial load
    loadGrades();
});
</script>
@endsection