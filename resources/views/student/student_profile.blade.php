@extends('layouts.main-student')

@section('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('student.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Profile</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <!-- Left Container -->
            <div class="col-md-3">
                <!-- Profile Card -->
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center mb-3">
                            <img id="profileImagePreview" class="profile-user-img img-fluid img-circle"
                                src="{{ $student->profile_image ? asset('storage/' . $student->profile_image) : asset('img/default-avatar.png') }}"
                                alt="Profile Image"
                                style="width: 200px; height: 200px; object-fit: cover;">
                        </div>

                        <h3 class="profile-username text-center">{{ $student->student_number }}</h3>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b><i class="fas fa-user mr-1"></i> Student Type</b>
                                <span class="float-right">{{ ucfirst($student->student_type) }}</span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-book mr-1"></i> Strand</b>
                                <span class="float-right">{{ $student->strand ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-layer-group mr-1"></i> Year Level</b>
                                <span class="float-right">{{ $student->level ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b><i class="fas fa-users mr-1"></i> Section</b>
                                <span class="float-right">{{ $student->section ?? 'N/A' }}</span>
                            </li>
                        </ul>
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
                                    <label><i class="fas fa-user mr-1"></i>First Name</label>
                                    <input type="text" class="form-control" readonly 
                                           value="{{ $student->first_name }}" />
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label><i class="fas fa-user mr-1"></i>M.I.</label>
                                    <input type="text" class="form-control" readonly 
                                           value="{{ $student->middle_name ? strtoupper(substr($student->middle_name, 0, 1)) : '' }}" />
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label><i class="fas fa-user mr-1"></i>Last Name</label>
                                    <input type="text" class="form-control" readonly 
                                           value="{{ $student->last_name }}" />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-envelope mr-1"></i>Email Address</label>
                                    <input type="email" class="form-control" readonly 
                                           value="{{ $student->email }}" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><i class="fas fa-venus-mars mr-1"></i>Gender</label>
                                    <input type="text" class="form-control" readonly 
                                           value="{{ $student->gender }}" />
                                </div>
                            </div>
                        </div>

                        <!-- Parent/Guardian Information -->
                        <hr>
                        <h6 class="mb-3"><i class="fas fa-user-friends mr-2"></i>Parent/Guardian Information</h6>

                        @php
                            // Ensure we always have at least one guardian slot to display
                            $displayGuardians = (isset($guardians) && count($guardians) > 0) ? $guardians : [null];
                        @endphp

                        @foreach($displayGuardians as $index => $guardian)
                            <div class="mb-3 p-3">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-user mr-1"></i>First Name</label>
                                            <input type="text" class="form-control" readonly 
                                                value="{{ $guardian->first_name ?? '' }}" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label><i class="fas fa-user mr-1"></i>Last Name</label>
                                            <input type="text" class="form-control" readonly 
                                                value="{{ $guardian->last_name ?? '' }}" />
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group mb-0">
                                            <label><i class="fas fa-envelope mr-1"></i>Email</label>
                                            <input type="email" class="form-control" readonly 
                                                value="{{ $guardian->email ?? '' }}" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if(!$loop->last)
                                <div class="mb-2"></div>
                            @endif
                        @endforeach

                    </div>
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
                                <tbody id="enrollmentHistoryBody">
                                    <tr>
                                        <td colspan="2" class="text-center py-3">
                                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                            <p class="mb-0">Loading enrollment history...</p>
                                        </td>
                                    </tr>
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
                            <table class="table table-sm table-hover m-0">
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
            getEnrolledClasses: "{{ route('student.profile.enrolled_classes') }}",
            getEnrollmentHistory: "{{ route('student.profile.enrollment_history') }}",
        };
    </script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection