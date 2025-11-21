@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
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
        
        /* Select2 custom styling (kept in case you still use Select2 elsewhere) */
        .select2-container--bootstrap4 .select2-selection {
            height: calc(2.25rem + 2px) !important;
            font-size: 0.875rem;
        }
        .select2-container--bootstrap4 .select2-selection__rendered {
            line-height: calc(2.25rem) !important;
        }
        .select2-container--bootstrap4 .select2-selection__arrow {
            height: calc(2.25rem) !important;
        }
        
        /* Expandable Row Styles */
        .expand-btn {
            cursor: pointer;
            transition: transform 0.2s;
            border: none;
            background: transparent;
            padding: 0.25rem 0.5rem;
            color: #6c757d;
        }
        .expand-btn:hover {
            color: #007bff;
        }
        .expand-btn.expanded {
            transform: rotate(90deg);
        }
        .expand-btn i {
            font-size: 1rem;
        }
        
        .classes-detail-row {
            background-color: #f8f9fa;
        }
        .classes-detail-cell {
            padding: 1rem !important;
            border-top: none !important;
        }
        .class-item {
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.5rem;
            background: white;
            border-left: 3px solid #007bff;
            border-radius: 0.25rem;
        }
        .class-item:last-child {
            margin-bottom: 0;
        }
        .class-name {
            color: #495057;
            font-weight: 500;
        }
        .no-classes {
            padding: 1rem;
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }

        /* Styles for bootstrap searchable dropdown to match form-control */
        .dropdown .dropdown-menu {
            min-width: 100%;
            border-radius: 0.25rem;
        }
        .dropdown .form-control {
            cursor: text;
        }
        .class-option[hidden] {
            display: none !important;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Teacher List</li>
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
                    <label>Search Teacher</label>
                    <input type="text" class="form-control" id="searchTeacher" placeholder="Name or Email...">
                </div>

                <!-- Replaced Select2 with Bootstrap searchable dropdown -->
                <div class="col-md-4">
                    <label>Filter by Class</label>

                    <div class="dropdown w-100">
                        <input 
                            type="text" 
                            id="classSearchInput" 
                            class="form-control" 
                            placeholder="Search or select a class..." 
                            data-toggle="dropdown"
                            autocomplete="off"
                            aria-haspopup="true" aria-expanded="false"
                        >

                        <ul class="dropdown-menu w-100" id="classDropdownList" style="max-height: 280px; overflow-y: auto;">
                            <li><a class="dropdown-item class-option" data-id="">All Classes</a></li>
                            <hr>
                            @foreach($classes as $class)
                                <li><a class="dropdown-item class-option" data-id="{{ $class->id }}">{{ $class->class_name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                
                <div class="col-md-1 d-flex">
                    <button class="btn btn-outline-secondary btn-block" id="clearFilters" title="Clear Filters">
                        <i class="fas fa-undo"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Teacher Table Card -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chalkboard-teacher mr-2"></i>Teacher List</h3>
            <div class="card-tools">
                <span class="badge badge-primary" id="teachersCount">{{ count($teachers) }} Teachers</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0" id="teacherTable" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th class="text-center">Classes</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="teacherTableBody">
                        @foreach ($teachers as $teacher)
                            <tr data-teacher-id="{{ $teacher->id }}" data-classes='@json($teacher->classes)'>
                                <td class="text-center">
                                    <button class="expand-btn" data-teacher-id="{{ $teacher->id }}" title="Show Classes">
                                        <i class="fas fa-chevron-right"></i>
                                    </button>
                                </td>
                                <td>{{ $teacher->last_name }}, {{ $teacher->first_name }} {{ $teacher->middle_name }}</td>
                                <td>{{ $teacher->email }}</td>
                                <td class="text-center">
                                    <span class="badge badge-primary">{{ count($teacher->classes) }}</span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('profile.teacher.show', $teacher->id) }}" 
                                        class="btn btn-sm btn-outline-secondary" title="View Profile">
                                        <i class="fas fa-user"></i>
                                    </a>
                                    <a href="{{ route('profile.teacher.edit', $teacher->id) }}"
                                        class="btn btn-sm btn-outline-primary" title="Edit">
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
    <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection