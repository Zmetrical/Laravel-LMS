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
        <li class="breadcrumb-item active">Section List</li>
    </ol>
@endsection

@section('content')
<br>
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-users"></i> Section Management</h3>
                <div class="card-tools">
                    <button class="btn btn-sm btn-primary" onclick="openCreateModal()">
                        <i class="fas fa-plus"></i> Create Section
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="small">Strand</label>
                        <select class="form-control form-control-sm" id="filterStrand">
                            <option value="">All Strands</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="small">Level</label>
                        <select class="form-control form-control-sm" id="filterLevel">
                            <option value="">All Levels</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="small">Search</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="searchSection" placeholder="Search by section name or code...">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="button" onclick="loadSections()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead class="thead-light">
                            <tr>
                                <th width="5%" class="text-center">#</th>
                                <th width="15%">Name</th>
                                <th width="15%">Strand</th>
                                <th width="10%">Level</th>
                                <th width="5%" class="text-center">Classes</th>
                                <th width="5%" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sectionsTableBody">
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    Loading sections...
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
                        <i class="fas fa-plus-circle"></i> Create New Section
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="sectionForm">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="sectionName">Section Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="sectionName" name="name" required
                                        placeholder="e.g., VIRGO, ARIES" />
                                    <small class="form-text text-muted">Enter section name (will be converted to uppercase)</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="strandSelect">Strand <span class="text-danger">*</span></label>
                                    <select class="form-control" id="strandSelect" name="strand_id" required>
                                        <option value="">Select Strand</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="levelSelect">Level <span class="text-danger">*</span></label>
                                    <select class="form-control" id="levelSelect" name="level_id" required>
                                        <option value="">Select Level</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Generated Code</label>
                                    <input type="text" class="form-control bg-light" id="generatedCode" readonly
                                        placeholder="Will be auto-generated" />
                                    <small class="form-text text-muted">Code format: STRAND-LEVEL-NAME</small>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" id="sectionId" name="id" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> Save Section
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Classes Modal -->
    <div class="modal fade" id="viewClassesModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header modal-outline">
                    <h5 class="modal-title">
                        <i class="fas fa-book"></i> Enrolled Classes - <span id="sectionNameDisplay"></span>
                    </h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">Class Code</th>
                                    <th width="40%">Class Name</th>
                                    <th width="35%">Grade Distribution</th>
                                </tr>
                            </thead>
                            <tbody id="classesTableBody">
                                <tr>
                                    <td colspan="4" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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