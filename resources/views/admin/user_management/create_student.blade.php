@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif

    <link rel="stylesheet" href="{{ asset('plugins/bs-stepper/css/bs-stepper.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/jsgrid/jsgrid.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/jsgrid/jsgrid-theme.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">


@endsection

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-user-graduate"></i> Student Information
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route(name: 'admin.home') }}">Home</a></li>
                <li class="breadcrumb-item active">Student Registration</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')
    <div class="bs-stepper">
        <div class="bs-stepper-header" role="tablist">
            <div class="step" data-target="#step-1">
                <button type="button" class="step-trigger" role="tab" aria-controls="step-1" id="stepper-trigger-1">
                    <span class="bs-stepper-circle">1</span>
                    <span class="bs-stepper-label">Academic Info</span>
                </button>
            </div>
            <div class="line"></div>
            <div class="step" data-target="#step-2">
                <button type="button" class="step-trigger" role="tab" aria-controls="step-2" id="stepper-trigger-2">
                    <span class="bs-stepper-circle">2</span>
                    <span class="bs-stepper-label">Personal Info</span>
                </button>
            </div>
        </div>

        <div class="bs-stepper-content">
            <form id="insert_students" method="POST" 
                data-submit-url="{{ route('procedure.insert_Students') }}"
                data-redirect-url="{{ route('admin.create_student') }}">
                
                @csrf

                <!-- Step 1: Academic Info -->
                <div id="step-1" class="content" role="tabpanel" aria-labelledby="stepper-trigger-1">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Academic Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="strand" class="font-weight-bold">
                                        Strand <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control form-control-lg" id="strand" name="strand" required>
                                        <option hidden disabled selected>Select Strand</option>
                                        @foreach($strands as $strand)
                                            <option value="{{ $strand->id }}">{{ $strand->code }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="level" class="font-weight-bold">
                                        Year Level <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control form-control-lg" id="level" name="level" required>
                                        <option hidden disabled selected>Select Year Level</option>
                                        @foreach($levels as $level)
                                            <option value="{{ $level->id }}">{{ $level->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="section" class="font-weight-bold">
                                        Section <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-control form-control-lg" id="section" name="section" required>
                                        <option hidden disabled selected>Select Section</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="button" class="btn btn-primary float-right" onclick="stepper.next()">
                                Next <i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Personal Info -->
                <!-- <div id="step-2" class="content" role="tabpanel" aria-labelledby="stepper-trigger-2">
                                <div class="card card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Personal Information</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="email">Email <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="email" name="email" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="firstName">First Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="firstName" name="first_name" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="middlename">Middle Initial</label>
                                                    <input type="text" class="form-control" id="middlename" name="middle_name">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="lastName">Last Name <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="lastName" name="last_name" required>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <button type="button" class="btn btn-secondary" onclick="stepper.previous()">
                                            <i class="fas fa-arrow-left"></i> Previous
                                        </button>
                                        <button type="submit" class="btn btn-success float-right">
                                            <i class="fas fa-save"></i> Submit
                                        </button>
                                    </div>
                                </div>
                            </div> -->

                <!-- Step 2: Personal Info Card -->
                <div id="step-2" class="content" role="tabpanel" aria-labelledby="stepper-trigger-2">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Personal Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="row mb-4 ">
                                <div class="col-md-6">
                                    <div class="input-group input-group-md">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-users"></i></span>
                                        </div>
                                        <input type="number" class="form-control" id="numRows" value="5" min="1" max="50"
                                            placeholder="Number of students">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button" id="generateRowsBtn">
                                                <i class="fas fa-plus-circle mr-1"></i> Generate
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 text-right">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-success btn-md">
                                            <i class="fas fa-file-excel mr-1"></i> Excel Actions
                                        </button>
                                        <button type="button"
                                            class="btn btn-success btn-md dropdown-toggle dropdown-toggle-split"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <span class="sr-only">Toggle Dropdown</span>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="#" id="importExcel">
                                                <i class="fas fa-upload text-success mr-2"></i> Upload Excel
                                                File
                                            </a>
                                            <a class="dropdown-item" href="#" id="generateTemplate">
                                                <i class="fas fa-download text-info mr-2"></i> Download Template
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Hidden file input -->
                                    <input type="file" class="d-none" id="excelFile" accept=".xlsx,.xls"
                                        onchange="handleExcelUpload(event)">
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center" width="50">#</th>
                                            <th width="150">Email <span class="text-danger">*</span></th>
                                            <th width="180">Last Name <span class="text-danger">*</span></th>
                                            <th width="180">First Name <span class="text-danger">*</span></th>
                                            <th width="80">M.I.</th>
                                            <th width="120">Gender <span class="text-danger">*</span></th>

                                            <th class="text-center" width="70">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentTableBody">
                                        <!-- Rows will be generated here -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-outline-success" id="addRowBtn">
                                    <i class="fas fa-plus mr-2"></i> Add Another Row
                                </button>
                            </div>


                        </div>
                        <div class="card-footer">
                            <hr>
                            <button type="button" class="btn btn-secondary" onclick="stepper.previous()">
                                <i class="fas fa-arrow-left"></i> Previous
                            </button>
                            <button type="submit" class="btn btn-success float-right">
                                <i class="fas fa-save"></i> Submit
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- BS Stepper -->
    <script src="{{ asset('plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
    <!-- JSGrid -->
    <script src="{{ asset('plugins/jsgrid/jsgrid.min.js') }}"></script>
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif

    <script>
        let stepper;
        $(document).ready(function () {
            stepper = new Stepper($('.bs-stepper')[0]);
        });
    </script>
@endsection