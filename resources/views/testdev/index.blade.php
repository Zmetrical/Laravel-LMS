@extends('layouts.main-test')

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
<div class="row">
    <!-- Send Guardian Email Section -->
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Send Guardian Access Email</h3>
            </div>
            <form id="sendGuardianEmailForm">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Select Guardian <span class="text-danger">*</span></label>
                        <select class="form-control" id="guardian_selector" name="guardian_id" required style="width: 100%;">
                            <option value="">-- Select Guardian --</option>
                        </select>
                    </div>

                    <div id="guardianInfo" style="display: none;">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-user mr-2"></i><span id="guardianName"></span></h5>
                            <p class="mb-1"><strong>Email:</strong> <span id="guardianEmail"></span></p>
                            <p class="mb-0"><strong>Linked Students:</strong> <span id="guardianStudents"></span></p>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Test Mode:</strong> This will send an email to the selected guardian with their access link.
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-2"></i>Send Email
                    </button>
                </div>
            </form>
        </div>

        <!-- Result Display -->
        <div class="card card-success" id="resultCard" style="display: none;">
            <div class="card-header">
                <h3 class="card-title">Result</h3>
            </div>
            <div class="card-body">
                <div id="resultContent"></div>
            </div>
        </div>
    </div>

    <!-- Existing Guardians List -->
    <div class="col-md-6">
        <div class="card card-secondary">
            <div class="card-header">
                <h3 class="card-title">Existing Guardians</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" id="refreshGuardiansBtn">
                        <i class="fas fa-sync"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th class="text-center">Students</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="guardiansTableBody">
                        <tr>
                            <td colspan="5" class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Loading...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row">
            <div class="col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3 id="totalGuardians">0</h3>
                        <p>Total Guardians</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3 id="activeGuardians">0</h3>
                        <p>Active Guardians</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Access URL Modal -->
<div class="modal fade" id="viewUrlModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Guardian Access URL</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Access URL:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="accessUrlInput" readonly>
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="button" id="copyUrlBtn">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Important:</strong> This URL provides direct access to student grades. Share it only with authorized guardians.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <a href="#" class="btn btn-primary" id="openUrlBtn" target="_blank">
                    <i class="fas fa-external-link-alt mr-2"></i>Open in New Tab
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        const API_ROUTES = {
            sendGuardianEmail: "{{ route('testdev.send_guardian_email') }}",
            getGuardians: "{{ route('testdev.get_guardians') }}",
            getGuardianStudents: "{{ url('testdev/get-guardian-students') }}",
            toggleGuardianStatus: "{{ url('testdev/toggle-guardian-status') }}",
            guardianAccessRoute: "{{ route('guardian.access', ['token' => 'TOKEN_PLACEHOLDER']) }}"
        };
    </script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection