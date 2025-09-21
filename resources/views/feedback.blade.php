@extends('layouts.adminlte')

@section('title', 'Main')

@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection



@section('content')
    <div class="card card-default">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-bullhorn"></i>
                Callouts
            </h3>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <div class="callout callout-danger">
                <h5>I am a danger callout!</h5>

                <p>There is a problem that we need to fix. A wonderful serenity has taken possession of my entire
                    soul,
                    like these sweet mornings of spring which I enjoy with my whole heart.</p>
            </div>
            <div class="callout callout-info">
                <h5>I am an info callout!</h5>

                <p>Follow the steps to continue to payment.</p>
            </div>
            <div class="callout callout-warning">
                <h5>I am a warning callout!</h5>

                <p>This is a yellow callout.</p>
            </div>
            <div class="callout callout-success">
                <h5>I am a success callout!</h5>

                <p>This is a green callout.</p>
            </div>
        </div>
        <!-- /.card-body -->
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Progress bars</h3>
        </div>
        <!-- /.card-header -->
        <div class="card-body">
            <div class="progress mb-3">
                <div class="progress-bar bg-success" role="progressbar" aria-valuenow="40" aria-valuemin="0"
                    aria-valuemax="100" style="width: 40%">
                    <span class="sr-only">40% Complete (success)</span>
                </div>
            </div>
            <div class="progress mb-3">
                <div class="progress-bar bg-info" role="progressbar" aria-valuenow="20" aria-valuemin="0"
                    aria-valuemax="100" style="width: 20%">
                    <span class="sr-only">20% Complete</span>
                </div>
            </div>
            <div class="progress mb-3">
                <div class="progress-bar bg-warning" role="progressbar" aria-valuenow="60" aria-valuemin="0"
                    aria-valuemax="100" style="width: 60%">
                    <span class="sr-only">60% Complete (warning)</span>
                </div>
            </div>
            <div class="progress mb-3">
                <div class="progress-bar bg-danger" role="progressbar" aria-valuenow="80" aria-valuemin="0"
                    aria-valuemax="100" style="width: 80%">
                    <span class="sr-only">80% Complete</span>
                </div>
            </div>
        </div>
        <!-- /.card-body -->
    </div>

    <a class="btn btn-app">
        <span class="badge bg-info">12</span>
        <i class="fas fa-envelope"></i> Inbox
    </a>

    <a class="btn btn-app">
        <span class="badge bg-purple">891</span>
        <i class="fas fa-users"></i> Users
    </a>

    <a class="btn btn-app">
        <span class="badge bg-warning">3</span>
        <i class="fas fa-bullhorn"></i> Notifications
    </a>
    <div>
    <a href="https://adminlte.io/themes/v3/pages/UI/modals.html">
        Modals
    </a>
    </div>

@endsection




@section('scripts')
    <script>
    </script>
@endsection