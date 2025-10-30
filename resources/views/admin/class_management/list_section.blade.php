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
                <i class="fas fa-users"></i> List of Sections
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
                <li class="breadcrumb-item active">List Sections</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Section List</h3>
                <div class="card-tools">
                    <button class="btn btn-sm" data-toggle="modal" data-target="#createSectionModal">
                        <i class="fas fa-plus-circle"></i> Create Section
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select class="form-control" id="filterStrand">
                            <option value="">All Strands</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="filterYear">
                            <option value="">All Year Levels</option>
                            <option value="11">Grade 11</option>
                            <option value="12">Grade 12</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th >#</th>
                                <th >Code</th>
                                <th >Name</th>
                                <th >Strand</th>
                                <th >Year Level</th>
                                <th  class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="sectionsTableBody">
                            <tr>
                                @foreach ($sections as $section)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $section->code }}</td>
                                        <td>{{ $section->name }}</td>
                                        <td>{{ $section->strand }}</td>

                                        <td>{{ $section->level }}</td>


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

    <!-- Create/Edit Section Modal -->
    <div class="modal fade" id="createSectionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sectionModalTitle">
                        <i class="fas fa-plus-circle"></i> Create New Section
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
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
                                        placeholder="e.g., Virgo, Aries" />
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="strand">Strand <span class="text-danger">*</span></label>
                                    <select class="form-control" id="strand" name="strand" required>
                                        <option value="">Select Strand</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="yearLevel">Year Level <span class="text-danger">*</span></label>
                                    <select class="form-control" id="yearLevel" name="year_level" required>
                                        <option value="">Select Year Level</option>
                                        <option value="11">Grade 11</option>
                                        <option value="12">Grade 12</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="capacity">Section Capacity</label>
                                    <input type="number" class="form-control" id="capacity" name="capacity" min="1"
                                        max="100" placeholder="e.g., 40" />
                                </div>
                            </div>
                        </div>
                        <input type="hidden" id="sectionId" name="id" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save"></i> Save Section
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Section Confirmation Modal -->
    <div class="modal fade" id="deleteSectionModal" tabindex="-1" role="dialog">
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
                    <p>Are you sure you want to delete this section?</p>
                    <p class="mb-0"><strong id="deleteSectionName"></strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteSection">
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