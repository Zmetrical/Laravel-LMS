@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        .filter-card { background: #f8f9fa; border: 1px solid #e9ecef; }
        .filter-card .form-control, .filter-card .form-select { 
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
        
        /* Tab Styling */
        .nav-tabs .nav-link {
            color: #6c757d;
            font-weight: 500;
            border: none;
            border-bottom: 2px solid transparent;
            padding: 0.75rem 1.5rem;
        }
        .nav-tabs .nav-link:hover {
            border-color: transparent;
            background-color: #f8f9fa;
        }
        .nav-tabs .nav-link.active {
            color: #007bff;
            background-color: transparent;
            border-color: transparent;
            border-bottom: 2px solid #007bff;
        }
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
        }

        .tab-content {
            padding-top: 1rem;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Section List</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <!-- Main Card with Tabs -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users mr-2"></i>Section Management</h3>
            <div class="card-tools">
                <button class="btn btn-primary btn-sm" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Create Section
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Tabs -->
            <ul class="nav nav-tabs" id="sectionTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="active-tab" data-toggle="tab" href="#activeSections" role="tab">
                        <i class="fas fa-check-circle mr-1"></i> Active Sections 
                        <span class="badge badge-white ml-1" id="activeCount">0</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="inactive-tab" data-toggle="tab" href="#inactiveSections" role="tab">
                        <i class="fas fa-pause-circle mr-1"></i> Inactive Sections 
                        <span class="badge badge-white ml-1" id="inactiveCount">0</span>
                    </a>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="sectionTabContent">
                <!-- Active Sections Tab -->
                <div class="tab-pane fade show active" id="activeSections" role="tabpanel">
                    <!-- Filter Card -->
                    <div class="card filter-card mb-3">
                        <div class="card-body py-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label>Strand</label>
                                    <select class="form-control" id="filterActiveStrand">
                                        <option value="">All Strands</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Level</label>
                                    <select class="form-control" id="filterActiveLevel">
                                        <option value="">All Levels</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Search</label>
                                    <input type="text" class="form-control" id="searchActiveSection" placeholder="Name...">
                                </div>
                                <div class="col-md-2 d-flex">
                                    <button class="btn btn-outline-secondary btn-block" id="clearActiveFilters" title="Clear Filters">
                                        <i class="fas fa-undo"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Sections Table -->
                    <div class="table-responsive position-relative">
                        <div id="activeTableLoading" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin"></i> Initializing...
                        </div>
                        <table class="table table-striped table-hover mb-0" id="activeSectionTable" style="width: 100%; display: none;">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Strand</th>
                                    <th>Level</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="activeSectionsTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Inactive Sections Tab -->
                <div class="tab-pane fade" id="inactiveSections" role="tabpanel">
                    <!-- Filter Card -->
                    <div class="card filter-card mb-3">
                        <div class="card-body py-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <label>Strand</label>
                                    <select class="form-control" id="filterInactiveStrand">
                                        <option value="">All Strands</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Level</label>
                                    <select class="form-control" id="filterInactiveLevel">
                                        <option value="">All Levels</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Search</label>
                                    <input type="text" class="form-control" id="searchInactiveSection" placeholder="Name...">
                                </div>
                                <div class="col-md-2 d-flex">
                                    <button class="btn btn-outline-secondary btn-block" id="clearInactiveFilters" title="Clear Filters">
                                        <i class="fas fa-undo"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inactive Sections Table -->
                    <div class="table-responsive position-relative">
                        <div id="inactiveTableLoading" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin"></i> Initializing...
                        </div>
                        <table class="table table-striped table-hover mb-0" id="inactiveSectionTable" style="width: 100%; display: none;">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Strand</th>
                                    <th>Level</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="inactiveSectionsTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Section Modal -->
<div class="modal fade" id="sectionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sectionModalTitle">
                    <i class="fas fa-plus"></i> Create New Section
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="sectionForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="sectionName">Section Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" id="sectionName" name="name" required
                                    placeholder="e.g., VIRGO, ARIES" maxlength="50" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="strandSelect">Strand <span class="text-danger" id="strandRequired">*</span></label>
                                <select class="form-control" id="strandSelect" name="strand_id" required>
                                    <option value="">Select Strand</option>
                                </select>
                                <small class="form-text text-muted d-none" id="strandEditNote">
                                    <i class="fas fa-info-circle"></i> Cannot be changed after creation
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="levelSelect">Level <span class="text-danger" id="levelRequired">*</span></label>
                                <select class="form-control" id="levelSelect" name="level_id" required>
                                    <option value="">Select Level</option>
                                </select>
                                <small class="form-text text-muted d-none" id="levelEditNote">
                                    <i class="fas fa-info-circle"></i> Cannot be changed after creation
                                </small>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" id="sectionId" name="id" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    
    <script>
    const API_ROUTES = {
        getSections: "{{ route('admin.sections.data') }}",
        getStrands: "{{ route('admin.strands.data') }}",
        getLevels: "{{ route('admin.levels.data') }}",
        createSection: "{{ route('admin.sections.create') }}",
        updateSection: "{{ route('admin.sections.update', ':id') }}"
    };
    const TOGGLE_STATUS_URL = "{{ route('admin.sections.toggleStatus') }}";
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection