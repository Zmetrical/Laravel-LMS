@extends('layouts.main-guardian')

@section('breadcrumb')
<nav aria-label="breadcrumb" class="breadcrumb-custom">
    <i class="fas fa-home breadcrumb-icon"></i>
    <ol class="breadcrumb mb-0 bg-transparent">
        <li class="breadcrumb-item active">Dashboard</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="row">
    <!-- Welcome Card -->
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-3">Welcome, {{ session('guardian_name') }}!</h4>
                <p class="text-muted">Monitor your students' academic progress and grades.</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="col-lg-4 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $totalStudents }}</h3>
                <p>My Students</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>{{ number_format($avgGrade, 1) }}</h3>
                <p>Average Grade</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $totalSubjects }}</h3>
                <p>Total Subjects</p>
            </div>
            <div class="icon">
                <i class="fas fa-book"></i>
            </div>
        </div>
    </div>

    <!-- Students Overview -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">My Students</h3>
            </div>
            <div class="card-body">
                @if($students->count() > 0)
                <div class="row">
                    @foreach($students as $student)
                    <div class="col-md-6">
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">{{ $student->full_name }}</h3>
                                <span class="badge badge-primary float-right">{{ $student->level_name }}</span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-4 text-center">
                                        <img class="img-circle" 
                                             src="{{ $student->profile_image ? asset('storage/' . $student->profile_image) : asset('img/default-avatar.png') }}" 
                                             alt="Student" 
                                             style="width: 80px; height: 80px; object-fit: cover;">
                                    </div>
                                    <div class="col-8">
                                        <p class="mb-1"><strong>Student Number:</strong> {{ $student->student_number }}</p>
                                        <p class="mb-1"><strong>Section:</strong> {{ $student->section_name ?? 'N/A' }}</p>
                                        <p class="mb-1"><strong>Type:</strong> 
                                            <span class="badge badge-{{ $student->student_type == 'regular' ? 'primary' : 'secondary' }}">
                                                {{ ucfirst($student->student_type) }}
                                            </span>
                                        </p>
                                        <a href="{{ route('guardian.student.grades', $student->student_number) }}" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye mr-1"></i> View Grades
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle mr-2"></i> No students linked to your account yet.
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Recent Updates -->
    @if($recentUpdates->count() > 0)
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Grade Updates</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Semester</th>
                            <th>Grade</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentUpdates as $update)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($update->updated_at)->format('M d, Y') }}</td>
                            <td>{{ $update->student_name }}</td>
                            <td>{{ $update->class_name }}</td>
                            <td>{{ $update->semester_name }}</td>
                            <td>
                                <span class="badge badge-{{ $update->final_grade >= 75 ? 'success' : 'danger' }}">
                                    {{ number_format($update->final_grade, 2) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-{{ $update->remarks == 'PASSED' ? 'success' : 'danger' }}">
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
    @endif
</div>
@endsection