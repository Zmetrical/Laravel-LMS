@extends('layouts.main-teacher')

@section('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('teacher.home') }}">Home</a></li>
        <li class="breadcrumb-item active">My Profile</li>
    </ol>
@endsection

@section('content')
<br>
    <div class="container-fluid">
        <div class="row">
            <!-- Left Column - Profile Card -->
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <img id="profileImagePreview" 
                                 class="profile-user-img img-fluid img-circle mb-4" 
                                 src="{{ $teacher->profile_image ? asset('storage/' . $teacher->profile_image) : asset('img/default-avatar.png') }}" 
                                 alt="Profile Image"
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        </div>

                        @if($mode === 'edit')
                        <div class="text-center mb-3">
                            <label for="profileImageInput" class="btn btn-secondary mb-0">
                                <i class="fas fa-camera"></i> Upload Photo
                            </label>
                            <input type="file" id="profileImageInput" accept="image/*" class="d-none">
                        </div>
                        @endif

                        <h3 class="profile-username text-center">
                            {{ $teacher->first_name }} {{ $teacher->last_name }}
                        </h3>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Email:</b> <span class="float-right">{{ $teacher->email }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Phone Number:</b> <span class="float-right">{{ $teacher->phone }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Gender:</b> <span class="float-right">{{ $teacher->gender ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item">
                                <b>Date Joined:</b> 
                                <span class="float-right">
                                    {{ $teacher->created_at ? date('M d, Y', strtotime($teacher->created_at)) : 'N/A' }}
                                </span>
                            </li>
                    </div>
                </div>
            </div>

            <!-- Right Column - Update Information -->
            <div class="col-md-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Personal Information</h3>
                        <div class="card-tools">
                            @if($mode === 'view')
                                <a href="{{ route('teacher.profile.edit') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit Profile
                                </a>
                            @else
                                <a href="{{ route('teacher.profile') }}" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            @endif
                        </div>
                    </div>
                    <form id="profileForm" data-teacher-id="{{ $teacher->id }}">
                        @csrf
                        <input type="hidden" id="teacherId" value="{{ $teacher->id }}">
                        
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="firstName">First Name <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="firstName" 
                                               name="first_name"
                                               value="{{ $teacher->first_name }}" 
                                               {{ $mode === 'view' ? 'readonly' : '' }}
                                               required />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lastName">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="lastName" 
                                               name="last_name"
                                               value="{{ $teacher->last_name }}" 
                                               {{ $mode === 'view' ? 'readonly' : '' }}
                                               required />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="middleName">Middle Name</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="middleName" 
                                               name="middle_name"
                                               value="{{ $teacher->middle_name ?? '' }}" 
                                               {{ $mode === 'view' ? 'readonly' : '' }} />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="gender">Gender</label>
                                        <select class="form-control" 
                                                id="gender" 
                                                name="gender"
                                                {{ $mode === 'view' ? 'disabled' : '' }}>
                                            <option value="">Select Gender</option>
                                            <option value="Male" {{ $teacher->gender === 'Male' ? 'selected' : '' }}>Male</option>
                                            <option value="Female" {{ $teacher->gender === 'Female' ? 'selected' : '' }}>Female</option>
                                            <option value="Other" {{ $teacher->gender === 'Other' ? 'selected' : '' }}>Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email"
                                               value="{{ $teacher->email }}" 
                                               {{ $mode === 'view' ? 'readonly' : '' }}
                                               required />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone Number <span class="text-danger">*</span></label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="phone" 
                                               name="phone"
                                               value="{{ $teacher->phone }}" 
                                               {{ $mode === 'view' ? 'readonly' : '' }}
                                               required />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="dateJoined">Date Joined</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="dateJoined" 
                                               value="{{ $teacher->created_at ? date('F d, Y', strtotime($teacher->created_at)) : 'N/A' }}"
                                               readonly />
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($mode === 'edit')
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary" id="saveProfileBtn">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="{{ route('teacher.profile') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>



@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const API_ROUTES = {
            updateTeacherProfile: "{{ route('teacher.profile.update') }}",
            redirectBack: "{{ route('teacher.profile') }}"
        };
    </script>
    <script src="{{ asset('js/teacher/teacher_profile.js') }}"></script>
@endsection