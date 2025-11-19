@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Student Grades</li>
    </ol>
@endsection

@section('content')
<br>
    <div class="container-fluid">
        <!-- Active Semester Display -->
        <div class="alert alert-dark alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h5><i class="icon fas fa-calendar-alt"></i> <span id="activeSemesterDisplay">Loading...</span></h5>
            View and manage final grades for students across all classes.
        </div>

        <!-- Search & Filter Card -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-search"></i> Search Filters</h3>
            </div>
            <div class="card-body">
                <form id="searchForm">
                    <div class="row">
                        <!-- Semester Filter -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-calendar"></i> Semester *</label>
                                <select class="form-control" id="semesterFilter" required>
                                    <option value="">-- Select Semester --</option>
                                </select>
                            </div>
                        </div>

                        <!-- Class Filter -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label><i class="fas fa-book"></i> Class</label>
                                <select class="form-control" id="classFilter">
                                    <option value="">All Classes</option>
                                </select>
                            </div>
                        </div>

                        <!-- Status Filter -->
                        <div class="col-md-2">
                            <div class="form-group">
                                <label><i class="fas fa-flag"></i> Status</label>
                                <select class="form-control" id="statusFilter">
                                    <option value="all">All Status</option>
                                    <option value="passed">Passed</option>
                                    <option value="failed">Failed</option>
                                    <option value="inc">INC</option>
                                    <option value="drp">Dropped</option>
                                    <option value="w">Withdrawn</option>
                                </select>
                            </div>
                        </div>

                        <!-- Search Input -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label><i class="fas fa-user"></i> Student Search</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchInput" 
                                           placeholder="Name or Student Number">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-default btn-sm" id="resetFiltersBtn">
                                <i class="fas fa-redo"></i> Reset Filters
                            </button>
                            <button type="button" class="btn btn-success btn-sm float-right" id="exportBtn" disabled>
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row" id="statsCards" style="display: none;">
            <div class="col-lg-2 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="totalRecords">0</h3>
                        <p>Total Records</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3 id="passedCount">0</h3>
                        <p>Passed</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3 id="failedCount">0</h3>
                        <p>Failed</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3 id="incCount">0</h3>
                        <p>Incomplete</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3 id="drpCount">0</h3>
                        <p>Dropped</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-minus"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3 id="averageGrade">0</h3>
                        <p>Average Grade</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Card -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list"></i> Grade Records</h3>
                <div class="card-tools">
                    <span class="badge badge-light" id="recordsCount">No records</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div id="noSearchYet" class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Search Performed</h5>
                    <p class="text-muted">Please select a semester and click Search to view grade records.</p>
                </div>

                <div id="resultsSection" style="display: none;">
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="table table-striped table-hover mb-0">
                            <thead style="position: sticky; top: 0; z-index: 1; background: white;">
                                <tr>
                                    <th width="10%">Student No.</th>
                                    <th width="20%">Student Name</th>
                                    <th width="18%">Class</th>
                                    <th width="12%">Section</th>
                                    <th width="8%" class="text-center">Type</th>
                                    <th width="6%" class="text-center">WW</th>
                                    <th width="6%" class="text-center">PT</th>
                                    <th width="6%" class="text-center">QA</th>
                                    <th width="8%" class="text-center">Final Grade</th>
                                    <th width="8%" class="text-center">Remarks</th>
                                    <th width="8%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="gradesTableBody">
                                <!-- Populated via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grade Details Modal -->
    <div class="modal fade" id="gradeDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">
                        <i class="fas fa-chart-bar"></i> Grade Details
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Student Info -->
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Student Number:</th>
                                    <td id="detailStudentNumber"></td>
                                </tr>
                                <tr>
                                    <th>Student Name:</th>
                                    <td id="detailStudentName"></td>
                                </tr>
                                <tr>
                                    <th>Student Type:</th>
                                    <td id="detailStudentType"></td>
                                </tr>
                                <tr>
                                    <th>Section:</th>
                                    <td id="detailSection"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <th width="40%">Class:</th>
                                    <td id="detailClass"></td>
                                </tr>
                                <tr>
                                    <th>Computed By:</th>
                                    <td id="detailComputedBy"></td>
                                </tr>
                                <tr>
                                    <th>Computed At:</th>
                                    <td id="detailComputedAt"></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td id="detailStatus"></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <hr>

                    <!-- Grade Breakdown -->
                    <h6 class="mb-3"><i class="fas fa-calculator"></i> Grade Computation</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Written Works</h6>
                                    <h3 id="detailWW">0.00</h3>
                                    <p class="mb-0 small text-muted">
                                        Weight: <span id="detailWWPerc"></span>%
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Performance Tasks</h6>
                                    <h3 id="detailPT">0.00</h3>
                                    <p class="mb-0 small text-muted">
                                        Weight: <span id="detailPTPerc"></span>%
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h6 class="text-muted">Quarterly Assessment</h6>
                                    <h3 id="detailQA">0.00</h3>
                                    <p class="mb-0 small text-muted">
                                        Weight: <span id="detailQAPerc"></span>%
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h6 class="text-muted mb-2">Final Grade</h6>
                            <h2 class="mb-0" id="detailFinalGrade">0.00</h2>
                            <h4 class="mt-2" id="detailRemarks"></h4>
                        </div>
                    </div>

                    <!-- Component Breakdown -->
                    <div id="componentsSection" style="display: none;">
                        <hr>
                        <h6 class="mb-3"><i class="fas fa-list-ul"></i> Component Breakdown</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Item Name</th>
                                        <th class="text-center">Score</th>
                                        <th class="text-center">Max Score</th>
                                        <th class="text-center">Percentage</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody id="componentsTableBody">
                                    <!-- Populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
    
    <script>
        const API_ROUTES = {
            getClasses: "{{ route('admin.grades.classes') }}",
            getSemesters: "{{ route('admin.grades.semesters') }}",
            searchGrades: "{{ route('admin.grades.search') }}",
            getGradeDetails: "{{ route('admin.grades.details', ['id' => ':id']) }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection