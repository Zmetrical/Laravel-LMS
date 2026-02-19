@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <style>
        .filter-card { background: #f8f9fa; border: 1px solid #e9ecef; }
        .filter-card .form-control { 
            font-size: 0.875rem; 
            height: calc(2.25rem + 2px);
        }
        .filter-card label { 
            font-size: 0.75rem; 
            font-weight: 600; 
            color: #6c757d; 
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }
        .table-responsive { overflow-x: auto; }
        #studentTable { width: 100% !important; }
        .badge small { font-size: 0.7rem; margin-left: 2px; }

        /* Skeleton rows */
        .skeleton-row td {
            padding: 0.6rem 0.75rem;
        }
        .skeleton-line {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.2s infinite;
            border-radius: 3px;
            height: 13px;
            display: inline-block;
        }
        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Pagination */
        .pagination-info { font-size: 0.8rem; color: #6c757d; }

        /* Per-page select */
        #perPageSelect { width: auto; display: inline-block; font-size: 0.8rem; height: calc(1.9rem + 2px); }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Student List</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card filter-card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label>Search Student</label>
                    <input type="text" class="form-control" id="searchStudent" placeholder="Number or Name...">
                </div>
                <div class="col-md-2">
                    <label>Semester</label>
                    <select class="form-control" id="semester">
                        <option value="">All Semesters</option>
                        @foreach($semesters as $sem)
                            <option value="{{ $sem->id }}"
                                {{ isset($activeSemester) && $activeSemester->id == $sem->id ? 'selected' : '' }}>
                                {{ $sem->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Student Type</label>
                    <select class="form-control" id="studentType">
                        <option value="">All Types</option>
                        <option value="regular">Regular</option>
                        <option value="irregular">Irregular</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label>Strand</label>
                    <select class="form-control" id="strand">
                        <option value="">All</option>
                        @foreach($strands as $strand)
                            <option value="{{ $strand->code }}">{{ $strand->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label>Level</label>
                    <select class="form-control" id="level">
                        <option value="">All</option>
                        @foreach($levels as $level)
                            <option value="{{ $level->name }}">{{ $level->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Section</label>
                    <select class="form-control" id="section">
                        <option value="">All Sections</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex">
                    <button class="btn btn-secondary btn-block" id="clearFilters" title="Clear Filters">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Table Card -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users mr-2"></i>Student List</h3>
            <div class="card-tools d-flex align-items-center">
                <label class="mb-0 mr-2 text-sm text-muted">Show</label>
                <select class="form-control" id="perPageSelect">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="badge badge-primary ml-2" id="studentsCount">Loading...</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="studentTable">
                    <thead>
                        <tr>
                            <th>Semester</th>
                            <th>Student Number</th>
                            <th>Full Name</th>
                            <th>Strand</th>
                            <th>Level</th>
                            <th>Section</th>
                            <th>Type</th>
                            <th class="text-center">Verified</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        {{-- Populated via AJAX --}}
                    </tbody>
                </table>
            </div>

            <!-- No results -->
            <div id="noResultsMessage" class="alert alert-primary text-center m-3" style="display: none;">
                <i class="fas fa-info-circle"></i> No students found matching your filters.
            </div>
        </div>

        <!-- Pagination row -->
        <div class="card-footer d-flex justify-content-between align-items-center" id="paginationWrapper" style="display: none !important;">
            <div class="pagination-info" id="paginationInfo"></div>
            <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
    <script>
        const API_ROUTES = {
            getStudentsAjax: "{{ route('admin.students.list.ajax') }}",
            getSections:     "{{ route('admin.sections.filter') }}",
        showStudent:     "{{ url('profile/student') }}",
            editStudent:     "{{ url('profile/student') }}", 
        };
        @if(isset($activeSemester))
        const DEFAULT_SEMESTER = "{{ $activeSemester->id }}";
        @else
            const DEFAULT_SEMESTER = "";
        @endif
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection