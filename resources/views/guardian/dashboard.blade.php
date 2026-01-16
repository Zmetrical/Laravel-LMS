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
                <h4 class="mb-3">Welcome, Guardian!</h4>
                <p class="text-muted">Monitor your students' academic progress and grades.</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="col-lg-4 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>2</h3>
                <p>My Students</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="{{ route('guardian.students') }}" class="small-box-footer">
                View Students <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-4 col-6">
        <div class="small-box bg-secondary">
            <div class="inner">
                <h3>89.5</h3>
                <p>Average Grade</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <a href="{{ route('guardian.students') }}" class="small-box-footer">
                View Details <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>12</h3>
                <p>Total Subjects</p>
            </div>
            <div class="icon">
                <i class="fas fa-book"></i>
            </div>
            <a href="{{ route('guardian.students') }}" class="small-box-footer">
                View Subjects <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Students Overview -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">My Students</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Maria Dela Cruz</h3>
                                <span class="badge badge-primary float-right">Grade 11</span>
                            </div>
                            <div class="card-body">
                                <p><strong>Student Number:</strong> 2024-0001</p>
                                <p><strong>Section:</strong> STEM 11-A</p>
                                <p><strong>Average Grade:</strong> <span class="badge badge-success">90.5</span></p>
                                <a href="{{ route('guardian.student.grades', '2024-0001') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye mr-1"></i> View Grades
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card card-outline card-primary">
                            <div class="card-header">
                                <h3 class="card-title">Jose Dela Cruz</h3>
                                <span class="badge badge-primary float-right">Grade 12</span>
                            </div>
                            <div class="card-body">
                                <p><strong>Student Number:</strong> 2024-0002</p>
                                <p><strong>Section:</strong> ABM 12-B</p>
                                <p><strong>Average Grade:</strong> <span class="badge badge-success">88.5</span></p>
                                <a href="{{ route('guardian.student.grades', '2024-0002') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye mr-1"></i> View Grades
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Updates -->
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
                            <th>Quarter</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Jan 15, 2026</td>
                            <td>Maria Dela Cruz</td>
                            <td>General Mathematics</td>
                            <td>Q2</td>
                            <td><span class="badge badge-success">90.0</span></td>
                        </tr>
                        <tr>
                            <td>Jan 14, 2026</td>
                            <td>Jose Dela Cruz</td>
                            <td>Business Mathematics</td>
                            <td>Q2</td>
                            <td><span class="badge badge-success">88.5</span></td>
                        </tr>
                        <tr>
                            <td>Jan 12, 2026</td>
                            <td>Maria Dela Cruz</td>
                            <td>English for Academic Purposes</td>
                            <td>Q2</td>
                            <td><span class="badge badge-success">91.5</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection