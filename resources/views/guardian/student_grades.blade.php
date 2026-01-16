@extends('layouts.main-guardian')

@section('breadcrumb')
<nav aria-label="breadcrumb" class="breadcrumb-custom">
    <i class="fas fa-graduation-cap breadcrumb-icon"></i>
    <ol class="breadcrumb mb-0 bg-transparent">
        <li class="breadcrumb-item"><a href="{{ route('guardian.home') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('guardian.students') }}">My Students</a></li>
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
                        <span class="float-right">{{ $student->level }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Section</b> 
                        <span class="float-right">{{ $student->section }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Summary Card -->
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title">Grade Summary</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="info-box bg-primary">
                            <div class="info-box-content">
                                <span class="info-box-text">Overall Average</span>
                                <span class="info-box-number">{{ number_format($grades->avg('final_grade'), 2) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-box bg-success">
                            <div class="info-box-content">
                                <span class="info-box-text">Passed</span>
                                <span class="info-box-number">{{ $grades->where('remarks', 'PASSED')->count() }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="info-box bg-danger">
                            <div class="info-box-content">
                                <span class="info-box-text">Failed</span>
                                <span class="info-box-number">{{ $grades->where('remarks', 'FAILED')->count() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grades Table -->
    <div class="col-md-8">
        <div class="card">
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
                    <tbody>
                        @foreach($grades as $grade)
                        <tr>
                            <td>{{ $grade->class_name }}</td>
                            <td class="text-center">
                                @if($grade->q1_grade)
                                    <span class="badge {{ $grade->q1_grade >= 75 ? 'badge-success' : 'badge-danger' }}">
                                        {{ number_format($grade->q1_grade, 2) }}
                                    </span>
                                @else
                                    <span class="badge badge-secondary">N/A</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($grade->q2_grade)
                                    <span class="badge {{ $grade->q2_grade >= 75 ? 'badge-success' : 'badge-danger' }}">
                                        {{ number_format($grade->q2_grade, 2) }}
                                    </span>
                                @else
                                    <span class="badge badge-secondary">N/A</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($grade->final_grade)
                                    <span class="badge {{ $grade->final_grade >= 75 ? 'badge-success' : 'badge-danger' }}">
                                        {{ number_format($grade->final_grade, 2) }}
                                    </span>
                                @else
                                    <span class="badge badge-secondary">N/A</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $grade->remarks == 'PASSED' ? 'success' : ($grade->remarks == 'FAILED' ? 'danger' : 'warning') }}">
                                    {{ $grade->remarks }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Performance Chart -->
        <div class="card">
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
    // Grade Performance Chart
    const ctx = document.getElementById('gradeChart').getContext('2d');
    const grades = @json($grades);
    
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: grades.map(g => g.class_name.substring(0, 20) + (g.class_name.length > 20 ? '...' : '')),
            datasets: [
                {
                    label: 'Q1 Grade',
                    data: grades.map(g => g.q1_grade),
                    backgroundColor: 'rgba(20, 29, 92, 0.7)',
                    borderColor: 'rgba(20, 29, 92, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Q2 Grade',
                    data: grades.map(g => g.q2_grade),
                    backgroundColor: 'rgba(108, 117, 125, 0.7)',
                    borderColor: 'rgba(108, 117, 125, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Final Grade',
                    data: grades.map(g => g.final_grade),
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
});
</script>
@endsection