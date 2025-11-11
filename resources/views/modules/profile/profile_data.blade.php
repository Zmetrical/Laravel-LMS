@extends('layouts.main')

@section('styles')

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif

<!-- CSS - use <link> tag -->
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">

@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Student Profile</li>
    </ol>
@endsection

@section('content')
<br>
    <div class="container-fluid">
        <!-- Profile Card -->
        <div class="card card-primary card-outline">
            <div class="card-body">
                <div class="row">
                    <!-- Profile Image Column -->
                    <div class="col-md-3 text-center">
                        <img class="profile-user-img img-fluid img-circle mb-3"
                        src="{{ $student->profile_image ? asset('storage/' . $student->profile_image) : asset('img/default-avatar.png') }}"
                        alt="Profile Image" style="width: 150px; height: 150px; min-height: 150px; object-fit: cover;">
                    </div>

                    <!-- Profile Information Column -->
                    <div class="col-md-9">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h4 class="text-primary mb-1">{{ $student->first_name . " " . $student->last_name}}</h4>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-lg-6">
                                <div class="form-group">
                                    <label class="text-sm"><i class="fas fa-user mr-1"></i>First Name</label>
                                    <input type="text" class="form-control form-control-sm bg-light" readonly
                                        value="{{ $student->first_name}}" />
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-6">
                                <div class="form-group">
                                    <label class="text-sm"><i class="fas fa-user mr-1"></i>Last Name</label>
                                    <input type="text" class="form-control form-control-sm bg-light" readonly
                                        value="{{ $student->last_name }}" />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-lg-6">
                                <div class="form-group">
                                    <label class="text-sm"><i class="fas fa-id-card mr-1"></i>Student ID</label>
                                    <input type="text" class="form-control form-control-sm bg-light" readonly
                                        value="{{ $student->student_number }}" />
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-6">
                                <div class="form-group">
                                    <label class="text-sm"><i class="fas fa-envelope mr-1"></i>Email Address</label>
                                    <input type="email" class="form-control form-control-sm bg-light" readonly
                                        value="{{ $student->email }}" />
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 col-lg-6">
                                <div class="form-group">
                                    <label class="text-sm"><i class="fas fa-key mr-1"></i>Password</label>
                                    <div class="input-group input-group-sm">
                                        <input type="password" id="passwordField" class="form-control bg-light" readonly
                                            value="{{ $student->student_password }}" />
                                        <div class="input-group-append">
                                            <button class="btn btn-info" type="button" id="togglePassword">
                                                <i class="fas fa-eye" id="toggleIcon"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-6">
                                <div class="form-group">
                                    <label class="text-sm"><i class="fas fa-venus-mars mr-1"></i>Gender</label>
                                    <input type="text" class="form-control form-control-sm bg-light" readonly
                                        value="{{ ucfirst($student->gender) }}" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('scripts')

<!-- JS - use <script> tag -->
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Toggle password visibility
    $('#togglePassword').on('click', function() {
        const passwordField = $('#passwordField');
        const toggleIcon = $('#toggleIcon');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
});
</script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection