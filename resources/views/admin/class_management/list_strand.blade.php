@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif

    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">

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
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Strand List</h3>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createStrandModal">
                        <i class="fas fa-plus"></i> Create Strand
                    </button>
                </div>
            </div>
            <div class="card-body">

                <div class="row mb-3">
                    <div class="col-md-3">
                        <select class="form-control" id="sortFilter">
                            <option value="newest">Newest</option>
                            <option value="oldest">Oldest</option>
                            <option value="name">Course Name</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Search code..." id="searchCode">
                    </div>

                    <div class="col-md-3">
                        <input type="text" class="form-control" placeholder="Search courses..." id="searchName">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="statusFilter">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
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
                    <h5 class="modal-title text-center" id="sectionsModalTitle">
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
                                    <td colspan="4" class="text-center">
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