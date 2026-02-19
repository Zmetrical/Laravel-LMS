@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .submission-progress {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }
        .submission-progress-bar {
            height: 100%;
            transition: width 0.3s;
        }

        .class-card {
            border-left: 4px solid #6c757d;
            transition: all 0.2s;
        }
        .class-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Section Grades View</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <!-- Active Semester Info -->
    <div class="alert alert-dark">
        <h5><i class="icon fas fa-calendar-alt"></i> {{ $activeSemesterDisplay ?? 'No Active Semester' }}</h5>
    </div>

    <div class="row">
        <!-- Left Sidebar - Section List -->
        <div class="col-md-3">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-users"></i> Sections</h3>
                </div>
                <div class="card-body p-0">
                    <div class="p-3">
                        <input type="text" class="form-control form-control-sm" id="sectionSearch"
                               placeholder="Search sections...">
                    </div>
                    <div class="p-3 pt-0">
                        <select class="form-control form-control-sm mb-2" id="levelFilter">
                            <option value="">All Levels</option>
                        </select>
                        <select class="form-control form-control-sm" id="strandFilter">
                            <option value="">All Strands</option>
                        </select>
                    </div>
                    <div class="list-group list-group-flush" id="sectionsListContainer"
                        style="max-height: 550px; overflow-y: auto;">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2 mb-0 small">Loading sections...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div id="noSectionSelected" class="card card-primary card-outline">
                <div class="card-body text-center py-5">
                    <i class="fas fa-arrow-left fa-3x text-muted mb-3"></i>
                    <h5>No Section Selected</h5>
                    <p class="text-muted">Please select a section from the left sidebar to view grades.</p>
                </div>
            </div>

            <div id="gradesSection" style="display:none;">
                <!-- Section Info Header -->
                <div class="card card-primary">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-12">
                                <h4 class="mb-1" id="selectedSectionName">
                                    <i class="fas fa-users"></i>
                                </h4>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-layer-group"></i> <span id="levelDisplay"></span> |
                                    <i class="fas fa-graduation-cap"></i> <span id="strandDisplay"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Classes with Grade Status -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-bar"></i> Grade Submission Status</h3>
                        <div class="card-tools">
                            <span class="badge badge-light" id="classesCount">0 Classes</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="classesContainer">
                            <div class="text-center py-4 text-primary">
                                <i class="fas fa-spinner fa-spin"></i> Loading classes...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students in Section -->
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-graduate"></i> Students in Section</h3>
                        <div class="card-tools">
                            <span class="badge badge-light" id="studentsCount">0 Students</span>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card-body pb-0">
                        <!-- Search / gender / status / reset row -->
                        <div class="row">
                            <div class="col-md-5">
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" class="form-control" id="studentSearchFilter"
                                        placeholder="Search by name or student number...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-control form-control-sm" id="genderFilter">
                                    <option value="">All Genders</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control form-control-sm" id="gradeStatusFilter">
                                    <option value="">All Status</option>
                                    <option value="submitted">Submitted</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-secondary btn-sm btn-block" id="resetStudentFiltersBtn">
                                    <i class="fas fa-redo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0 pt-3">
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-striped table-hover mb-0">
                                <thead style="position: sticky; top: 0; z-index: 1; background: white;">
                                    <tr>
                                        <th width="20%">Student Number</th>
                                        <th width="45%">Name</th>
                                        <th width="10%" class="text-center">Gender</th>
                                        <th width="25%" class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="studentsBody">
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-primary">
                                            <i class="fas fa-spinner fa-spin"></i> Loading...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const API_ROUTES = {
            getSections: "{{ route('admin.sections.grades-list') }}",
            getSectionDetails: "{{ route('admin.sections.grades-details', ['id' => ':id']) }}",
        };
    </script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection