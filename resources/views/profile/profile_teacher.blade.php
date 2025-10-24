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

    <style>

    </style>
@endsection

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-user-graduate"></i> Teacher Page
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route(name: 'admin.home') }}">Home</a></li>
                <li class="breadcrumb-item active">Teacher Page</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <!-- Left Column - Profile Card -->
            <div class="col-md-4">
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile">
                        <div class="text-center">
                            <img id="profileImagePreview" class="profile-user-img img-fluid img-circle mb-4" src="" alt=""
                                style="width: 150px; height: 150px; min-height: 150px; min-height: 150px; object-fit: cover;">
                        </div>
                        <div class="text-center mb-3">
                            <label for="profileImageInput" class="btn btn-default mb-0">
                                <i class="fas fa-camera"></i> Upload Photo
                            </label>
                            <input type="file" id="profileImageInput" accept="image/*" class="d-none">
                        </div>
                        <h3 class="profile-username text-center">Ms. Elena Dela Cruz</h3>

                        <ul class="list-group list-group-unbordered mb-3">
                            <li class="list-group-item">
                                <b>Email:</b> <span class="float-right">{{ $teachers->email}}</span>
                            </li>

                            <li class="list-group-item">
                                <b>Phone Number:</b> <span class="float-right">{{ $teachers->phone}}</span>
                            </li>
                        </ul>

                        <button class="btn btn-warning btn-block" data-toggle="modal" data-target="#changePasswordModal">
                            <i class="fas fa-key"></i> Change Password
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column - Update Information -->
            <div class="col-md-8">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Personal Information</h3>
                    </div>
                    <form id="profileForm">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="firstName">First Name</label>
                                        <input type="text" class="form-control" id="firstName" value="{{ $teachers->first_name}}" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lastName">Last Name</label>
                                        <input type="text" class="form-control" id="lastName" value="{{ $teachers->last_name}}" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" class="form-control" id="email" value="{{ $teachers->email}}" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="text">Phone Number</label>
                                        <input type="text" class="form-control" id="phone" value="{{ $teachers->phone}}" />
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="dateJoined">Date Joined</label>
                                        <input type="date" class="form-control" id="dateJoined" value="{{ $teachers->email}}"
                                            readonly />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')

    <!-- JS - use <script> tag -->
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>


    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection