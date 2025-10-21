@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif


    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">


    <style>


    </style>
@endsection

@section('breadcrumb')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>
                <i class="fas fa-user-graduate"></i> Student List
            </h1>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item"><a href="{{ route(name: 'admin.home') }}">Home</a></li>
                <li class="breadcrumb-item active">Student List</li>
            </ol>
        </div>
    </div>
@endsection

@section('content')

    <!-- Content -->
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <!-- Compact Filter Area -->
                <div class="row align-items-end mb-3">
                    <div class="col-auto">
                        <label class="mb-1 text-sm">Strand</label>
                        <select class="form-control form-control-sm" id="strand" name="strand" required>
                            <option hidden disabled selected>Select Strand</option>
                            @foreach($strands as $strand)
                                <option value="{{ $strand->id }}">{{ $strand->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="mb-1 text-sm">Year</label>
                        <select class="form-control form-control-sm" id="level" name="level" required>
                            <option hidden disabled selected>Select Year Level</option>
                            @foreach($levels as $level)
                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="mb-1 text-sm">Section</label>
                        <select class="form-control form-control-sm" id="section" name="section" required>
                            <option hidden disabled selected>Select Section</option>
                        </select>
                    </div>

                    <!-- Search Filter (Student Number) -->
                    <div class="col-auto" id="searchFilter">
                        <label class="mb-1 text-sm">Student Number</label>
                        <input type="text" class="form-control form-control-sm" id="studentNumber"
                            placeholder="Enter student number">
                    </div>

                    <div class="col-auto ml-auto">
                        <button class="btn btn-sm btn-secondary" id="clearFilters">
                            <i class="fas fa-undo"></i> Clear filters
                        </button>
                        <button class="btn btn-sm btn-secondary" id="exportBtn">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </button>
                    </div>


                </div>

                <div class="border-top pt-3">
                    <div class="row">
                        <div class="col-auto">
                            <strong class="d-block mb-1" style="font-size: 14px;">First name</strong>
                            <div id="firstNameFilter" class="btn-group btn-group-sm" role="group"></div>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-auto">
                            <strong class="d-block mb-1" style="font-size: 14px;">Last name</strong>
                            <div id="lastNameFilter" class="btn-group btn-group-sm" role="group"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body ">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0" id="studentTable" style="width: 100%;">
                        <thead
                            style="position: sticky; top: 0; background-color: #fff; z-index: 10; box-shadow: 0 2px 2px -1px rgba(0,0,0,0.1);">
                            <tr>
                                <th>Student Number</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Strand</th>
                                <th>Level</th>
                                <th>Section</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody">
                            @foreach ($students as $student)
                                <tr>
                                    <td>{{ $student->student_number }}</td>
                                    <td>{{ $student->first_name }}</td>
                                    <td>{{ $student->last_name }}</td>
                                    <td>{{ $student->strand }}</td>
                                    <td>{{ $student->level }}</td>
                                    <td>{{ $student->section }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info">
                                            <i class="fas fa-user"></i> Profile
                                        </button>
                                        <button class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export E-Class Record</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="exportForm">
                        <div class="form-group">
                            <label>Region</label>
                            <input type="text" class="form-control" id="region" required>
                        </div>
                        <div class="form-group">
                            <label>Division</label>
                            <input type="text" class="form-control" id="division" required>
                        </div>
                        <div class="form-group">
                            <label>School Name</label>
                            <input type="text" class="form-control" id="schoolName" required>
                        </div>
                        <div class="form-group">
                            <label>School ID</label>
                            <input type="text" class="form-control" id="schoolId" required>
                        </div>
                        <div class="form-group">
                            <label>School Year</label>
                            <input type="text" class="form-control" id="schoolYear" value="2024-2025" required>
                        </div>
                        <div class="form-group">
                            <label>Grade & Section</label>
                            <input type="text" class="form-control" id="gradeSection" required>
                        </div>
                        <div class="form-group">
                            <label>Teacher</label>
                            <input type="text" class="form-control" id="teacher" required>
                        </div>
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" class="form-control" id="subject" value="Core Subject (All Track)" required>
                        </div>
                        <div class="form-group">
                            <label>Semester</label>
                            <select class="form-control" id="semester" required>
                                <option value="1st">1st</option>
                                <option value="2nd">2nd</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Track</label>
                            <input type="text" class="form-control" id="track" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmExport">
                        <i class="fas fa-download"></i> Generate Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')

    <!-- DataTables -->
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>

    <!-- ExcelJs (CDN stays as-is) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>


    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection