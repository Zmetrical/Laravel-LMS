@extends('layouts.main-teacher')
{{-- view gradebook --}}
@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .table-gradebook {
            font-size: 13px;
            margin-bottom: 0;
        }
        
        .table-gradebook thead th {
            background-color: #343a40;
            color: #fff;
            font-weight: 600;
            vertical-align: top;
            padding: 10px 5px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table-gradebook tbody td {
            padding: 8px 5px;
            text-align: center;
        }
        
        .table-gradebook .student-info {
            text-align: left;
            position: sticky;
            left: 0;
            z-index: 5;
        }

        .table-gradebook .student-info::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 1px;
            background-color: #dee2e6;
        }
        
        .table-gradebook .score-cell {
            text-align: center;
        }
        
        .table-gradebook .total-cell {
            background-color: #e9ecef;
            font-weight: 600;
        }
        
        .table-gradebook .grade-cell {
            background-color: #6c757d;
            color: #fff;
            font-weight: 700;
            font-size: 14px;
        }
        
        .component-header {
            background-color: #495057 !important;
        }
        
        .online-badge {
            font-size: 10px;
            padding: 2px 5px;
            margin-left: 3px;
        }
        
        .column-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            line-height: 1.3;
        }
        
        .column-title {
            font-size: 13px;
            font-weight: 700;
        }
        
        .column-points {
            font-size: 11px;
            opacity: 0.9;
            font-weight: normal;
        }
        
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .summary-table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .summary-header th {
            background-color: #343a40 !important;
            color: #fff !important;
            font-weight: 600;
            vertical-align: top;
            padding: 10px 5px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .summary-header th:first-child,
        .summary-header th:nth-child(2) {
            position: sticky;
            left: 0;
            z-index: 11;
        }

        .summary-header th:nth-child(2) {
            left: 120px;
        }

        .disabled-column {
            background-color: #f5f5f5 !important;
            opacity: 0.6;
        }

        .gender-separator {
            background-color: #6c757d !important;
            color: #fff !important;
            font-weight: 700;
            text-align: left !important;
            padding: 8px 10px !important;
        }

        .gender-separator td {
            text-align: left !important;
            position: sticky;
            left: 0;
            z-index: 8;
        }

        .loading-row td {
            text-align: center !important;
            padding: 40px !important;
        }

        .btn-group-quarter .btn {
            border-radius: 0;
        }

        .btn-group-quarter .btn:first-child {
            border-top-left-radius: 0.25rem;
            border-bottom-left-radius: 0.25rem;
        }

        .btn-group-quarter .btn:last-child {
            border-top-right-radius: 0.25rem;
            border-bottom-right-radius: 0.25rem;
        }

        .filter-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-group label {
            margin: 0;
            font-weight: 600;
            white-space: nowrap;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('teacher.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('teacher.list_class') }}">Classes</a></li>
        <li class="breadcrumb-item active">View Gradebook</li>
    </ol>
@endsection

@section('content')
    <div class="card card-dark card-outline">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="mb-1">
                        <i class="fas fa-book"></i> {{ $class->class_name }}
                    </h3>
                </div>
                <div class="col-md-6 text-right">
                    <button class="btn btn-primary btn-sm" id="exportBtn">
                        <i class="fas fa-file-excel"></i> Export to Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-dark card-outline">
        <div class="card-body">
            <div class="filter-controls">
                <div class="filter-group">
                    <label>Quarter:</label>
                    <div class="btn-group btn-group-sm btn-group-quarter" role="group" id="quarterBtnGroup">
                        @foreach($quarters as $quarter)
                            <button type="button" 
                                    class="btn btn-outline-secondary quarter-btn" 
                                    data-quarter="{{ $quarter->id }}">
                                {{ $quarter->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="filter-group">
                    <label>Section:</label>
                    <select class="form-control form-control-sm" id="sectionFilter" style="width: 200px;">
                        <option value="">All Sections</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-dark card-outline card-tabs">
        <div class="card-header p-0 pt-1 border-bottom-0">
            <ul class="nav nav-tabs" id="custom-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="ww-tab" data-toggle="pill" href="#ww" role="tab">
                        <i class="fas fa-pen"></i> Written Work
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="pt-tab" data-toggle="pill" href="#pt" role="tab">
                        <i class="fas fa-tasks"></i> Performance Tasks
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="qa-tab" data-toggle="pill" href="#qa" role="tab">
                        <i class="fas fa-clipboard-check"></i> Quarterly Assessment
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="summary-tab" data-toggle="pill" href="#summary" role="tab">
                        <i class="fas fa-chart-bar"></i> Summary
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="custom-tabs-content">
                <div class="tab-pane fade show active" id="ww" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm table-gradebook" id="wwTable">
                            <thead>
                                <tr id="wwHeaderRow"></tr>
                            </thead>
                            <tbody id="wwBody">
                                <tr class="loading-row">
                                    <td colspan="100">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="pt" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm table-gradebook" id="ptTable">
                            <thead>
                                <tr id="ptHeaderRow"></tr>
                            </thead>
                            <tbody id="ptBody">
                                <tr class="loading-row">
                                    <td colspan="100">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="qa" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm table-gradebook" id="qaTable">
                            <thead>
                                <tr id="qaHeaderRow"></tr>
                            </thead>
                            <tbody id="qaBody">
                                <tr class="loading-row">
                                    <td colspan="100">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="summary" role="tabpanel">
                    <div class="summary-table-wrapper">
                        <table class="table table-bordered table-hover table-sm">
                            <thead class="summary-header">
                                <tr>
                                    <th class="pb-4">USN</th>
                                    <th class="pb-4">Student Name</th>
                                    <th class="text-center">Written Work<br><small id="wwPercLabel">({{ $class->ww_perc }}%)</small></th>
                                    <th class="text-center">Performance Task<br><small id="ptPercLabel">({{ $class->pt_perc }}%)</small></th>
                                    <th class="text-center">Quarterly Assessment<br><small id="qaPercLabel">({{ $class->qa_perce }}%)</small></th>
                                    <th class="text-center pb-4">Initial Grade</th>
                                    <th class="text-center pb-4">Quarterly Grade</th>
                                </tr>
                            </thead>
                            <tbody id="summaryTableBody">
                                <tr class="loading-row">
                                    <td colspan="7">
                                        <i class="fas fa-spinner fa-spin"></i> Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-file-excel"></i> Export Gradebook
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="exportForm">
                    <div class="modal-body">
                        <p><strong>Class:</strong> {{ $class->class_name }}</p>
                        <p class="mb-0"><strong>Quarter:</strong> <span id="exportQuarterName"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download"></i> Download Excel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
    <script>
        const CLASS_ID = {{ $classId }};
        const QUARTERS = @json($quarters);
        const CLASS_INFO = @json($class);
        const API_ROUTES = {
            getGradebook: "{{ route('teacher.gradebook.data', ['classId' => $classId]) }}",
            exportGradebook: "{{ route('teacher.gradebook.export', ['classId' => $classId]) }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection