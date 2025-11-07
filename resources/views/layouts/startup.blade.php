@extends('layouts.root')

@section('body')

    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif

            <div class="container-fluid">
                <div class="row mt-4">
                    <!-- Admin Card -->
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="card card-primary card-outline">
                            <div class="card-body box-profile">
                                <div class="text-center">
                                    <i class="fas fa-user-shield fa-5x text-primary mb-3"></i>
                                </div>
                                <h3 class="profile-username text-center">Admin Panel</h3>
                                <p class="text-muted text-center">Manage system settings and users</p>
                                <a href="{{ route('admin.login') }}" class="btn btn-primary btn-block"><i class="fas fa-cog mr-2"></i><b>Go to Admin</b></a>
                            </div>
                        </div>
                    </div>

                    <!-- Teacher Card -->
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="card card-success card-outline">
                            <div class="card-body box-profile">
                                <div class="text-center">
                                    <i class="fas fa-chalkboard-teacher fa-5x text-success mb-3"></i>
                                </div>
                                <h3 class="profile-username text-center">Teacher Portal</h3>
                                <p class="text-muted text-center">Manage classes and grades</p>
                                <a href="{{ route('teacher.home') }}" class="btn btn-success btn-block"><i class="fas fa-graduation-cap mr-2"></i><b>Go to Teacher</b></a>
                            </div>
                        </div>
                    </div>

                    <!-- Student Card -->
                    <div class="col-lg-4 col-md-6 col-sm-12">
                        <div class="card card-info card-outline">
                            <div class="card-body box-profile">
                                <div class="text-center">
                                    <i class="fas fa-user-graduate fa-5x text-info mb-3"></i>
                                </div>
                                <h3 class="profile-username text-center">Student Portal</h3>
                                <p class="text-muted text-center">View grades and progress</p>
                                <a href="{{ route('student.login') }}" class="btn btn-info btn-block"><i class="fas fa-book-reader mr-2"></i><b>Go to Student</b></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif

@endsection
