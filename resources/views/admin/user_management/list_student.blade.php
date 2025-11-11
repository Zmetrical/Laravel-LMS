@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif

    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Student List</li>
    </ol>
@endsection

@section('content')
<br>
    <!-- Content -->
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <!-- Compact Filter Area -->
                <div class="row align-items-end mb-3">
                    <!-- Search Filter (Student Number) -->
                    <div class="col-auto" id="searchFilter">
                        <label class="mb-1 ">Student Number</label>
                        <input type="text" class="form-control" id="studentNumber"
                            placeholder="Enter student number">
                    </div>
                    <!-- Search Filter (Student Name) -->
                    <div class="col-auto" id="searchNameFilter">
                        <label class="mb-1">Student Name</label>
                        <input type="text" class="form-control" id="studentName"
                            placeholder="Enter student name">
                    </div>
                    <div class="col-auto">
                        <label class="mb-1">Strand</label>
                        <select class="form-control" id="strand" name="strand" required>
                            <option hidden disabled selected>Select Strand</option>
                            @foreach($strands as $strand)
                                <option value="{{ $strand->id }}">{{ $strand->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="mb-1 ">Year</label>
                        <select class="form-control " id="level" name="level" required>
                            <option hidden disabled selected>Select Year Level</option>
                            @foreach($levels as $level)
                                <option value="{{ $level->id }}">{{ $level->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label class="mb-1 ">Section</label>
                        <select class="form-control" id="section" name="section" required>
                            <option hidden disabled selected>Select Section</option>
                        </select>
                    </div>

                    <div class="col-auto ml-auto">
                        <button class="btn btn-secondary" id="clearFilters">
                            <i class="fas fa-undo"></i> Clear filters
                        </button>
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
                                        <a href="{{ route('profile.student.show', $student->id) }}" 
                                            class="btn btn-sm btn-info">
                                            <i class="fas fa-user"></i> Profile
                                        </a>
                                        <a href="{{ route('profile.student.edit', $student->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
    <script>
    const API_ROUTES = {
        getSections: "{{ route('sections.data') }}",
    };
    </script>


    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection