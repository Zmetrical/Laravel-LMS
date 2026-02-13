@extends('layouts.main-test')
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('breadcrumb')
<nav aria-label="breadcrumb" class="breadcrumb-custom">
    <i class="fas fa-flask breadcrumb-icon"></i>
    <ol class="breadcrumb mb-0 bg-transparent">
        <li class="breadcrumb-item active">Test & Development</li>
    </ol>
</nav>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-10 col-lg-8">
        
        <!-- Guardian Email Verification Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Guardian Email Verification Testing</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" id="refreshBtn">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-secondary">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Testing Flow:</strong> Send verification email → Guardian verifies → Access email automatically sent
                </div>

                <div class="form-group">
                    <label>Select Guardian <span class="text-danger">*</span></label>
                    <select class="form-control" id="guardian_selector" style="width: 100%;">
                        <option value="">-- Select Guardian --</option>
                    </select>
                </div>

                <div id="guardianInfo" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon" id="verificationIcon">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Verification Status</span>
                                    <span class="info-box-number" id="verificationStatus">Unknown</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-secondary">
                                    <i class="fas fa-users"></i>
                                </span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Linked Students</span>
                                    <span class="info-box-number" id="studentCount">0</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="callout" id="guardianDetails">
                        <h5><i class="fas fa-user mr-2"></i><span id="guardianName"></span></h5>
                        <p class="mb-1"><strong>Email:</strong> <span id="guardianEmail"></span></p>
                        <p class="mb-1" id="verifiedAtContainer" style="display: none;">
                            <strong>Verified:</strong> <span id="verifiedAt"></span>
                        </p>
                        <p class="mb-0"><strong>Students:</strong> <span id="guardianStudents"></span></p>
                    </div>

                    <button type="button" class="btn btn-default btn-block" id="sendVerificationBtn">
                        <i class="fas fa-envelope-open-text mr-2"></i>Send Verification Email
                    </button>
                </div>
            </div>
        </div>

        <!-- Result Display -->
        <div class="card" id="resultCard" style="display: none;">
            <div class="card-header">
                <h3 class="card-title">Result</h3>
            </div>
            <div class="card-body">
                <div id="resultContent"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
    const API_ROUTES = {
        sendVerification: "{{ route('testdev.send_verification') }}",
        getGuardians: "{{ route('testdev.get_guardians') }}",
        getGuardianStudents: "{{ url('testdev/get-guardian-students') }}"
    };
</script>

@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection