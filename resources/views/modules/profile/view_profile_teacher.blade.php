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
        <li class="breadcrumb-item"><a href="{{ route('admin.list_teacher') }}">Teacher List</a></li>
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
                                src="{{ $teacher->profile_image ? asset('storage/' . $teacher->profile_image) : asset('img/default-avatar.png') }}"
                                alt="Profile Image">
                        </div>

                        <h3 class="profile-username text-center">{{ $teacher->first_name }} {{ $teacher->last_name }}</h3>

                        <form id="profileForm" method="POST" data-student-id="{{ $teacher->id }}">
                            @if($mode === 'edit')
                                @csrf
                                @method('POST')
                            @endif
{{-- 
                            @if($mode === 'edit')
                                <div class="text-center mb-3">
                                    <label for="profileImageInput" class="btn btn-sm btn-default">
                                        <i class="fas fa-camera"></i> Upload Photo
                                    </label>
                                    <input type="file" id="profileImageInput" name="profile_image" accept="image/*" class="d-none">
                                </div>
                            @endif --}}

                                @if($mode === 'view')
                                    <hr>
                                    <a href="{{ route('profile.teacher.edit', $teacher->id) }}" class="btn btn-primary btn-block">
                                        <i class="fas fa-edit"></i> Edit Profile
                                    </a>
                                    <hr>
                                    <a href="{{ route('admin.teachers.history', $teacher->id) }}" class="btn btn-default btn-block mt-2">
                                        <i class="fas fa-history"></i> View Subject History
                                    </a>
                                @else
                                <hr>
                                <button type="submit" class="btn btn-primary btn-block mb-2">
                                    <i class="fas fa-save"></i> Save
                                </button>
                                <a href="{{ route('profile.teacher.show', $teacher->id) }}" class="btn btn-default btn-block">
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
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="firstName"><i class="fas fa-user mr-1"></i>First Name</label>
                                    <input type="text" class="form-control" id="firstName"
                                        name="first_name" placeholder="Enter first name"
                                        value="{{ $teacher->first_name }}"
                                        {{ $mode === 'view' ? 'readonly' : '' }} />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="middleName"><i class="fas fa-user mr-1"></i>Middle Name</label>
                                    <input type="text" class="form-control" id="middleName"
                                        name="middle_name" placeholder="Enter middle name"
                                        value="{{ $teacher->middle_name ?? '' }}"
                                        {{ $mode === 'view' ? 'readonly' : '' }} />
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="lastName"><i class="fas fa-user mr-1"></i>Last Name</label>
                                    <input type="text" class="form-control" id="lastName"
                                        name="last_name" placeholder="Enter last name"
                                        value="{{ $teacher->last_name }}"
                                        {{ $mode === 'view' ? 'readonly' : '' }} />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email"><i class="fas fa-envelope mr-1"></i>Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        placeholder="Enter email" value="{{ $teacher->email }}"
                                        {{ $mode === 'view' ? 'readonly' : '' }} />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone"><i class="fas fa-phone mr-1"></i>Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone"
                                        placeholder="Enter phone number" value="{{ $teacher->phone }}"
                                        {{ $mode === 'view' ? 'readonly' : '' }} />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="gender"><i class="fas fa-venus-mars mr-1"></i>Gender</label>
                                    <select class="form-control" id="gender" name="gender"
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
                                    <label for="dateJoined"><i class="fas fa-calendar mr-1"></i>Date Joined</label>
                                    <input type="text" class="form-control" id="dateJoined"
                                        value="{{ $teacher->created_at ? date('F d, Y', strtotime($teacher->created_at)) : 'N/A' }}"
                                        readonly />
                                </div>
                            </div>
                        </div>

                        <!-- Password Section (Admin Type 1 only) -->
                        @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->admin_type == 1)
                        <hr>
                        <h6 class="mb-3"><i class="fas fa-lock mr-2"></i>Security Information</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password"><i class="fas fa-key mr-1"></i>Password</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="passwordDisplay" 
                                            value="••••••••" readonly />
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-default" id="togglePassword" data-visible="false">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="passcode"><i class="fas fa-hashtag mr-1"></i>Passcode</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="passcodeDisplay" 
                                            value="••••••" readonly />
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-default" id="togglePasscode" data-visible="false">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
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
            updateTeacherProfile: "{{ route('profile.teacher.update', ['id' => $teacher->id]) }}",
            redirectBack: "{{ route('profile.teacher.show', ['id' => $teacher->id]) }}",
            getCredentials: "{{ route('admin.teacher.credentials', ['id' => $teacher->id]) }}"
        };
    </script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection