@extends('layouts.main')

@section('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif

    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.list_student') }}">Student List</a></li>
        <li class="breadcrumb-item active">Profile</li>
    </ol>
@endsection

@section('content')
<br>
    <div class="container-fluid">
        <div class="row">
            <!-- Left Container -->
            <div class="col-md-3">

                <!-- Profile Card -->
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <img id="profileImagePreview" class="profile-user-img img-fluid img-circle"
                                src="{{ $student->profile_image ? asset('storage/' . $student->profile_image) : asset('img/default-avatar.png') }}"
                                alt="Profile Image">
                        </div>

                        <h3 class="profile-username text-center">{{ $student->student_number }}</h3>

                        <form id="profileForm" method="POST" data-student-id="{{ $student->id }}">
                            @if($mode === 'edit')
                                @csrf
                                @method('POST')
                            @endif

                            <ul class="list-group list-group-unbordered mb-3">

                                @if(auth()->guard('admin')->check())

                                <li class="list-group-item">
                                    <div class="input-group input-group-sm">
                                        <input type="password" class="form-control" id="studentPassword" 
                                            value="••••••••" readonly>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-default" id="togglePassword" 
                                                title="Show password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </li>

                                @endif
                                <li class="list-group-item">
                                    <b><i class="fas fa-user mr-1"></i> Student Type</b>
                                    <span class="float-right">{{ ucfirst($student->student_type) }}</span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-book mr-1"></i> Strand</b>
                                    <span class="float-right">{{ $student->strand }}</span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-layer-group mr-1"></i> Year Level</b>
                                    <span class="float-right">{{ $student->level }}</span>
                                </li>
                                <li class="list-group-item">
                                    <b><i class="fas fa-users mr-1"></i> Section</b>
                                    <span class="float-right">{{ $student->section }}</span>
                                </li>



                            </ul>

                            @if($mode === 'view')
                                <a href="{{ route('profile.student.edit', $student->id) }}" class="btn btn-primary btn-block">
                                    <i class="fas fa-edit"></i> Edit Profile
                                </a>

                                <hr>
                                
                                @if(count($enrolledSemesters) > 0)
                                <a href="{{ route('admin.grades.evaluation', $student->student_number) }}" 
                                   class="btn btn-default btn-block">
                                    <i class="fas fa-graduation-cap"></i> View Evaluation
                                </a>
                                @endif
                            @else
                                <button type="submit" class="btn btn-primary btn-block mb-2">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <a href="{{ route('profile.student.show', $student->id) }}" class="btn btn-default btn-block">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

            
                <!-- Right Container -->
            <div class="col-md-9">
                <!-- Personal Information Card -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user mr-2"></i>Personal Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="firstName"><i class="fas fa-user mr-1"></i>First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="firstName"
                                        name="first_name" data-editable placeholder="Enter first name"
                                        value="{{ $student->first_name }}" required />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="middleInitial"><i class="fas fa-user mr-1"></i>M.I. <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="middleInitial"
                                        name="middle_name" data-editable placeholder="M.I."
                                        maxlength="1"
                                        value="{{ $student->middle_name ? strtoupper(substr($student->middle_name, 0, 1)) : '' }}" />
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="lastName"><i class="fas fa-user mr-1"></i>Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="lastName"
                                        name="last_name" data-editable placeholder="Enter last name"
                                        value="{{ $student->last_name }}" required />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email"><i class="fas fa-envelope mr-1"></i>Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        data-editable placeholder="Enter email" value="{{ $student->email }}" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="gender"><i class="fas fa-venus-mars mr-1"></i>Gender <span class="text-danger">*</span></label>
                                    <select class="form-control" id="gender" name="gender" data-editable required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" {{ $student->gender === 'Male' ? 'selected' : '' }}>Male</option>
                                        <option value="Female" {{ $student->gender === 'Female' ? 'selected' : '' }}>Female</option>
                                        <option value="Other" {{ $student->gender === 'Other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

<!-- Parent/Guardian Information -->
<hr>
<h6 class="mb-3"><i class="fas fa-user-friends mr-2"></i>Parent/Guardian Information</h6>

@php
    $displayGuardians = (isset($guardians) && count($guardians) > 0) ? $guardians : [null];
@endphp

@foreach($displayGuardians as $index => $guardian)
    <div class="guardian-card mb-3 p-3 border rounded" data-guardian-id="{{ $guardian->id ?? '' }}">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-user mr-1"></i>First Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control guardian-first-name" 
                        name="guardian_first_name[]" data-editable
                        value="{{ $guardian->first_name ?? '' }}" 
                        placeholder="Enter first name" 
                        {{ $mode === 'edit' ? 'required' : 'readonly' }} />
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label><i class="fas fa-user mr-1"></i>Last Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control guardian-last-name" 
                        name="guardian_last_name[]" data-editable
                        value="{{ $guardian->last_name ?? '' }}" 
                        placeholder="Enter last name"
                        {{ $mode === 'edit' ? 'required' : 'readonly' }} />
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label><i class="fas fa-envelope mr-1"></i>Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="email" class="form-control guardian-email" 
                            name="guardian_email[]"
                            value="{{ $guardian->email ?? '' }}" 
                            placeholder="Enter email"
                            readonly />
                        @if($guardian && $mode === 'view')
                            <div class="input-group-append">
                                <button type="button" class="btn btn-default btn-sm btn-change-email" 
                                    data-guardian-id="{{ $guardian->id }}">
                                    <i class="fas fa-edit"></i> Change
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        @if($guardian && $guardian->id && $mode === 'view')
            <div class="row">
                <div class="col-md-12">
                    <div class="guardian-actions">
                        <span class="verification-status badge" data-guardian-id="{{ $guardian->id }}">
                            <i class="fas fa-spinner fa-spin"></i> <small>Checking...</small>
                        </span>
                        <button type="button" class="btn btn-sm btn-primary float-right btn-email-action" 
                            data-guardian-id="{{ $guardian->id }}" 
                            data-action="verification">
                            <i class="fas fa-envelope"></i> <span class="btn-text">Loading...</span>
                        </button>
                    </div>
                </div>
            </div>
        @elseif($mode === 'view')
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-primary mb-0">
                        <i class="fas fa-info-circle mr-2"></i>No guardian information available yet.
                    </div>
                </div>
            </div>
        @endif
    </div>
@endforeach


                    </div>
                </form>
                </div>

<!-- Enrollment History Card -->
<div class="card card-primary card-outline">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-history mr-2"></i>Enrollment History</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 300px;">
            <table class="table table-sm table-hover m-0">
                <thead>
                    <tr>
                        <th>School Year</th>
                        <th>Semester</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrolledSemesters as $semester)
                        <tr>
                            <td>{{ $semester->year_start }} - {{ $semester->year_end }}</td>
                            <td>{{ $semester->semester_name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-3">
                                <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                No enrollment history found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

                <!-- Enrolled Classes Card -->
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-book-open mr-2"></i>Enrolled Classes</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 300px;">
                            <table class="table table-sm table-hover m-0" id="enrolledClassesTable">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Subject Name</th>
                                        <th>School Year</th>
                                        <th>Semester</th>
                                    </tr>
                                </thead>
                                <tbody id="enrolledClassesBody">
                                    <tr>
                                        <td colspan="4" class="text-center py-3">
                                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                            <p class="mb-0">Loading enrolled classes...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const API_ROUTES = {
            updateStudentProfile: "{{ route('profile.student.update', ['id' => $student->id]) }}",
            getEnrolledClasses: "{{ route('profile.student.enrolled_classes', ['id' => $student->id]) }}",
            redirectBack: "{{ route('profile.student.show', ['id' => $student->id]) }}",
            studentId: "{{ $student->id }}"
        };
    </script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection