@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
@endsection

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-bookmark"></i> List of Strands
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                <li class="breadcrumb-item active">List Strands</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Strand List</h3>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#createStrandModal">
                        <i class="fas fa-plus-circle"></i> Create Strand
                    </button>
                </div>
            </div>
            <div class="card-body">
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
                                @foreach ($strands as $strand)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $strand->code }}</td>
                                        <td>{{ $strand->name }}</td>
                                        <td></td>

                                        <td class="text-center">
                                            <button class="btn btn-sm btn-info" onclick="editStrand(${strand.id})" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger"
                                                onclick="confirmDeleteStrand(${strand.id}, '${strand.code}')" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
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
                        <i class="fas fa-plus-circle"></i> Create New Strand
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
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

    <!-- Delete Strand Confirmation Modal -->
    <div class="modal fade" id="deleteStrandModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle"></i> Confirm Delete
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this strand?</p>
                    <p class="mb-0"><strong id="deleteStrandName"></strong></p>
                    <div class="alert alert-warning mt-3" id="strandWarning" style="display: none;">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> This strand has <span
                            id="sectionCount"></span> section(s). Deleting it will also delete all associated sections.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteStrand">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection