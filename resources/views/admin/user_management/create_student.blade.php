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
        <li class="breadcrumb-item active">Student Registration</li>
    </ol>
@endsection

@section('content')
    <br>
<div class="container-fluid">
    <form id="insert_students" method="POST">
        @csrf
        <div class="row">
            <!-- Left Sidebar - Academic Filters -->
<div class="col-lg-3">
    <div class="card card-primary card-outline sticky-top">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Academic Information</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label>Strand <span class="text-danger">*</span></label>
                <select class="form-control" id="strand" name="strand_id" required>
                    <option value="" selected disabled>Select Strand</option>
                    @foreach($strands as $strand)
                        <option value="{{ $strand->id }}">{{ $strand->code }} - {{ $strand->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label>Year Level <span class="text-danger">*</span></label>
                <select class="form-control" id="level" name="level_id" required>
                    <option value="" selected disabled>Select Year Level</option>
                    @foreach($levels as $level)
                        <option value="{{ $level->id }}">{{ $level->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Section Capacity Cards -->
            <div class="form-group">
                <label>Sections <span class="text-danger">*</span></label>
                <div id="sectionCapacityContainer">
                    <div class="alert alert-primary">
                         Select Strand and Level to view sections
                    </div>
                </div>
                <input type="hidden" id="selectedStrand" name="strand_id">
                <input type="hidden" id="selectedLevel" name="level_id">
            </div>

            <div class="form-group">
                <div class="d-flex flex-wrap">
                    <button type="button" class="btn btn-primary mr-1 mb-2 flex-grow-1" id="importExcel">
                        <i class="fas fa-upload mr-1"></i> Import
                    </button>

                    <button type="button" class="btn btn-primary mr-1 mb-2 flex-grow-1" id="generateTemplate">
                        <i class="fas fa-download mr-1"></i> Download
                    </button>
                </div>
                <input type="file" class="d-none" id="excelFile" accept=".xlsx,.xls">
            </div>

        </div>
    </div>
</div>

            <!-- Main Content - Student Entry -->
            <div class="col-lg-9">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-users mr-2"></i>Student Information</h3>
                    </div>
                    <div class="card-body">
                        <!-- Action Buttons Row -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="numRows" value="5" min="1" max="100" placeholder="Number of students">
                                    <div class="input-group-append">
                                        <button class="btn btn-secondary" type="button" id="generateRowsBtn">
                                            <i class="fas fa-plus-circle mr-1"></i> Generate Rows
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-right">
                                <button type="button" class="btn btn-secondary" id="addRowBtn">
                                    <i class="fas fa-plus mr-1"></i> Add Another Student
                                </button>
                            </div>
                        </div>

                        <!-- Student Table -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="40" class="text-center">#</th>
                                        <th width="220">Email</th>
                                        <th width="180">Last Name <span class="text-danger">*</span></th>
                                        <th width="180">First Name <span class="text-danger">*</span></th>
                                        <th width="80">M.I.</th>
                                        <th width="100" class="text-center">Gender <span class="text-danger">*</span></th>
                                        <th width="100" class="text-center">Type <span class="text-danger">*</span></th>
                                        <th width="60" class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="studentTableBody">
                                    <!-- Rows will be generated here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary btn-lg float-right">
                            <i class="fas fa-save mr-2"></i> Save All Students
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>

<script>
const API_ROUTES = {
    getSections: "{{ route('sections.data') }}",
    insertStudents: "{{ route('admin.insert_students') }}",
    redirectAfterSubmit: "{{ route('admin.list_student') }}"
};
</script>

@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection