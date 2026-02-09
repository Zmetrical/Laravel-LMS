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
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Strand List</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card filter-card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label>Search Strand</label>
                    <input type="text" class="form-control" id="searchStrand" placeholder="Code or Name...">
                </div>
                <div class="col-md-7">
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

    <!-- Strand Table Card -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-book mr-2"></i>Strand List</h3>
            <div class="card-tools">
                <span class="badge badge-primary mr-2" id="strandsCount">0 Strands</span>
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createStrandModal">
                    <i class="fas fa-plus"></i> Create Strand
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="strandTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th width="10%">#</th>
                            <th width="15%">Code</th>
                            <th>Name</th>
                            <th width="15%" class="text-center">Sections</th>
                            <th width="15%" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="strandsTableBody">
                        <tr>
                            <td colspan="5" class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Loading strands...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Strand Modal -->
<div class="modal fade" id="createStrandModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="strandModalTitle">
                    <i class="fas fa-plus"></i> Create New Strand
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="strandForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="strandCode">Strand Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase" id="strandCode" name="code"
                                    required placeholder="e.g., ICT, ABM, STEM" maxlength="10" />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="strandName">Strand Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="strandName" name="name" required
                                    placeholder="e.g., Information and Communications Technology" />
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="strandId" name="id" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Strand
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Sections Modal -->
<div class="modal fade" id="viewSectionsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sectionsModalTitle">
                    <i class="fas fa-list"></i> Sections
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th width="10%">#</th>
                                <th>Name</th>
                                <th width="20%">Level</th>
                            </tr>
                        </thead>
                        <tbody id="sectionsTableBody">
                            <tr>
                                <td colspan="3" class="text-center">
                                    <i class="fas fa-spinner fa-spin"></i> Loading sections...
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
        getStrands: "{{ route('admin.strands.data') }}",
        createStrand: "{{ route('admin.strands.store') }}",
        updateStrand: "{{ route('admin.strands.update', ':id') }}",
        getStrandSections: "{{ route('admin.strands.sections', ':id') }}"
    };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection