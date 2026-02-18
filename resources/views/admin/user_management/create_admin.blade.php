@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.list_admin') }}">Admin List</a></li>
        <li class="breadcrumb-item active">Add Admin</li>
    </ol>
@endsection

@section('content')
<br>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-cog mr-2"></i>Admin Registration
                        </h3>
                    </div>

                    <form id="insert_admin" method="POST">
                        @csrf
                        <div class="card-body">

                            <div class="form-group">
                                <label for="adminName">Admin Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="adminName"
                                        name="admin_name" placeholder="Enter full name" required />
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    </div>
                                    <input type="email" class="form-control" id="email"
                                        name="email" placeholder="Enter email address" required />
                                </div>
                            </div>

                        </div>

                        <div class="card-footer">
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo mr-1"></i>Reset
                            </button>
                            <button type="submit" class="btn btn-primary float-right">
                                <i class="fas fa-save mr-1"></i>Create Admin
                            </button>
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
            insertAdmin: "{{ route('admin.insert_admin') }}",
            redirectAfterSubmit: "{{ route('admin.list_admin') }}"
        };
    </script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection