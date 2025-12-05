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

        <!-- Profile Card - Horizontal Layout -->
        <div class="card card-primary card-outline">
            <div class="card-body">
                <form id="profileForm" method="POST" data-student-id="{{ $student->id }}">
                    @if($mode === 'edit')
                        @csrf
                        @method('POST')
                    @endif

                    <div class="row">
                        <!-- Profile Image Column -->
                        <div class="col-md-3 text-center">
                            <img id="profileImagePreview" class="profile-user-img img-fluid img-circle mb-3 me-3"
                            src="{{ $student->profile_image ? asset('storage/' . $student->profile_image) : asset('img/default-avatar.png') }}"
                            alt="Profile Image" style="width: 150px; height: 150px; min-height: 150px; object-fit: cover;">

                            <div class="mb-3" data-editable>
                                <label for="profileImageInput" class="btn btn-default mb-0">
                                    <i class="fas fa-camera"></i> Upload Photo
                                </label>
                                <input type="file" id="profileImageInput" name="profile_image"
                                    class="d-none">
                            </div>
                        </div>

                        <!-- Profile Information Column -->
                        <div class="col-md-9">
                            <div class="row mb-4">
                                <div class="col-6">
                                    <h4 class="text-primary mb-1">{{ $student->first_name . " " . $student->last_name}}</h4>
                                </div>
                                <!-- Action Buttons -->
                                <div class="col-6 d-flex justify-content-end">
                                    @if($mode === 'view')
                                        <a href="{{ route('profile.student.edit', $student->id) }}" class="btn btn-primary">
                                            <i class="fas fa-edit"></i> Edit Profile
                                        </a>
                                    @else
                                        <button type="submit" class="btn btn-primary mr-2">
                                            <i class="fas fa-save"></i> Save Changes
                                        </button>
                                        <a href="{{ route('profile.student.show', $student->id) }}" class="btn btn-secondary">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-5 col-lg-5">
                                    <div class="form-group">
                                        <label for="firstName" class="text-sm"><i class="fas fa-user mr-1"></i>First
                                            Name</label>
                                        <input type="text" class="form-control form-control-sm" id="firstName"
                                            name="first_name" data-editable placeholder="Enter first name"
                                            value="{{ $student->first_name}}" />
                                    </div>
                                </div>
                                <div class="col-md-2 col-lg-2">
                                    <div class="form-group">
                                        <label for="middleInitial" class="text-sm"><i class="fas fa-user mr-1"></i>Middle Initial</label>
                                        <input type="text" class="form-control form-control-sm" id="middleInitial"
                                            name="middle_name" data-editable placeholder="M.I."
                                            maxlength="1"
                                            value="{{ $student->middle_name ? strtoupper(substr($student->middle_name, 0, 1)) : '' }}" />
                                    </div>
                                </div>
                                <div class="col-md-5 col-lg-5">
                                    <div class="form-group">
                                        <label for="lastName" class="text-sm"><i class="fas fa-user mr-1"></i>Last
                                            Name</label>
                                        <input type="text" class="form-control form-control-sm" id="lastName"
                                            name="last_name" data-editable placeholder="Enter last name"
                                            value="{{ $student->last_name }}" />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label for="studentId" class="text-sm"><i class="fas fa-id-card mr-1"></i>Student
                                            ID</label>
                                        <input type="text" class="form-control form-control-sm bg-light" id="studentId"
                                            readonly placeholder="USN"
                                            value="{{ $student->student_number }}" />
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label for="email" class="text-sm"><i class="fas fa-envelope mr-1"></i>Email
                                            Address</label>
                                        <input type="email" class="form-control form-control-sm" id="email" name="email"
                                            data-editable placeholder="Enter email" value="{{ $student->email }}" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- Academic Information Card -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-graduation-cap mr-2"></i>Academic Information
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon"><i class="fas fa-user"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Student Type</span>
                                <span class="info-box-number">{{ $student->student_type }}</span>
                            </div>
                        </div>
                    </div>


                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon"><i class="fas fa-book"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Strand</span>
                                <span class="info-box-number">{{ $student->strand }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon"><i class="fas fa-layer-group"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Year Level</span>
                                <span class="info-box-number">{{ $student->level }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="info-box bg-light">
                            <span class="info-box-icon"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Section</span>
                                <span class="info-box-number">{{ $student->section }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Enrollment History Card -->
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history mr-2"></i>Enrollment History
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="bg-light sticky-top">
                                    <tr>
                                        <th>School Year</th>
                                        <th>Semester</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($enrolledSemesters as $semester)
                                        <tr>
                                            <td class="">{{ $semester->year_start }} - {{ $semester->year_end }}</td>
                                            <td>
                                                {{ $semester->semester_name }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                                No enrollment history found
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enrolled Classes Card -->
            <div class="col-md-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-book-open mr-2"></i>Enrolled Classes
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0" id="enrolledClassesTable">
                                <thead class="bg-light sticky-top">
                                    <tr>
                                        <th style="width: 15%;">Code</th>
                                        <th>Subject Name</th>
                                        <th style="width: 20%;">School Year</th>
                                        <th style="width: 15%;">Semester</th>
                                    </tr>
                                </thead>
                                <tbody id="enrolledClassesBody">
                                    <tr>
                                        <td colspan="4" class="text-center py-4">
                                            <i class="fas fa-spinner fa-spin fa-2x text-primary mb-2"></i>
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
        };
    </script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection