@extends('layouts.main-student')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-book"></i> My Classes
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('student.home') }}">Home</a></li>
                <li class="breadcrumb-item active">My Classes</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <!-- Loading State -->
        <div id="loadingState" class="text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading your classes...</p>
        </div>

        <!-- Empty State -->
        <div id="emptyState" style="display: none;">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                    <h4>No Classes Yet</h4>
                    <p class="text-muted">You are not enrolled in any classes at the moment.</p>
                </div>
            </div>
        </div>

        <!-- Classes Grid -->
        <div id="classesGrid" class="row" style="display: none;"></div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
    
    <script>
        const API_ROUTES = {
            getClasses: "{{ route('student.classes.list') }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection