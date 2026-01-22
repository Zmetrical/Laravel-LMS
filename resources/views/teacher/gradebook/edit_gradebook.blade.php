@extends('layouts.main-teacher')
{{-- edit gradebook --}}
@section('styles')
<link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/jsgrid/jsgrid.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/jsgrid/jsgrid-theme.min.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .jsgrid-header-row > .jsgrid-header-cell {
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

        .jsgrid-cell {
            padding: 8px 5px;
            text-align: center;
        }
        
        .jsgrid-cell input {
            text-align: center;
            border: 1px solid #dee2e6;
            background: #fff;
            width: 100%;
            padding: 4px;
            border-radius: 3px;
        }
        
        .jsgrid-cell input:focus {
            background: #fff3cd;
            outline: 2px solid #007bff;
            border-color: #007bff;
        }
        
        .changed-cell-value {
            background-color: #fff3cd;
            padding: 2px 6px;
            border-radius: 3px;
            display: inline-block;
            border: 2px solid #ffc107;
            font-weight: 600;
        }
        
        .online-column {
            background-color: #e3f2fd !important;
        }

        .disabled-column {
            background-color: #f5f5f5 !important;
            opacity: 0.6;
        }

        .component-header {
            background-color: #495057 !important;
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

        .online-badge {
            font-size: 10px;
            padding: 2px 5px;
            margin-left: 3px;
        }
        
        .edit-column-btn, .toggle-column-btn {
            cursor: pointer;
            font-size: 12px;
            opacity: 0.8;
            transition: all 0.2s;
        }
        
        .edit-column-btn:hover, .toggle-column-btn:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        .toggle-column-btn {
            font-size: 16px;
        }
        
        .table-scroll-wrapper {
            overflow-x: auto;
            margin-bottom: 15px;
            -webkit-overflow-scrolling: touch;
        }

        #saveChangesBtn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            animation: slideInUp 0.4s ease-out;
        }

        #saveChangesBtn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .badge-info {
            padding: 4px 8px;
            font-size: 12px;
        }

        .gender-separator .jsgrid-cell {
            background-color: #6c757d !important;
            color: #fff !important;
            font-weight: 700;
            text-align: left !important;
            padding: 8px 10px !important;
        }

        .student-info {
            text-align: left !important;
            position: sticky;
            left: 0;
            z-index: 5;
            background-color: #fff;
        }

        .student-info::after {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 1px;
            background-color: #dee2e6;
        }

        .total-cell {
            background-color: #e9ecef;
            font-weight: 600;
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
        <li class="breadcrumb-item active">Edit Gradebook</li>
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
                    <button class="btn btn-outline-secondary btn-sm" id="viewBtn">
                        Back to View
                    </button>
                </div>
            </div>
            
        </div>
    </div>
    <div class="card card-dark card-outline">
        <div class="card-body">
            <div class="row align-items-center">

            <div class="col-md-6 filter-controls">
                <div class="filter-group">
                    <label>Quarter:</label>
                    <div class="btn-group btn-group-sm btn-group-quarter" role="group">
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
                    <label><i class="fas fa-users"></i> Section:</label>
                    <select class="form-control form-control-sm" id="sectionFilter" style="width: 200px;" required>
                        <option value="">Select Section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

                <div class="col-md-6 text-right">
                    <button class="btn btn-primary btn-sm" id="importBtn">
                        <i class="fas fa-file-import"></i> Import Scores
                    </button>
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-primary" id="saveChangesBtn" style="display: none;">
        <i class="fas fa-save"></i> <span id="saveChangesText">Save Changes</span>
    </button>

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
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="custom-tabs-content">
                <div class="tab-pane fade show active" id="ww" role="tabpanel">
                    <div class="mb-2">
                        <span class="text-muted" id="wwColumnCount"></span>
                    </div>
                    <div class="table-scroll-wrapper">
                        <div id="wwGrid"></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pt" role="tabpanel">
                    <div class="mb-2">
                        <span class="text-muted" id="ptColumnCount"></span>
                    </div>
                    <div class="table-scroll-wrapper">
                        <div id="ptGrid"></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="qa" role="tabpanel">
                    <div class="mb-2">
                        <span class="text-muted" id="qaColumnCount"></span>
                    </div>
                    <div class="table-scroll-wrapper">
                        <div id="qaGrid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<!-- Enable Column Modal -->
<div class="modal fade" id="enableColumnModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-toggle-on"></i> Enable Column: <span id="enableColumnName"></span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="enableColumnForm">
                <input type="hidden" id="enableColumnId">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Maximum Points <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="enableMaxPoints" required min="1" step="1">
                    </div>

                    <div class="form-group">
                        <label>Grade Source <span class="text-danger">*</span></label>
                        <select class="form-control" id="enableGradeSource" required>
                            <option value="">Select Grade Source</option>
                            <option value="manual">Manual Entry (No automatic grades)</option>
                            <option value="online">Online Quiz Grades</option>
                            <option value="import">Import from File</option>
                        </select>
                        <small class="form-text text-muted">
                            Choose how grades will be populated for this column
                        </small>
                    </div>

                    <!-- Online Quiz Option -->
                    <div class="form-group" id="enableOnlineQuizGroup" style="display: none;">
                        <label>Select Online Quiz <span class="text-danger">*</span></label>
                        <select class="form-control" id="enableQuizId">
                            <option value="">Select Quiz</option>
                        </select>
                        <small class="form-text text-muted">
                            Quiz scores will be automatically imported and adjusted to match the max points
                        </small>
                    </div>

                    <!-- Import File Option -->
                    <div id="enableImportGroup" style="display: none;">

                        <div class="form-group">
                            <label>Excel File <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="enableImportFile" 
                                       accept=".xlsx,.xls">
                                <label class="custom-file-label" for="enableImportFile">Choose file...</label>
                            </div>
                            <small class="form-text text-muted">
                                Maximum file size: 5MB. Accepted formats: .xlsx, .xls
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check"></i> Enable Column
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Edit Column Modal -->
    <div class="modal fade" id="editColumnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark">
                    <h5 class="modal-title text-white"><i class="fas fa-edit"></i> Edit Column</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="editColumnForm">
                    <input type="hidden" id="editColumnId">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Column Name</label>
                            <input type="text" class="form-control" id="editColumnName" readonly>
                        </div>
                        <div class="form-group">
                            <label>Maximum Points <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="editMaxPoints" required min="1" step="1">
                        </div>
                        <div class="form-group">
                            <label>Link to Online Quiz (Optional)</label>
                            <select class="form-control" id="editQuizId">
                                <option value="">Manual Entry</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark">
                            <i class="fas fa-save"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">
                    <i class="fas fa-file-import"></i> Import Scores
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="importForm" enctype="multipart/form-data">
                <div class="modal-body">

                    <div class="form-group">
                        <label>Excel File <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="importFile" 
                                   accept=".xlsx,.xls" required>
                            <label class="custom-file-label" for="importFile">Choose file...</label>
                        </div>
                        <small class="form-text text-muted">
                            Maximum file size: 5MB. Accepted formats: .xlsx, .xls
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Component Type <span class="text-danger">*</span></label>
                        <select class="form-control" id="importComponentType" required>
                            <option value="">Select Component</option>
                            <option value="WW">Written Work (WW)</option>
                            <option value="PT">Performance Task (PT)</option>
                            <option value="QA">Quarterly Assessment (QA)</option>
                        </select>
                    </div>

                    <div class="form-group" id="importColumnGroup" style="display: none;">
                        <label>Column Number <span class="text-danger">*</span></label>
                        <select class="form-control" id="importColumnNumber" required>
                            <option value="">Select Column</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection

@section('scripts')
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ asset('plugins/jsgrid/jsgrid.min.js') }}"></script>
    <script>
        const CLASS_ID = {{ $classId }};
        const MAX_WW_COLUMNS = 10;
        const MAX_PT_COLUMNS = 10;
        const MAX_QA_COLUMNS = 1;
        const QUARTERS = @json($quarters);
        const API_ROUTES = {
            viewGradebook: "{{ route('teacher.gradebook.view', ['classId' => $classId]) }}",
            getGradebook: "{{ route('teacher.gradebook.data', ['classId' => $classId]) }}",
            toggleColumn: "{{ route('teacher.gradebook.column.toggle', ['classId' => $classId, 'columnId' => '__COLUMN_ID__']) }}",
            updateColumn: "{{ route('teacher.gradebook.column.update', ['classId' => $classId, 'columnId' => '__COLUMN_ID__']) }}",
            batchUpdate: "{{ route('teacher.gradebook.scores.batch', ['classId' => $classId]) }}",
            getQuizzes: "{{ route('teacher.gradebook.quizzes', ['classId' => $classId]) }}",
            import: "{{ route('teacher.gradebook.import', ['classId' => $classId]) }}", // Add this line
                importColumn: "{{ route('teacher.gradebook.column.import', ['classId' => $classId, 'columnId' => '__COLUMN_ID__']) }}" // Add this


        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection