@extends('layouts.main')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
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

        /* Report Card Preview Styles */
        .report-card-preview {
            background: white;
            border: 2px solid #007bff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .report-card-preview:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .report-card-header {
            border-bottom: 3px solid #007bff;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .report-card-logo {
            width: 60px;
            height: 60px;
            object-fit: contain;
        }
        .report-card-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: #007bff;
            margin: 0;
        }
        .report-card-subtitle {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 0;
        }
        .student-info-row {
            border-bottom: 1px solid #dee2e6;
            padding: 8px 0;
        }
        .student-info-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.85rem;
        }
        .student-info-value {
            color: #212529;
            font-size: 0.85rem;
        }
        .subjects-preview {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .subjects-preview-table {
            font-size: 0.8rem;
            margin-bottom: 0;
        }
        .subjects-preview-table th {
            background: #e9ecef;
            padding: 5px 8px;
            font-weight: 600;
        }
        .subjects-preview-table td {
            padding: 4px 8px;
        }
        .view-full-card-btn {
            width: 100%;
            margin-top: 10px;
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

    <!-- Grade Cards Grid -->
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-id-card mr-2"></i>Student Grade Cards</h3>
            <div class="card-tools">
                <span class="badge badge-primary" id="cardsCount">{{ count($gradeCards) }} Records</span>
            </div>
        </div>
        <div class="card-body">
            <div id="gradeCardsContainer" class="row">
                @foreach ($gradeCards as $card)
                    <div class="col-md-6 col-lg-4 grade-card-item" 
                         data-student-number="{{ $card->student_number }}"
                         data-student-name="{{ strtolower($card->last_name . ' ' . $card->first_name) }}"
                         data-section-code="{{ $card->section_code }}"
                         data-semester-id="{{ $card->semester_id }}">
                        <div class="report-card-preview">
                            <!-- Header -->
                            <div class="report-card-header">
                                <div class="d-flex align-items-center">
                                    <img src="{{ asset('img/logo/trinity_logo.png') }}" alt="Logo" class="report-card-logo mr-3">
                                    <div class="flex-grow-1">
                                        <p class="report-card-title">COPY OF GRADES</p>
                                        <p class="report-card-subtitle">{{ $card->semester_display }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Student Info -->
                            <div class="student-info">
                                <div class="row student-info-row">
                                    <div class="col-4 student-info-label">NAME:</div>
                                    <div class="col-8 student-info-value">{{ strtoupper($card->last_name) }}, {{ strtoupper($card->first_name) }}</div>
                                </div>
                                <div class="row student-info-row">
                                    <div class="col-4 student-info-label">STUDENT NO:</div>
                                    <div class="col-8 student-info-value">{{ $card->student_number }}</div>
                                </div>
                                <div class="row student-info-row">
                                    <div class="col-4 student-info-label">SECTION:</div>
                                    <div class="col-8 student-info-value">
                                        {{ $card->section_name ?? 'N/A' }}
                                        @if($card->section_name)
                                            <br><small class="text-muted">{{ $card->strand_code }} - {{ $card->level_name }}</small>
                                        @endif
                                    </div>
                                </div>
                                <div class="row student-info-row">
                                    <div class="col-4 student-info-label">TYPE:</div>
                                    <div class="col-8 student-info-value">
                                        <span class="badge badge-{{ $card->student_type === 'regular' ? 'primary' : 'secondary' }}">
                                            {{ strtoupper($card->student_type) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Subjects Preview -->
                            <div class="subjects-preview">
                                <small class="text-muted d-block mb-2"><strong>SUBJECTS ENROLLED:</strong></small>
                                <div class="d-flex justify-content-around text-center">
                                    <div>
                                        <div class="text-muted" style="font-size: 0.7rem;">TOTAL</div>
                                        <div class="font-weight-bold">{{ $card->total_subjects }}</div>
                                    </div>
                                </div>
                            </div>

                            <!-- View Button -->
                            <a href="{{ route('admin.grades.card.view.page', ['student_number' => $card->student_number, 'semester_id' => $card->semester_id]) }}" 
                               class="btn btn-primary view-full-card-btn">
                                <i class="fas fa-file-alt"></i> View Full Report Card
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div id="noResultsMessage" class="alert alert-info text-center" style="display: none;">
                <i class="fas fa-info-circle"></i> No grade cards found matching your filters.
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
    <script>
        const API_ROUTES = {
            getGradeCard: "{{ route('admin.grades.card.view') }}"
        };
    </script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection