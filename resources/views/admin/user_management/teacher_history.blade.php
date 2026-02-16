@extends('layouts.main')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .filter-card { 
            background: #f8f9fa; 
            border: 1px solid #e9ecef; 
        }
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

        /* Teacher Info Card */
        .teacher-info-card {
            background: white;
            border: 1px solid #dee2e6;
        }
        .teacher-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #212529;
        }
        .teacher-email {
            color: #6c757d;
            font-size: 0.875rem;
        }

        /* School Year Card List */
        .school-year-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 1rem;
            transition: all 0.2s ease;
        }
        .school-year-card:hover {
            border-color: #007bff;
            box-shadow: 0 1px 4px rgba(0,123,255,0.1);
        }
        .school-year-card .card-body {
            padding: 1rem;
        }

        .school-year-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        .school-year-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #212529;
            margin: 0;
        }

        .info-row {
            display: flex;
            gap: 1.5rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }
        .info-item {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
        }
        .info-item i {
            color: #6c757d;
            margin-right: 0.4rem;
            width: 14px;
        }
        .info-label {
            color: #6c757d;
            margin-right: 0.3rem;
        }
        .info-value {
            color: #212529;
            font-weight: 500;
        }

        /* Subject Accordion */
        .subject-accordion {
            margin-top: 1rem;
        }
        .subject-item {
            border: 1px solid #e9ecef;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
        }
        .subject-header {
            padding: 0.75rem 1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }
        .subject-header:hover {
            background: #e9ecef;
        }
        .subject-name {
            font-weight: 600;
            color: #212529;
            flex: 1;
        }
        .subject-meta {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            margin-right: 1rem;
        }
        .subject-expand-icon {
            transition: transform 0.2s;
            color: #6c757d;
        }
        .subject-item.expanded .subject-expand-icon {
            transform: rotate(90deg);
        }
        .subject-content {
            display: none;
            padding: 0 1rem 0.75rem 1rem;
        }
        .subject-item.expanded .subject-content {
            display: block;
        }

        .section-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .section-list-item {
            padding: 0.5rem 0.75rem;
            background: white;
            border-left: 3px solid #007bff;
            margin-bottom: 0.5rem;
            border-radius: 0 4px 4px 0;
        }
        .section-code {
            font-weight: 600;
            color: #212529;
        }
        .section-details {
            font-size: 0.85rem;
            color: #6c757d;
            margin-left: 0.5rem;
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
    <!-- Teacher Info Card -->
    <div class="card teacher-info-card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="teacher-name">
                        {{ $teacher->first_name }} {{ $teacher->middle_name }} {{ $teacher->last_name }}
                    </div>
                    <div class="teacher-email">
                        <i class="fas fa-envelope mr-2"></i>{{ $teacher->email }}
                    </div>
                    <div class="teacher-email">
                        <i class="fas fa-phone mr-2"></i>{{ $teacher->phone }}
                    </div>
                </div>
                <div class="col-md-4 text-right">
                    <a href="{{ route('profile.teacher.show', $teacher->id) }}" class="btn btn-outline-primary">
                        <i class="fas fa-user mr-1"></i> View Profile
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card filter-card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label>Search Subject</label>
                    <input type="text" class="form-control" id="searchSubject" placeholder="Subject name...">
                </div>
                <div class="col-md-3">
                    <label>School Year</label>
                    <select class="form-control" id="schoolYearFilter">
                        <option value="">All School Years</option>
                        @foreach($schoolYearData as $data)
                            <option value="{{ $data['school_year_id'] }}" {{ $loop->first ? 'selected' : '' }}>
                                {{ $data['school_year_code'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary btn-block" id="clearFilters" title="Clear Filters">
                        <i class="fas fa-undo"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- School Year History -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-history mr-2"></i>Subject</h3>
        </div>
        <div class="card-body">
            <div id="schoolYearContainer">
                @if(count($schoolYearData) > 0)
                    @foreach($schoolYearData as $data)
                        @php
                            $isActive = $data['teacher_status'] === 'active';
                            $isCurrent = $data['school_year_status'] === 'active';
                        @endphp
                        
                        <div class="school-year-card" 
                             data-school-year-id="{{ $data['school_year_id'] }}"
                             data-teacher-status="{{ $data['teacher_status'] }}">
                            <div class="card-body">
                                <div class="school-year-header">
                                    <div>
                                        <h5 class="school-year-title">
                                            {{ $data['school_year_code'] }}
                                            @if($isCurrent)
                                                <span class="badge badge-primary ml-2">Current</span>
                                            @endif
                                        </h5>
                                        <div class="info-row">
                                            @if($data['activated_at'])
                                                <div class="info-item">
                                                    <i class="fas fa-clock"></i>
                                                    <span class="info-value">
                                                        {{ \Carbon\Carbon::parse($data['activated_at'])->format('M d, Y') }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div class="info-item">
                                                <i class="fas fa-book"></i>
                                                <span class="info-value">{{ $data['total_classes'] }} Subject(s)</span>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-users"></i>
                                                <span class="info-value">{{ $data['total_sections'] }} Section(s)</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        @if($isActive)
                                            <button class="btn btn-sm btn-outline-primary btn-toggle-status" 
                                                    data-teacher-id="{{ $teacher->id }}"
                                                    data-school-year-id="{{ $data['school_year_id'] }}"
                                                    data-action="deactivate">
                                                <i class="fas fa-pause mr-1"></i> Deactivate
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-primary btn-toggle-status" 
                                                    data-teacher-id="{{ $teacher->id }}"
                                                    data-school-year-id="{{ $data['school_year_id'] }}"
                                                    data-action="activate">
                                                <i class="fas fa-check mr-1"></i> Activate
                                            </button>
                                        @endif
                                    </div>
                                </div>

<!-- Subjects Accordion -->
@if($data['total_classes'] > 0 || $data['total_adviser_sections'] > 0)
    <div class="subject-accordion">
        <!-- Teaching Subjects -->
        @if($data['total_classes'] > 0)
            @foreach($data['classes'] as $classCode => $classGroup)
                @php
                    $firstClass = $classGroup->first();
                    $totalSections = $classGroup->sum('section_count');
                @endphp
                <div class="subject-item" data-subject-name="{{ strtolower($firstClass->class_name) }}" data-subject-type="teaching">
                    <div class="subject-header">
                        <div class="subject-name">
                            {{ $firstClass->class_name }}
                            <small class="text-muted">({{ $firstClass->class_code }})</small>
                        </div>
                        <div class="subject-meta">
                            <span class="badge badge-{{ $firstClass->class_category === 'CORE SUBJECT' ? 'primary' : ($firstClass->class_category === 'APPLIED SUBJECT' ? 'info' : 'secondary') }}">
                                {{ $firstClass->class_category }}
                            </span>
                            <span class="badge badge-white">
                                {{ $totalSections }} Section(s)
                            </span>
                            <i class="fas fa-chevron-right subject-expand-icon"></i>
                        </div>
                    </div>
                    <div class="subject-content">
                        @foreach($classGroup as $class)
                            @if($class->sections->count() > 0)
                                <h6 class="text-muted mb-2 mt-2">
                                    <i class="fas fa-calendar-alt mr-1"></i>{{ $class->semester_name }}
                                </h6>
                                <ul class="section-list">
                                    @foreach($class->sections as $section)
                                        <li class="section-list-item">
                                            <span class="section-code">{{ $section->section_name }}</span>
                                            <span class="section-details">
                                                {{ $section->strand_code }} - {{ $section->level_name }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif

        <!-- Class Adviser Assignments -->
        @if($data['total_adviser_sections'] > 0)
            <div class="subject-item" data-subject-name="class adviser" data-subject-type="adviser">
                <div class="subject-header">
                    <div class="subject-name">
                        Class Adviser
                    </div>
                    <div class="subject-meta">
                        <span class="badge badge-primary">
                            ADVISER
                        </span>
                        <span class="badge badge-white">
                            {{ $data['total_adviser_sections'] }} Section(s)
                        </span>
                        <i class="fas fa-chevron-right subject-expand-icon"></i>
                    </div>
                </div>
                <div class="subject-content">
                    @foreach($data['adviser_assignments'] as $semesterId => $advisers)
                        @php
                            $semester = $advisers->first();
                        @endphp
                        <h6 class="text-muted mb-2 mt-2">
                            <i class="fas fa-calendar-alt mr-1"></i>{{ $semester->semester_name }}
                        </h6>
                        <ul class="section-list">
                            @foreach($advisers as $adviser)
                                <li class="section-list-item">
                                    <span class="section-code">{{ $adviser->section_name }}</span>
                                    <span class="section-details">
                                        {{ $adviser->strand_code }} - {{ $adviser->level_name }}
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
    <p class="text-muted mb-0 mt-2">
        <i class="fas fa-inbox mr-1"></i> No subjects or adviser assignments
    </p>
@endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No history found for this teacher</p>
                    </div>
                @endif
            </div>

            <div id="noResultsMessage" class="alert alert-primary text-center" style="display: none;">
                <i class="fas fa-info-circle"></i> No records found matching your filters.
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
        const TOGGLE_STATUS_URL = "{{ route('admin.teachers.toggleStatus') }}";
        const TEACHER_ID = {{ $teacher->id }};
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection