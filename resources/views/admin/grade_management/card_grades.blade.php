@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .filter-card { 
            background: #f8f9fa; 
            border: 1px solid #e9ecef; 
        }
        .filter-card .form-control { 
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
        .select2-container .select2-selection--single {
            height: calc(2.25rem + 2px) !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: calc(2.25rem + 2px) !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 2px) !important;
        }

        /* Compact Card List Styles */
        .grade-card-list {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 10px;
            transition: border-color 0.15s ease;
        }
        .grade-card-list:hover {
            border-color: #2a347e;
        }
        .grade-card-list .card-body {
            padding: 0.75rem 1rem;
        }
        .student-id-badge {
            background: #2a347e;
            color: white;
            padding: 0.25rem 0.6rem;
            border-radius: 3px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
            margin-right: 0.5rem;
        }
        .student-name-display {
            font-size: 1rem;
            font-weight: 600;
            color: #212529;
            margin: 0;
            display: inline-block;
        }
        .info-inline {
            display: flex;
            gap: 1.5rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }
        .info-item-inline {
            display: flex;
            align-items: center;
            font-size: 0.85rem;
        }
        .info-item-inline i {
            color: #6c757d;
            margin-right: 0.4rem;
            width: 14px;
            text-align: center;
        }
        .info-value-inline {
            color: #212529;
            font-weight: 500;
        }
        .subjects-count-compact {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 0.4rem 0.7rem;
            border-radius: 4px;
            text-align: center;
            margin-right: 0.75rem;
            min-width: 120px;
        }
        .subjects-count-compact .semester-label {
            font-size: 0.7rem;
            color: #6c757d;
            line-height: 1.2;
            display: block;
        }
        .view-card-btn {
            white-space: nowrap;
            padding: 0.4rem 0.9rem;
            font-size: 0.875rem;
        }

        /* Skeleton loader */
        .skeleton-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 10px;
            padding: 0.75rem 1rem;
        }
        .skeleton-line {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 1.2s infinite;
            border-radius: 3px;
            height: 14px;
            margin-bottom: 8px;
        }
        .skeleton-line.short { width: 35%; }
        .skeleton-line.medium { width: 60%; }
        .skeleton-line.long { width: 80%; }
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* Pagination info */
        .pagination-info {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('admin.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Grade Cards</li>
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
                    <label>Search Student</label>
                    <input type="text" class="form-control" id="searchStudent" placeholder="Number or Name...">
                </div>
                <div class="col-md-3">
                    <label>Semester</label>
                    <select class="form-control" id="semesterFilter">
                        <option value="">All Semesters</option>
                        @foreach($semesters as $sem)
                            <option value="{{ $sem->id }}" 
                                {{ isset($activeSemester) && $activeSemester->semester_id == $sem->id ? 'selected' : '' }}>
                                {{ $sem->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label>Section</label>
                    <select class="form-control" id="sectionFilter" style="width: 100%;">
                        <option value="">All Sections</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->section_code }}">{{ $section->section_code }} - {{ $section->section_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-secondary btn-block" id="clearFilters" title="Clear Filters">
                        <i class="fas fa-undo"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Grade Cards List -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-id-card mr-2"></i>Student Grade Cards</h3>
            <div class="card-tools">
                <span class="badge badge-primary" id="cardsCount">Loading...</span>
            </div>
        </div>
        <div class="card-body">

            <!-- Cards Container -->
            <div id="gradeCardsContainer"></div>

            <!-- No Results -->
            <div id="noResultsMessage" class="alert alert-primary text-center" style="display: none;">
                <i class="fas fa-info-circle"></i> No grade cards found matching your filters.
            </div>

            <!-- Pagination Row -->
            <div class="d-flex justify-content-between align-items-center mt-3" id="paginationWrapper" style="display: none !important;">
                <div class="pagination-info" id="paginationInfo"></div>
                <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        const API_ROUTES = {
            getGradeCard:   "{{ route('admin.grades.card.view') }}",
            getCardsAjax:   "{{ route('admin.grades.cards.list') }}",
        };
        const CARD_VIEW_BASE = "{{ url('admin/grades/card') }}";
        @if(isset($activeSemester))
            const DEFAULT_SEMESTER = "{{ $activeSemester->semester_id }}";
        @else
        const DEFAULT_SEMESTER = "";
        @endif
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection