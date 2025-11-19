@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
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
        <div class="card card-dark card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-alt"></i> School Year Management</h3>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm" id="addSchoolYearBtn">
                        <i class="fas fa-plus"></i> Add School Year
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="loadingIndicator" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-3">Loading school years...</p>
                </div>

                <div id="tableContainer" style="display: none;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="schoolYearsTable">
                            <thead class="bg-dark">
                                <tr>
                                    <th width="50">#</th>
                                    <th>School Year</th>
                                    <th>Code</th>
                                    <th width="120">Status</th>
                                    <th width="100">Semesters</th>
                                    <th width="200">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="schoolYearsTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="noDataMessage" class="text-center py-5" style="display: none;">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No school years found. Click "Add School Year" to create one.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit School Year Modal -->
    <div class="modal fade" id="schoolYearModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark">
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const API_ROUTES = {
            getSchoolYears: "{{ route('admin.schoolyears.list') }}",
            createSchoolYear: "{{ route('admin.schoolyears.create') }}",
            updateSchoolYear: "{{ route('admin.schoolyears.update', ['id' => ':id']) }}",
            setActive: "{{ route('admin.schoolyears.set-active', ['id' => ':id']) }}",
            semestersPage: "{{ route('admin.semesters.index') }}" 
        };
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection