@extends('layouts.main')

@section('styles')
@if(isset($styles))
    @foreach($styles as $style)
        <link rel="stylesheet" href="{{ asset('assets/css/'.$style) }}">
    @endforeach
@endif
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
@endsection

@section('breadcrumb')
<li class="breadcrumb-item"><a href="#">Home</a></li>
<li class="breadcrumb-item active">List Class</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Class List</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" onclick="openClassModal()">
                            <i class="fas fa-plus"></i> New Course
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" class="form-control" id="searchInput" placeholder="Search classes...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" id="sortBy">
                                <option value="newest">Newest</option>
                                <option value="oldest">Oldest</option>
                                <option value="name">Course Name</option>
                            </select>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="classesTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%">#</th>
                                    <th style="width: 35%">Course Name</th>
                                    <th style="width: 15%">Written Work</th>
                                    <th style="width: 15%">Performance Task</th>
                                    <th style="width: 20%">Quarterly Assessment</th>
                                    <th style="width: 10%" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($classes as $class)
                                <tr data-id="{{ $class->id }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $class->class_name }}</td>
                                    <td>{{ $class->ww_perc }}%</td>
                                    <td>{{ $class->pt_perc }}%</td>
                                    <td>{{ $class->qa_perce }}%</td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-primary btn-edit" data-id="{{ $class->id }}" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No classes found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="row mt-3">
                        <div class="col-sm-12 col-md-5">
                            <div class="dataTables_info">
                                Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalEntries">{{ $classes->count() }}</span> entries
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit Modal  -->
<div class="modal fade" id="classModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="classModalTitle">Create New Class</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="classForm">
                @csrf
                <input type="hidden" id="class_id" name="class_id">
                <input type="hidden" id="form_method" name="_method" value="">
                
                <div class="modal-body">
                    <!-- Weight Alert -->
                    <div class="alert" id="weightAlert" style="display: none;">
                        <strong><span id="weightStatus"></span></strong><span id="weightMessage"></span>
                    </div>

                    <!-- Class Code (Read-only for Edit) -->
                    <div class="form-group" id="classCodeGroup" style="display: none;">
                        <label for="class_code">Class Code</label>
                        <input type="text" class="form-control" id="class_code" readonly style="background-color: #e9ecef;">
                    </div>

                    <!-- Class Name -->
                    <div class="form-group">
                        <label for="class_name">Class Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="class_name" name="class_name" placeholder="e.g., Mathematics 101" required>
                    </div>

                    <!-- Weight Distribution -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="ww_perc" class="pt-2 pb-3">Written Work <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control weight-input" id="ww_perc" name="ww_perc" value="30" min="0" max="100" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pt_perc" class="pt-2 pb-3">Performance Task <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control weight-input" id="pt_perc" name="pt_perc" value="50" min="0" max="100" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="qa_perce">Quarterly Assessment <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control weight-input" id="qa_perce" name="qa_perce" value="20" min="0" max="100" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save Class</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        const API_ROUTES = {
            insertClass: "{{ route('admin.insert_class') }}",
            getClass: "{{ route('admin.get_class', ':id') }}",
            updateClass: "{{ route('admin.update_class', ':id') }}"
        };
    </script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/'.$script) }}"></script>
        @endforeach
    @endif
@endsection