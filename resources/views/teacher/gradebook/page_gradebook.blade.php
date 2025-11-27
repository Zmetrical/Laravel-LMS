@extends('layouts.main-teacher')

@section('styles')
    <link rel="stylesheet" href="{{ asset('plugins/toastr/toastr.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid-theme.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .jsgrid-header-row > .jsgrid-header-cell {
            background-color: #343a40;
            color: #fff;
            font-weight: 600;
            vertical-align: top;
            padding: 10px 5px;
        }
        .summary-header th {
            background-color: #343a40 !important;
            color: #fff !important;
            font-weight: 600;
            vertical-align: top;
            padding: 10px 5px;
        }
        .jsgrid-cell {
            padding: 8px 5px;
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

        .quarter-selector {
            max-width: 250px;
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('teacher.home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('teacher.list_class') }}">Classes</a></li>
        <li class="breadcrumb-item active">Gradebook</li>
    </ol>
@endsection

@section('content')
    <div class="card card-dark card-outline">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h3 class="mb-1">
                        <i class="fas fa-book"></i> <span id="className">{{ $class->class_name }}</span>
                    </h3>
                    <p class="text-muted mb-0">{{ $class->class_code }}</p>
                </div>
                <div class="col-md-6 text-right">
                    <div class="d-inline-block quarter-selector mr-2">
                        <select class="form-control form-control-sm" id="quarterSelector">
                            @foreach($quarters as $quarter)
                                <option value="{{ $quarter->id }}">{{ $quarter->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="btn btn-primary btn-sm" id="exportBtn">
                        <i class="fas fa-file-excel"></i> Export to Excel
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
                        <span class="text-info ml-2"><i class="fas fa-info-circle"></i> Click toggle icon on column header to enable/disable</span>
                    </div>
                    <div class="table-scroll-wrapper">
                        <div id="qaGrid"></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="summary" role="tabpanel">
                    <div class="table-responsive">
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
                                <tr>
                                    <td colspan="7" class="text-center">
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

    <!-- Enable Column Modal -->
    <div class="modal fade" id="enableColumnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white"><i class="fas fa-toggle-on"></i> Enable Column: <span id="enableColumnName"></span></h5>
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
                            <label>Link to Online Quiz </label>
                            <select class="form-control" id="enableQuizId">
                                <option value="">Manual Entry</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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

    <!-- Export Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white"><i class="fas fa-file-excel"></i> Export Gradebook</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form id="exportForm">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> This will export the currently selected quarter.
                        </div>
                        <p class="mb-0"><strong>Selected Quarter:</strong> <span id="exportQuarterName"></span></p>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid.min.js"></script>
    <script>
        const CLASS_ID = {{ $classId }};
        const MAX_WW_COLUMNS = 10;
        const MAX_PT_COLUMNS = 10;
        const MAX_QA_COLUMNS = 1;
        const QUARTERS = @json($quarters);
        const API_ROUTES = {
            getGradebook: "{{ route('teacher.gradebook.data', ['classId' => $classId]) }}",
            toggleColumn: "{{ route('teacher.gradebook.column.toggle', ['classId' => $classId, 'columnId' => '__COLUMN_ID__']) }}",
            updateColumn: "{{ route('teacher.gradebook.column.update', ['classId' => $classId, 'columnId' => '__COLUMN_ID__']) }}",
            batchUpdate: "{{ route('teacher.gradebook.scores.batch', ['classId' => $classId]) }}",
            getQuizzes: "{{ route('teacher.gradebook.quizzes', ['classId' => $classId]) }}",
            exportGradebook: "{{ route('teacher.gradebook.export', ['classId' => $classId]) }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection