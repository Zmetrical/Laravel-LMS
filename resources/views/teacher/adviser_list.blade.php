@extends('layouts.main-teacher')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
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
        .select2-container .select2-selection--single {
            height: calc(2.25rem + 2px) !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
            line-height: calc(2.25rem + 2px) !important;
        }
        .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 2px) !important;
        }

        .grade-card-list {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 10px;
            transition: all 0.2s ease;
        }
        .grade-card-list:hover {
            border-color: #007bff;
            box-shadow: 0 1px 4px rgba(0,123,255,0.1);
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
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('teacher.home') }}">Home</a></li>
        <li class="breadcrumb-item active">Student Grade Cards</li>
    </ol>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Info Alert -->
    <div class="alert alert-primary">
        <i class="fas fa-info-circle"></i> 
        <strong>Current Semester:</strong> 
        @if($activeSemester)
            {{ $activeSemester->display_name }}
        @else
            No Active Semester
        @endif
    </div>

    @if(isset($message))
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> {{ $message }}
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card filter-card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label>Search Student</label>
                    <input type="text" class="form-control" id="searchStudent" placeholder="Number or Name...">
                </div>
                <div class="col-md-4">
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
                <span class="badge badge-primary" id="cardsCount">{{ count($gradeCards) }} Records</span>
            </div>
        </div>
        <div class="card-body">
            <div id="gradeCardsContainer">
                @foreach ($gradeCards as $card)
                    <div class="grade-card-list grade-card-item" 
                         data-student-number="{{ $card->student_number }}"
                         data-student-name="{{ strtolower($card->last_name . ' ' . $card->first_name) }}"
                         data-section-code="{{ $card->section_code }}">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-9">
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="student-id-badge">{{ $card->student_number }}</span>
                                        <h5 class="student-name-display">{{ strtoupper($card->last_name) }}, {{ strtoupper($card->first_name) }}</h5>
                                    </div>
                                    
                                    <div class="info-inline">
                                        <div class="info-item-inline">
                                            <i class="fas fa-users"></i>
                                            <span class="info-value-inline">
                                                {{ $card->section_name ?? 'N/A' }}
                                                @if($card->section_name)
                                                    <small class="text-muted">({{ $card->strand_code }} - {{ $card->level_name }})</small>
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <div class="subjects-count-compact">
                                            <span class="semester-label">{{ $card->semester_display }}</span>
                                        </div>
                                        <a href="{{ route('teacher.grades.card.view', ['student_number' => $card->student_number, 'semester_id' => $card->semester_id]) }}" 
                                           class="btn btn-primary view-card-btn"
                                           target="_blank">
                                            <i class="fas fa-file-alt"></i> View Card
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div id="noResultsMessage" class="alert alert-primary text-center" style="display: none;">
                <i class="fas fa-info-circle"></i> No grade cards found matching your filters.
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection