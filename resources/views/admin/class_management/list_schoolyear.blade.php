@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">School Year Management</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <div class="row">
        <!-- Left Panel: School Years by Status -->
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-alt"></i> School Years
                    </h3>
                </div>
                <div class="card-body p-0">
                    <!-- Status Tabs -->
                    <ul class="nav nav-pills nav-fill" id="statusTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="active-tab" data-toggle="pill" href="#activeTab">
                                <i class="fas fa-star"></i> Active
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="upcoming-tab" data-toggle="pill" href="#upcomingTab">
                                <i class="fas fa-clock"></i> Upcoming
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="archived-tab" data-toggle="pill" href="#archivedTab">
                                <i class="fas fa-archive"></i> Archived
                            </a>
                        </li>
                    </ul>

                    <!-- Loading Indicator -->
                    <div id="loadingIndicator" class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2 text-muted">Loading...</p>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content" id="statusTabContent" style="display: none;">
                        <!-- Active Tab -->
                        <div class="tab-pane fade show active" id="activeTab">
                            <div id="activeYearsList" class="list-group list-group-flush">
                            </div>
                        </div>

                        <!-- Upcoming Tab -->
                        <div class="tab-pane fade" id="upcomingTab">
                            <div id="upcomingYearsList" class="list-group list-group-flush">
                            </div>
                        </div>

                        <!-- Archived Tab -->
                        <div class="tab-pane fade" id="archivedTab">
                            <div id="archivedYearsList" class="list-group list-group-flush">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary btn-block" id="addSchoolYearBtn">
                        <i class="fas fa-plus"></i> Create New School Year
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Panel: Selected Year Details -->
        <div class="col-md-8">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title" id="detailsTitle">
                        <i class="fas fa-info-circle"></i> School Year Details
                    </h3>
                    <div class="card-tools" id="detailsTools" style="display: none;">
                        <button class="btn btn-tool" id="editYearBtn" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" style="min-height: 400px;">
                    <!-- Empty State -->
                    <div id="emptyState" class="text-center text-muted py-5">
                        <i class="fas fa-arrow-left fa-3x mb-3"></i>
                        <h5>Select a School Year</h5>
                        <p>Choose a school year from the list to view details and manage semesters</p>
                    </div>

                    <!-- Details Content -->
                    <div id="detailsContent" style="display: none;">
                        <!-- Year Info -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-primary">
                                        <i class="fas fa-calendar"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">School Year</span>
                                        <span class="info-box-number" id="yearDisplay">-</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-box bg-light">
                                    <span class="info-box-icon bg-info">
                                        <i class="fas fa-code"></i>
                                    </span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Year Code</span>
                                        <span class="info-box-number" id="codeDisplay">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="mb-3">
                            <span class="badge badge-lg" id="statusBadge"></span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mb-4" id="actionButtons">
                            <button class="btn btn-success" id="activateBtn" style="display: none;">
                                <i class="fas fa-check-circle"></i> Set as Active
                            </button>
                            <button class="btn btn-warning" id="archiveBtn" style="display: none;">
                                <i class="fas fa-archive"></i> Archive School Year
                            </button>
                        </div>

                        <!-- Semesters Section -->
                        <div class="card">
                            <div class="card-header bg-primary">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list"></i> Semesters
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="semestersLoading" class="text-center py-3">
                                    <i class="fas fa-spinner fa-spin"></i> Loading semesters...
                                </div>
                                <div id="semestersList" style="display: none;">
                                    <div class="list-group" id="semestersContainer">
                                    </div>
                                </div>
                                <div id="noSemesters" class="text-center text-muted py-3" style="display: none;">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No semesters found for this school year</p>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="#" class="btn btn-primary btn-sm" id="manageSemestersBtn">
                                    <i class="fas fa-cog"></i> Manage Semesters
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit School Year Modal -->
<div class="modal fade" id="schoolYearModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-alt"></i> <span id="modalTitle">Add School Year</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="schoolYearForm">
                <input type="hidden" id="schoolYearId">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="yearStart">Start Year <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="yearStart" 
                               min="2000" max="2100" placeholder="e.g., 2024" required>
                    </div>
                    <div class="form-group">
                        <label for="yearEnd">End Year <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="yearEnd" 
                               min="2000" max="2100" placeholder="e.g., 2025" required>
                    </div>
                    <div class="form-group" id="statusGroup" style="display: none;">
                        <label for="status">Status <span class="text-danger">*</span></label>
                        <select class="form-control" id="status">
                            <option value="upcoming">Upcoming</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const API_ROUTES = {
            getSchoolYears: "{{ route('admin.schoolyears.list') }}",
            createSchoolYear: "{{ route('admin.schoolyears.create') }}",
            updateSchoolYear: "{{ route('admin.schoolyears.update', ['id' => ':id']) }}",
            setActive: "{{ route('admin.schoolyears.set-active', ['id' => ':id']) }}",
            getSemesters: "{{ route('admin.semesters.list') }}",
            semestersPage: "{{ route('admin.semesters.index') }}",
            csrfToken: "{{ csrf_token() }}"
        };
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection