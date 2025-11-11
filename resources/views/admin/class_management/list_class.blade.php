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
        <li class="breadcrumb-item active">List Class</li>
    </ol>
@endsection

@section('content')
<br>
    <!-- Content Wrapper -->
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Class List</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                        data-target="#createClassModal">
                        <i class="fas fa-plus"></i> New Course
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
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Course Code</th>
                                <th>Course Name</th>
                                <th>Written Work</th>
                                <th>Performance Task</th>
                                <th>Quarterly Assessment</th>
                            </tr>
                        </thead>
                        <tbody id="classTableBody">
                            @foreach ($classes as $class)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $class->class_code }}</td>
                                    <td>{{ $class->class_name }}</td>
                                    <td>{{ $class->ww_perc }}%</td>
                                    <td>{{ $class->pt_perc }}%</td>
                                    <td>{{ $class->qa_perce }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row mt-3">
                    <div class="col-sm-12 col-md-5">
                        <div class="dataTables_info" id="tableInfo">
                            Showing 0 to 0 of 0 entries
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-7">
                        <ul class="pagination pagination-sm m-0 float-right" id="pagination">
                            <!-- Pagination will be generated here -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Class Modal -->
    <div class="modal fade" id="createClassModal" tabindex="-1" role="dialog" aria-labelledby="createClassModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createClassModalLabel">
                        <i class="fas fa-plus"></i> Create New Class
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="insert_class" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="class_code">Class Code <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="class_code" name="class_code" required
                                        placeholder="e.g., GEN-MATH-11">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="class_name">Class Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="class_name" name="class_name" required
                                        placeholder="e.g., General Mathematics">
                                </div>
                            </div>
                        </div>


                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="ww_perc">Written Work <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control weight-input" id="ww_perc" name="ww_perc"
                                            required min="0" max="100" value="30" step="1">
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="pt_perc">Performance Task <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control weight-input" id="pt_perc" name="pt_perc"
                                            required min="0" max="100" value="50" step="1">
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
                                        <input type="number" class="form-control weight-input" id="qa_perce" name="qa_perce"
                                            required min="0" max="100" value="20" step="1">
                                        <div class="input-group-append">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="alert" id="weightAlert" style="display: none;">
                                    <strong id="weightStatus"></strong>
                                    <span id="weightMessage"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Class
                        </button>
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
            insertClass: "{{ route('admin.insert_class') }}"
        };
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection