@extends('layouts.main-guardian')

@section('breadcrumb')
<nav aria-label="breadcrumb" class="breadcrumb-custom">
    <i class="fas fa-graduation-cap breadcrumb-icon"></i>
    <ol class="breadcrumb mb-0 bg-transparent">
        <li class="breadcrumb-item"><a href="{{ route('guardian.home') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">{{ $student->full_name }}</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="row">
    <!-- Student Info Card -->
    <div class="col-md-4">
        <div class="card card-primary card-outline">
            <div class="card-body box-profile">
                <div class="text-center">
                    <img class="profile-user-img img-fluid img-circle" 
                         src="{{ $student->profile_image ? asset('storage/' . $student->profile_image) : asset('img/default-avatar.png') }}" 
                         alt="Student profile picture"
                         style="width: 100px; height: 100px; object-fit: cover;">
                </div>

                <h3 class="profile-username text-center">{{ $student->full_name }}</h3>
                <p class="text-muted text-center">{{ $student->student_number }}</p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Level</b> 
                        <span class="float-right">{{ $student->level_name }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Section</b> 
                        <span class="float-right">{{ $student->section_name ?? 'N/A' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Type</b> 
                        <span class="float-right">
                            <span class="badge badge-{{ $student->student_type == 'regular' ? 'primary' : 'secondary' }}">
                                {{ ucfirst($student->student_type) }}
                            </span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title">Grade Summary</h3>
            </div>
            <div class="card-body" id="grade-summary">
                <div class="text-center text-muted">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>

    <!-- Grades Section -->
    <div class="col-md-8">
        <!-- Semester Selector -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Select Semester</h3>
            </div>
            <div class="card-body">
                @if($semesters->count() > 0)
                <div class="form-group">
                    <select class="form-control" id="semester-selector">
                        <option value="">-- Select Semester --</option>
                        @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}">{{ $semester->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle mr-2"></i> No grade records found for this student yet.
                </div>
                @endif
            </div>
        </div>

        <!-- Grades Table -->
        <div class="card" id="grades-card" style="display: none;">
            <div class="card-header">
                <h3 class="card-title">Grades Report</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th class="text-center">Q1</th>
                            <th class="text-center">Q2</th>
                            <th class="text-center">Final</th>
                            <th class="text-center">Remarks</th>
                        </tr>
                    </thead>
                    <tbody id="grades-tbody">
                        <!-- Will be populated via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Performance Chart -->
        <div class="card" id="chart-card" style="display: none;">
            <div class="card-header">
                <h3 class="card-title">Grade Performance</h3>
            </div>
            <div class="card-body">
                <canvas id="gradeChart" style="height: 250px;"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('plugins/chart.js/Chart.min.js') }}"></script>
<script>
$(document).ready(function() {
    let gradeChart = null;
    const studentNumber = '{{ $student->student_number }}';

    $('#semester-selector').change(function() {
        const semesterId = $(this).val();
        
        if (!semesterId) {
            $('#grades-card').hide();
            $('#chart-card').hide();
            return;
        }

        // Show loading
        $('#grades-tbody').html('<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading grades...</td></tr>');
        $('#grades-card').show();
        
        // Fetch grades
        $.ajax({
            url: '{{ route("guardian.student.grades.data", $student->student_number) }}',
            method: 'GET',
            data: { semester_id: semesterId },
            success: function(response) {
                if (response.grades && response.grades.length > 0) {
                    displayGrades(response.grades);
                    updateSummary(response.stats);
                    updateChart(response.grades);
                    $('#chart-card').show();
                } else {
                    $('#grades-tbody').html('<tr><td colspan="5" class="text-center text-muted">No grades recorded for this semester yet.</td></tr>');
                    $('#chart-card').hide();
                    $('#grade-summary').html('<div class="alert alert-warning mb-0"><i class="fas fa-info-circle mr-2"></i> No grades available</div>');
                }
            },
            error: function() {
                $('#grades-tbody').html('<tr><td colspan="5" class="text-center text-danger">Error loading grades. Please try again.</td></tr>');
                $('#chart-card').hide();
            }
        });
    });

    function displayGrades(grades) {
        let html = '';
        
        grades.forEach(function(grade) {
            html += '<tr>';
            html += '<td>' + grade.class_name + '</td>';
            
            // Q1 Grade
            html += '<td class="text-center">';
            if (grade.q1_grade !== null) {
                const q1Class = grade.q1_grade >= 75 ? 'badge-success' : 'badge-danger';
                html += '<span class="badge ' + q1Class + '">' + parseFloat(grade.q1_grade).toFixed(2) + '</span>';
            } else {
                html += '<span class="badge badge-secondary">N/A</span>';
            }
            html += '</td>';
            
            // Q2 Grade
            html += '<td class="text-center">';
            if (grade.q2_grade !== null) {
                const q2Class = grade.q2_grade >= 75 ? 'badge-success' : 'badge-danger';
                html += '<span class="badge ' + q2Class + '">' + parseFloat(grade.q2_grade).toFixed(2) + '</span>';
            } else {
                html += '<span class="badge badge-secondary">N/A</span>';
            }
            html += '</td>';
            
            // Final Grade
            html += '<td class="text-center">';
            if (grade.final_grade !== null) {
                const finalClass = grade.final_grade >= 75 ? 'badge-success' : 'badge-danger';
                html += '<span class="badge ' + finalClass + '">' + parseFloat(grade.final_grade).toFixed(2) + '</span>';
            } else {
                html += '<span class="badge badge-secondary">N/A</span>';
            }
            html += '</td>';
            
            // Remarks
            html += '<td class="text-center">';
            let remarksClass = 'badge-secondary';
            if (grade.remarks === 'PASSED') remarksClass = 'badge-success';
            else if (grade.remarks === 'FAILED') remarksClass = 'badge-danger';
            else if (grade.remarks === 'INC') remarksClass = 'badge-warning';
            html += '<span class="badge ' + remarksClass + '">' + grade.remarks + '</span>';
            html += '</td>';
            
            html += '</tr>';
        });
        
        $('#grades-tbody').html(html);
    }

    function updateSummary(stats) {
        const avgGrade = stats.average ? parseFloat(stats.average).toFixed(2) : 'N/A';
        
        let html = '<div class="row">';
        html += '<div class="col-12 mb-3">';
        html += '<div class="info-box bg-primary">';
        html += '<div class="info-box-content">';
        html += '<span class="info-box-text">Overall Average</span>';
        html += '<span class="info-box-number">' + avgGrade + '</span>';
        html += '</div></div></div>';
        
        html += '<div class="col-6">';
        html += '<div class="info-box bg-success">';
        html += '<div class="info-box-content">';
        html += '<span class="info-box-text">Passed</span>';
        html += '<span class="info-box-number">' + stats.passed + '</span>';
        html += '</div></div></div>';
        
        html += '<div class="col-6">';
        html += '<div class="info-box bg-danger">';
        html += '<div class="info-box-content">';
        html += '<span class="info-box-text">Failed</span>';
        html += '<span class="info-box-number">' + stats.failed + '</span>';
        html += '</div></div></div>';
        
        html += '</div>';
        
        $('#grade-summary').html(html);
    }

    function updateChart(grades) {
        const ctx = document.getElementById('gradeChart').getContext('2d');
        
        if (gradeChart) {
            gradeChart.destroy();
        }
        
        const labels = grades.map(g => {
            const name = g.class_name;
            return name.length > 20 ? name.substring(0, 20) + '...' : name;
        });
        
        const q1Data = grades.map(g => g.q1_grade);
        const q2Data = grades.map(g => g.q2_grade);
        const finalData = grades.map(g => g.final_grade);
        
        gradeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Q1 Grade',
                        data: q1Data,
                        backgroundColor: 'rgba(20, 29, 92, 0.7)',
                        borderColor: 'rgba(20, 29, 92, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Q2 Grade',
                        data: q2Data,
                        backgroundColor: 'rgba(108, 117, 125, 0.7)',
                        borderColor: 'rgba(108, 117, 125, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Final Grade',
                        data: finalData,
                        backgroundColor: 'rgba(255, 193, 7, 0.7)',
                        borderColor: 'rgba(255, 193, 7, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 60,
                        max: 100,
                        ticks: {
                            stepSize: 10
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                }
            }
        });
    }
});
</script>
@endsection