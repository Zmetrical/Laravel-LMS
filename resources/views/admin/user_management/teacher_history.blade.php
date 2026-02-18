@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <style>
        .filter-card { background: #f8f9fa; border: 1px solid #e9ecef; }
        .filter-card .form-control { font-size: 0.875rem; height: calc(2.25rem + 2px); }
        .filter-card label {
            font-size: 0.75rem; font-weight: 600; color: #6c757d;
            text-transform: uppercase; margin-bottom: 0.25rem;
        }

        /* Teacher info */
        .teacher-info-card { background: white; border: 1px solid #dee2e6; }
        .teacher-name { font-size: 1.2rem; font-weight: 600; color: #212529; }
        .teacher-meta { color: #6c757d; font-size: 0.875rem; }

        /* School year card */
        .school-year-card {
            background: white; border: 1px solid #dee2e6;
            border-radius: 4px; margin-bottom: 1rem;
            transition: border-color 0.15s;
        }
        .school-year-card:hover { border-color: #adb5bd; }
        .school-year-card .card-body { padding: 1rem 1.25rem; }

        .school-year-header {
            display: flex; justify-content: space-between;
            align-items: flex-start; margin-bottom: 0.5rem;
        }
        .school-year-title { font-size: 1.05rem; font-weight: 600; margin: 0; }

        .info-row { display: flex; flex-wrap: wrap; gap: 1.25rem; margin-top: 0.4rem; }
        .info-item { display: flex; align-items: center; font-size: 0.825rem; }
        .info-item i { color: #6c757d; margin-right: 0.35rem; width: 13px; }
        .info-value { color: #495057; font-weight: 500; }

        /* Subject accordion */
        .subject-accordion { margin-top: 0.85rem; }
        .subject-item {
            border: 1px solid #e9ecef; border-radius: 4px;
            margin-bottom: 0.4rem; background: #f8f9fa;
        }
        .subject-header {
            padding: 0.65rem 1rem; cursor: pointer;
            display: flex; justify-content: space-between; align-items: center;
            user-select: none;
        }
        .subject-header:hover { background: #e9ecef; border-radius: 4px; }
        .subject-name { font-weight: 600; color: #212529; flex: 1; font-size: 0.9rem; }
        .subject-meta { display: flex; gap: 0.5rem; align-items: center; margin-right: 0.75rem; }
        .subject-expand-icon { transition: transform 0.2s; color: #6c757d; }
        .subject-item.expanded .subject-expand-icon { transform: rotate(90deg); }
        .subject-content { display: none; padding: 0 1rem 0.75rem 1rem; }
        .subject-item.expanded .subject-content { display: block; }

        .section-list { list-style: none; padding: 0; margin: 0; }
        .section-list-item {
            padding: 0.4rem 0.75rem; background: white;
            border-left: 3px solid #dee2e6;
            margin-bottom: 0.4rem; border-radius: 0 4px 4px 0;
            font-size: 0.85rem;
        }
        .section-code { font-weight: 600; color: #212529; }
        .section-details { color: #6c757d; margin-left: 0.4rem; }

        .semester-label {
            font-size: 0.78rem; font-weight: 600; color: #6c757d;
            text-transform: uppercase; margin: 0.75rem 0 0.35rem;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('admin.list_teacher') }}">Teacher List</a></li>
        <li class="breadcrumb-item active">Subject History</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">

    {{-- Teacher info --------------------------------------------------------}}
    <div class="card teacher-info-card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="teacher-name">
                        {{ $teacher->first_name }} {{ $teacher->middle_name }} {{ $teacher->last_name }}
                    </div>
                    <div class="teacher-meta mt-1">
                        <i class="fas fa-envelope mr-1"></i>{{ $teacher->email }}
                        <span class="mx-2">·</span>
                        <i class="fas fa-phone mr-1"></i>{{ $teacher->phone }}
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    {{-- Global teacher status badge --}}
                    @if($teacher->status == 1)
                        <span class="badge badge-secondary mr-2" style="font-size:0.8rem;padding:0.4em 0.75em;">
                            <i class="fas fa-circle mr-1" style="font-size:0.6rem;color:#28a745;"></i>Active
                        </span>
                    @else
                        <span class="badge badge-secondary mr-2" style="font-size:0.8rem;padding:0.4em 0.75em;">
                            <i class="fas fa-circle mr-1" style="font-size:0.6rem;color:#adb5bd;"></i>Inactive
                        </span>
                    @endif
                    @if($teacher->status == 1)
                        <button class="btn btn-sm btn-outline-secondary btn-toggle-status mr-1"
                                title="Deactivate Teacher"
                                data-teacher-id="{{ $teacher->id }}"
                                data-school-year-id="{{ $activeSchoolYear->id ?? 0 }}"
                                data-action="deactivate">
                            <i class="fas fa-pause mr-1"></i> Deactivate
                        </button>
                    @else
                        <button class="btn btn-sm btn-outline-secondary btn-toggle-status mr-1"
                                title="Reactivate Teacher"
                                data-teacher-id="{{ $teacher->id }}"
                                data-school-year-id="{{ $activeSchoolYear->id ?? 0 }}"
                                data-action="activate">
                            <i class="fas fa-play mr-1"></i> Reactivate
                        </button>
                    @endif
                    <a href="{{ route('profile.teacher.show', $teacher->id) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-user mr-1"></i> View Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters ------------------------------------------------------------}}
    <div class="card filter-card mb-3">
        <div class="card-body py-3">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label>Search Subject</label>
                    <input type="text" class="form-control" id="searchSubject" placeholder="Subject name...">
                </div>
                <div class="col-md-3">
                    <label>School Year</label>
                    <select class="form-control" id="schoolYearFilter">
                        <option value="">All School Years</option>
                        @foreach($schoolYearData as $data)
                            <option value="{{ $data['school_year_id'] }}">
                                {{ $data['school_year_code'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Status</label>
                    <select class="form-control" id="statusFilter">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="none">No Record</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary btn-block" id="clearFilters">
                        <i class="fas fa-undo"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- History ------------------------------------------------------------}}
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history mr-2"></i>Subject History</h3>
        </div>
        <div class="card-body">
            <div id="schoolYearContainer">
                @forelse($schoolYearData as $data)
                    <div class="school-year-card"
                         data-school-year-id="{{ $data['school_year_id'] }}"
                         data-trail-status="{{ $data['has_trail'] ? $data['trail_status'] : 'none' }}">
                        <div class="card-body">

                            {{-- Header row --}}
                            <div class="school-year-header">
                                <div>
                                    <h5 class="school-year-title">
                                        {{ $data['school_year_code'] }}
                                        @if($data['has_trail'])
                                            @if($data['trail_status'] === 'active')
                                                <span class="badge badge-primary ml-1">Active</span>
                                            @else
                                                <span class="badge badge-secondary ml-1" style="opacity:0.6;">Inactive</span>
                                            @endif
                                        @endif
                                    </h5>
                                    <div class="info-row">
                                        <div class="info-item">
                                            <i class="fas fa-book"></i>
                                            <span class="info-value">{{ $data['total_classes'] }} Subject(s)</span>
                                        </div>
                                        <div class="info-item">
                                            <i class="fas fa-users"></i>
                                            <span class="info-value">{{ $data['total_sections'] }} Section(s)</span>
                                        </div>
                                        @if($data['total_adviser_sections'] > 0)
                                            <div class="info-item">
                                                <i class="fas fa-user-tie"></i>
                                                <span class="info-value">{{ $data['total_adviser_sections'] }} Adviser Section(s)</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Subject accordion --}}
                            @if($data['total_classes'] > 0 || $data['total_adviser_sections'] > 0)
                                <div class="subject-accordion">

                                    @foreach($data['classes'] as $classCode => $classGroup)
                                        @php $firstClass = $classGroup->first(); @endphp
                                        <div class="subject-item"
                                             data-subject-name="{{ strtolower($firstClass->class_name) }}">
                                            <div class="subject-header">
                                                <div class="subject-name">
                                                    {{ $firstClass->class_name }}
                                                    <small class="text-muted ml-1">({{ $firstClass->class_code }})</small>
                                                </div>
                                                <div class="subject-meta">
                                                    <span class="badge badge-secondary">{{ $firstClass->class_category }}</span>
                                                    <span class="badge badge-secondary">
                                                        {{ $classGroup->sum('section_count') }} Section(s)
                                                    </span>
                                                    <i class="fas fa-chevron-right subject-expand-icon"></i>
                                                </div>
                                            </div>
                                            <div class="subject-content">
                                                @foreach($classGroup as $class)
                                                    @if($class->sections->count() > 0)
                                                        <p class="semester-label">
                                                            <i class="fas fa-calendar-alt mr-1"></i>{{ $class->semester_name }}
                                                        </p>
                                                        <ul class="section-list">
                                                            @foreach($class->sections as $section)
                                                                <li class="section-list-item">
                                                                    <span class="section-code">{{ $section->section_name }}</span>
                                                                    <span class="section-details">
                                                                        {{ $section->strand_code }} · {{ $section->level_name }}
                                                                    </span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach

                                    @if($data['total_adviser_sections'] > 0)
                                        <div class="subject-item" data-subject-name="class adviser">
                                            <div class="subject-header">
                                                <div class="subject-name">Class Adviser</div>
                                                <div class="subject-meta">
                                                    <span class="badge badge-secondary">ADVISER</span>
                                                    <span class="badge badge-secondary">
                                                        {{ $data['total_adviser_sections'] }} Section(s)
                                                    </span>
                                                    <i class="fas fa-chevron-right subject-expand-icon"></i>
                                                </div>
                                            </div>
                                            <div class="subject-content">
                                                @foreach($data['adviser_assignments'] as $semesterId => $advisers)
                                                    <p class="semester-label">
                                                        <i class="fas fa-calendar-alt mr-1"></i>{{ $advisers->first()->semester_name }}
                                                    </p>
                                                    <ul class="section-list">
                                                        @foreach($advisers as $adviser)
                                                            <li class="section-list-item">
                                                                <span class="section-code">{{ $adviser->section_name }}</span>
                                                                <span class="section-details">
                                                                    {{ $adviser->strand_code }} · {{ $adviser->level_name }}
                                                                </span>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                </div>
                            @else
                                <p class="text-muted mb-0 mt-2" style="font-size:0.875rem;">
                                    <i class="fas fa-inbox mr-1"></i> No subjects or adviser assignments
                                </p>
                            @endif

                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                        <p class="text-muted">No history found for this teacher</p>
                    </div>
                @endforelse
            </div>

            <div id="noResultsMessage" class="alert alert-secondary text-center mt-2" style="display:none;">
                <i class="fas fa-info-circle mr-1"></i> No records found matching your filters.
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const TOGGLE_STATUS_URL = "{{ route('admin.teachers.toggleStatus') }}";
        const TEACHER_ID = {{ $teacher->id }};
        const ACTIVE_SCHOOL_YEAR_ID = {{ $activeSchoolYear->id ?? 0 }};
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection