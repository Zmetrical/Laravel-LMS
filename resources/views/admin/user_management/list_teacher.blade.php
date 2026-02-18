@extends('layouts.main')

@section('styles')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <style>
        .filter-card { background: #f8f9fa; border: 1px solid #e9ecef; }
        .filter-card .form-control { font-size: 0.875rem; height: calc(2.25rem + 2px); }
        .filter-card label {
            font-size: 0.75rem; font-weight: 600; color: #6c757d;
            text-transform: uppercase; margin-bottom: 0.25rem;
        }

        .nav-tabs .nav-link { color: #6c757d; font-weight: 500; border: none; border-bottom: 2px solid transparent; padding: 0.75rem 1.5rem; }
        .nav-tabs .nav-link:hover { background-color: #f8f9fa; }
        .nav-tabs .nav-link.active { color: #007bff; background-color: transparent; border-bottom: 2px solid #007bff; }
        .nav-tabs { border-bottom: 1px solid #dee2e6; }
        .tab-content { padding-top: 1rem; }

        .expand-btn {
            cursor: pointer; transition: transform 0.2s;
            border: none; background: transparent; padding: 0.25rem 0.5rem; color: #6c757d;
        }
        .expand-btn:hover { color: #007bff; }
        .expand-btn.expanded { transform: rotate(90deg); }

        .classes-detail-row { background-color: #f8f9fa; }
        .classes-detail-cell { padding: 1rem !important; border-top: none !important; }
        .class-item {
            padding: 0.5rem 0.75rem; margin-bottom: 0.5rem;
            background: white; border-left: 3px solid #dee2e6; border-radius: 0.25rem;
        }
        .class-item:last-child { margin-bottom: 0; }

        .dropdown .dropdown-menu { min-width: 100%; border-radius: 0.25rem; }
        .class-option[hidden] { display: none !important; }
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
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chalkboard-teacher mr-2"></i>Teacher Management</h3>
        </div>
        <div class="card-body">

            <ul class="nav nav-tabs" id="teacherTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#activeTeachers" role="tab">
                        <i class="fas fa-check-circle mr-1"></i> Active
                        <span class="badge badge-secondary ml-1">{{ $activeCount }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#inactiveTeachers" role="tab">
                        <i class="fas fa-pause-circle mr-1"></i> Inactive
                        <span class="badge badge-secondary ml-1">{{ $inactiveCount }}</span>
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="teacherTabContent">

                {{-- ─── Active Teachers ──────────────────────────────────────────── --}}
                <div class="tab-pane fade show active" id="activeTeachers" role="tabpanel">
                    <div class="card filter-card mb-3">
                        <div class="card-body py-3">
                            <div class="row align-items-end">
                                <div class="col-md-5">
                                    <label>Search Teacher</label>
                                    <input type="text" class="form-control" id="searchActiveTeacher" placeholder="Name or email...">
                                </div>
                                <div class="col-md-5">
                                    <label>Filter by Class</label>
                                    <div class="dropdown w-100">
                                        <input type="text" id="classSearchInputActive" class="form-control"
                                               placeholder="Search or select a class..."
                                               data-toggle="dropdown" autocomplete="off">
                                        <ul class="dropdown-menu w-100" id="classDropdownListActive"
                                            style="max-height: 280px; overflow-y: auto;">
                                            <li><a class="dropdown-item class-option" data-id="">All Classes</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            @foreach($classes as $class)
                                                <li>
                                                    <a class="dropdown-item class-option" data-id="{{ $class->id }}">
                                                        {{ $class->class_name }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-secondary btn-block" id="clearActiveFilters">
                                        <i class="fas fa-undo"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="activeTeacherTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th style="width:40px;"></th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th class="text-center">Classes</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- teachers.status == 1 → active --}}
                                @foreach ($teachers->where('status', 1) as $teacher)
                                    <tr data-teacher-id="{{ $teacher->id }}"
                                        data-classes='@json($teacher->classes)'>
                                        <td class="text-center">
                                            <button class="expand-btn" data-teacher-id="{{ $teacher->id }}" title="Show Classes">
                                                <i class="fas fa-chevron-right"></i>
                                            </button>
                                        </td>
                                        <td>{{ $teacher->last_name }}, {{ $teacher->first_name }} {{ $teacher->middle_name }}</td>
                                        <td>{{ $teacher->email }}</td>
                                        <td class="text-center">
                                            <span class="badge badge-secondary">{{ count($teacher->classes) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.teachers.history', $teacher->id) }}"
                                               class="btn btn-sm btn-outline-secondary" title="History">
                                                <i class="fas fa-history"></i>
                                            </a>
                                            <a href="{{ route('profile.teacher.show', $teacher->id) }}"
                                               class="btn btn-sm btn-outline-secondary" title="Profile">
                                                <i class="fas fa-user"></i>
                                            </a>
                                            <a href="{{ route('profile.teacher.edit', $teacher->id) }}"
                                               class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- ─── Inactive Teachers ────────────────────────────────────────── --}}
                <div class="tab-pane fade" id="inactiveTeachers" role="tabpanel">
                    <div class="card filter-card mb-3">
                        <div class="card-body py-3">
                            <div class="row align-items-end">
                                <div class="col-md-10">
                                    <label>Search Teacher</label>
                                    <input type="text" class="form-control" id="searchInactiveTeacher" placeholder="Name or email...">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-outline-secondary btn-block" id="clearInactiveFilters">
                                        <i class="fas fa-undo"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0" id="inactiveTeacherTable" style="width:100%;">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- teachers.status == 0 → inactive --}}
                                @foreach ($teachers->where('status', 0) as $teacher)
                                    <tr data-teacher-id="{{ $teacher->id }}">
                                        <td>{{ $teacher->last_name }}, {{ $teacher->first_name }} {{ $teacher->middle_name }}</td>
                                        <td>{{ $teacher->email }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.teachers.history', $teacher->id) }}"
                                               class="btn btn-sm btn-outline-secondary" title="History">
                                                <i class="fas fa-history"></i>
                                            </a>
                                            <a href="{{ route('profile.teacher.show', $teacher->id) }}"
                                               class="btn btn-sm btn-outline-secondary" title="Profile">
                                                <i class="fas fa-user"></i>
                                            </a>
                                            <a href="{{ route('profile.teacher.edit', $teacher->id) }}"
                                               class="btn btn-sm btn-outline-secondary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>{{-- end tab-content --}}
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection