@extends('layouts.main')

@section('styles')
@if(isset($styles))
    @foreach($styles as $style)
        <link rel="stylesheet" href="{{ asset('assets/css/'.$style) }}">
    @endforeach
@endif
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        .filter-card { background: #f8f9fa; border: 1px solid #e9ecef; }
        .filter-card .form-control { 
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
    <li class="breadcrumb-item active">Class List</li>
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
                    <label>Search Classes</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by class name or code...">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-block" id="clearFilters">
                        <i class="fas fa-undo mr-1"></i> Clear
                    </button>
                </div>
                <div class="col-md-6 text-right">
                    <button type="button" class="btn btn-primary" onclick="openClassModal()">
                        <i class="fas fa-plus mr-1"></i> New Class
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Class Table Card -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-book mr-2"></i>Class List</h3>
            <div class="card-tools">
                <span class="badge badge-primary" id="classCount">0 Classes</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="classesTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 35%">Class Name</th>
                            <th style="width: 15%">Written Work</th>
                            <th style="width: 15%">Performance Task</th>
                            <th style="width: 15%">Quarterly Assessment</th>
                            <th class="text-center" style="width: 10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="classTableBody">
                        <!-- DataTables will populate this via AJAX -->
                    </tbody>
                </table>
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
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        const API_ROUTES = {
            insertClass: "{{ route('admin.insert_class') }}",
            getClass: "{{ route('admin.get_class', ':id') }}",
            updateClass: "{{ route('admin.update_class', ':id') }}",
            getClassesList: "{{ route('admin.get_classes_list') }}"
        };
    </script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/'.$script) }}"></script>
        @endforeach
    @endif
@endsection