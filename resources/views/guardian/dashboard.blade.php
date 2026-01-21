@extends('layouts.main-guardian')

@section('breadcrumb')
<nav aria-label="breadcrumb" class="breadcrumb-custom">
    <i class="fas fa-home breadcrumb-icon"></i>
    <ol class="breadcrumb mb-0 bg-transparent">
        <li class="breadcrumb-item active">Dashboard</li>
    </ol>
</nav>
@endsection

@section('styles')
<style>
    .student-card {
        transition: all 0.3s ease;
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    }
    
    .student-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        transform: translateY(-2px);
    }
    
    .student-avatar-large {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #f8f9fa;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .student-info-item {
        padding: 8px 0;
        border-bottom: 1px solid #f1f1f1;
    }
    
    .student-info-item:last-child {
        border-bottom: none;
    }
    
    .info-label {
        color: #6c757d;
        font-size: 0.85rem;
        font-weight: 500;
        margin-bottom: 2px;
    }
    
    .info-value {
        color: #343a40;
        font-size: 0.95rem;
        font-weight: 600;
    }
    
    .grade-badge {
        font-size: 1.1rem;
        padding: 6px 12px;
    }
</style>
@endsection

@section('content')
<div class="row">
    <!-- Welcome Card -->
    <div class="col-12 mb-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body py-4">
                <h3 class="mb-2">
                    <i class="fas fa-hand-wave text-warning mr-2"></i>
                    Welcome, {{ session('guardian_name') }}!
                </h3>
                <p class="text-muted mb-0">Monitor your students' academic progress and grades.</p>
            </div>
        </div>
    </div>

        <!-- Recent Updates -->
    @if(isset($recentUpdates) && $recentUpdates->count() > 0)
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h3 class="card-title mb-0">
                    <i class="fas fa-clock text-primary mr-2"></i>
                    Recent Grade Updates
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0">Date</th>
                                <th class="border-0">Student</th>
                                <th class="border-0">Subject</th>
                                <th class="border-0">Semester</th>
                                <th class="border-0 text-center">Grade</th>
                                <th class="border-0 text-center">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentUpdates as $update)
                            <tr>
                                <td>
                                    <small class="text-muted">
                                        <i class="far fa-calendar-alt mr-1"></i>
                                        {{ \Carbon\Carbon::parse($update->updated_at)->format('M d, Y') }}
                                    </small>
                                </td>
                                <td>
                                    <strong>{{ $update->student_name }}</strong>
                                </td>
                                <td>{{ $update->class_name }}</td>
                                <td>
                                    <small class="text-muted">{{ $update->semester_name }}</small>
                                </td>
                                <td class="text-center">
                                        {{ number_format($update->final_grade, 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $update->remarks == 'PASSED' ? 'success' : 'danger' }}">
                                        <i class="fas fa-{{ $update->remarks == 'PASSED' ? 'check' : 'times' }} mr-1"></i>
                                        {{ $update->remarks }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Students Overview -->
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h3 class="card-title mb-0">
                    <i class="fas fa-users text-primary mr-2"></i>
                    My Students
                </h3>
            </div>
            <div class="card-body">
                @if($students->count() > 0)
                <div class="row">
                    @foreach($students as $student)
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card student-card h-100">
                            <div class="card-body">
                                <!-- Student Header -->
                                <div class="text-center mb-3">
                                    <img class="student-avatar-large mb-3" 
                                         src="{{ $student->profile_image ? asset('storage/' . $student->profile_image) : asset('img/default-avatar.png') }}" 
                                         alt="{{ $student->full_name }}">
                                    <h5 class="mb-1 font-weight-bold">{{ $student->full_name }}</h5>
                                </div>

                                <!-- Student Information -->
                                <div class="mb-3">
                                    <div class="student-info-item">
                                        <div class="info-label">Student Number</div>
                                        <div class="info-value">
                                            {{ $student->student_number }}
                                        </div>
                                    </div>

                                    <div class="student-info-item">
                                        <div class="info-label">Grade Level</div>
                                        <div class="info-value">
                                            {{ $student->level_name }}
                                        </div>
                                    </div>

                                    <div class="student-info-item">
                                        <div class="info-label">Section</div>
                                        <div class="info-value">
                                            {{ $student->section_name ?? 'Not Assigned' }}
                                        </div>
                                    </div>

                                    <div class="student-info-item">
                                        <div class="info-label">Student Type</div>
                                        <div class="info-value">
                                            {{ ucfirst($student->student_type) }}
                                        </div>
                                    </div>

                                    @if(isset($student->average_grade))
                                    <div class="student-info-item">
                                        <div class="info-label">Current Average</div>
                                        <div class="info-value">
                                            <span class="grade-badge badge badge-{{ $student->average_grade >= 75 ? 'success' : 'danger' }}">
                                                {{ number_format($student->average_grade, 2) }}
                                            </span>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                <!-- Action Button -->
                                <a href="{{ route('guardian.student.grades', $student->student_number) }}" 
                                   class="btn btn-primary btn-block">
                                    <i class="fas fa-chart-line mr-2"></i>View Complete Grades
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-users text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                    <h5 class="mt-3 text-muted">No Students Linked</h5>
                    <p class="text-muted">No students are currently linked to your guardian account.</p>
                </div>
                @endif
            </div>
        </div>
    </div>


    @endif
</div>
@endsection