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
        .filter-card { background: #f8f9fa; border: 1px solid #e9ecef; }
        .filter-card .form-control, .filter-card .form-select { 
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
        <li class="breadcrumb-item active">Student List</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <!-- Filter Card -->
    <div class="card filter-card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label>Search Student</label>
                    <input type="text" class="form-control" id="searchStudent" placeholder="Number or Name...">
                </div>
                <div class="col-md-2">
                    <label>Semester</label>
                    <select class="form-control" id="semester">
                        <option value="">All Semesters</option>
                        @foreach($semesters as $sem)
                            <option value="{{ $sem->id }}" 
                                {{ isset($activeSemester) && $activeSemester->id == $sem->id ? 'selected' : '' }}>
                                {{ $sem->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Student Type</label>
                    <select class="form-control" id="studentType">
                        <option value="">All Types</option>
                        <option value="regular">Regular</option>
                        <option value="irregular">Irregular</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label>Strand</label>
                    <select class="form-control" id="strand">
                        <option value="">All</option>
                        @foreach($strands as $strand)
                            <option value="{{ $strand->code }}">{{ $strand->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label>Level</label>
                    <select class="form-control" id="level">
                        <option value="">All</option>
                        @foreach($levels as $level)
                            <option value="{{ $level->name }}">{{ $level->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Section</label>
                    <select class="form-control" id="section">
                        <option value="">All Sections</option>
                    </select>
                </div>
                <div class="col-md-1 d-flex">
                    <button class="btn btn-secondary btn-block" id="clearFilters" title="Clear Filters">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Table Card -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users mr-2"></i>Student List</h3>
            <div class="card-tools">
                <span class="badge badge-primary" id="studentsCount">{{ count($students) }} Students</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="studentTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Student Number</th>
                            <th>Full Name</th>
                            <th>Strand</th>
                            <th>Level</th>
                            <th>Section</th>
                            <th>Type</th>
                            <th>Semester</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="studentTableBody">
                        @foreach ($students as $student)
                            <tr>
                                <td>{{ $student->student_number }}</td>
                                <td>{{ $student->last_name }}, {{ $student->first_name }}</td>
                                <td>{{ $student->strand }}</td>
                                <td>{{ $student->level }}</td>
                                <td>{{ $student->enrolled_section_name ?? $student->current_section }}</td>
                                <td>
                                    @php
                                        $type = $student->student_type ?? 'regular';
                                        $badgeClass = match($type) {
                                            'regular' => 'badge-primary',
                                            'irregular' => 'badge-secondary',
                                            default => 'badge-primary'
                                        };
                                    @endphp
                                    <span class="badge {{ $badgeClass }}">{{ ucfirst($type) }}</span>
                                </td>
                                <td data-semester-id="{{ $student->semester_id ?? '' }}">
                                    @if($student->semester_display)
                                        {{ $student->semester_display }}
                                    @else
                                        <span class="text-muted">No enrollment</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('profile.student.show', $student->id) }}" 
                                        class="btn btn-sm btn-secondary" title="View Profile">
                                        <i class="fas fa-user"></i>
                                    </a>
                                    <a href="{{ route('profile.student.edit', $student->id) }}"
                                        class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
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
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.3.0/exceljs.min.js"></script>
    <script>
        const API_ROUTES = {
            getSections: "{{ route('admin.sections.filter') }}",
        };
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection