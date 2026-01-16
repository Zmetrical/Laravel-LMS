@extends('layouts.main-guardian')

@section('breadcrumb')
<nav aria-label="breadcrumb" class="breadcrumb-custom">
    <i class="fas fa-users breadcrumb-icon"></i>
    <ol class="breadcrumb mb-0 bg-transparent">
        <li class="breadcrumb-item"><a href="{{ route('guardian.home') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">My Students</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">My Students</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($students as $student)
                    <div class="col-md-6">
                        <div class="card card-outline card-primary">
                            <div class="card-body box-profile">
                                <div class="text-center">
                                    <img class="profile-user-img img-fluid img-circle" 
                                         src="{{ $student->profile_image ? asset('storage/' . $student->profile_image) : asset('img/default-avatar.png') }}" 
                                         alt="Student profile picture"
                                         style="width: 100px; height: 100px; object-fit: cover;">
                                </div>

                                <h3 class="profile-username text-center">{{ $student->full_name }}</h3>

                                <ul class="list-group list-group-unbordered mb-3">
                                    <li class="list-group-item">
                                        <b>Student Number</b> 
                                        <span class="float-right">{{ $student->student_number }}</span>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Level</b> 
                                        <span class="float-right">{{ $student->level }}</span>
                                    </li>
                                    <li class="list-group-item">
                                        <b>Section</b> 
                                        <span class="float-right">{{ $student->section }}</span>
                                    </li>
                                </ul>

                                <a href="{{ route('guardian.student.grades', $student->student_number) }}" 
                                   class="btn btn-primary btn-block">
                                    <i class="fas fa-eye mr-2"></i>View Grades
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection