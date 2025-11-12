// Student Grades JS
$(document).ready(function() {
    const classId = API_ROUTES.classId;
    const studentNumber = API_ROUTES.studentNumber;
    
    function loadGrades() {
        $('#loadingState').show();
        $('#gradeTableContainer').hide();
        $('#emptyState').hide();
        
        console.log('Loading grades for class:', classId, 'student:', studentNumber);
        
        $.ajax({
            url: API_ROUTES.getGrades,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
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