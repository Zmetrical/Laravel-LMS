@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
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
        .semester-badge {
            background-color: white;
            color: black;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .grade-weight-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin: 0 0.1rem;
            background-color: #ffffff;
            color: #000000;
            border: 1px solid #dee2e6;
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
    <!-- Filter Card -->
    <div class="card filter-card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label>Semester <span class="text-danger">*</span></label>
                    <select class="form-control" id="filterSemester">
                        <option value="">Select Semester</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Strand</label>
                    <select class="form-control" id="filterStrand">
                        <option value="">All Strands</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Level</label>
                    <select class="form-control" id="filterLevel">
                        <option value="">All Levels</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <!-- Spacer -->
                </div>
                <div class="col-md-1 d-flex">
                    <button class="btn btn-outline-secondary btn-block" id="clearFilters" title="Clear Filters">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Table Card -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users mr-2"></i>Section List</h3>
            <div class="card-tools">
                <span class="badge badge-primary mr-2" id="sectionsCount">0 Sections</span>
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#sectionModal" onclick="openCreateModal()">
                    <i class="fas fa-plus"></i> Create Section
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="sectionTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th width="8%">#</th>
                            <th width="20%">Name</th>
                            <th width="18%">Strand</th>
                            <th width="12%">Level</th>
                            <th width="12%" class="text-center">Classes</th>
                            <th width="15%" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sectionsTableBody">
                        <tr>
                            <td colspan="6" class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Loading sections...
                            </td>
                        </tr>
                    </tbody>
                </table>
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
                                <label for="strandSelect">Strand <span class="text-danger">*</span></label>
                                <select class="form-control" id="strandSelect" name="strand_id" required>
                                    <option value="">Select Strand</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="levelSelect">Level <span class="text-danger">*</span></label>
                                <select class="form-control" id="levelSelect" name="level_id" required>
                                    <option value="">Select Level</option>
                                </select>
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

<!-- View Classes Modal -->
<div class="modal fade" id="viewClassesModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="classesModalTitle">
                    <i class="fas fa-book"></i> View Classes
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-12">
                        <div class="d-flex align-items-center">
                            <label class="mb-0 mr-2">Semester:</label>
                            <span class="semester-badge" id="currentSemesterLabel"></span>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="35%">Class Name</th>
                                <th width="20%">Category</th>
                                <th width="10%" class="text-center">WW</th>
                                <th width="10%" class="text-center">PT</th>
                                <th width="10%" class="text-center">QA</th>
                            </tr>
                        </thead>
                        <tbody id="classesTableBody">
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    <i class="fas fa-info-circle"></i> Loading...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    
    <script>
    const API_ROUTES = {
        getSections: "{{ route('admin.sections.data') }}",
        getStrands: "{{ route('admin.strands.data') }}",
        getLevels: "{{ route('admin.levels.data') }}",
        getSemesters: "{{ route('admin.semesters.data') }}",
        createSection: "{{ route('admin.sections.create') }}",
        updateSection: "{{ route('admin.sections.update', ':id') }}",
        getSectionClasses: "{{ route('admin.sections.classes', ':id') }}"
    };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection