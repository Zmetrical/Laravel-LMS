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
        
        /* Highlight changed cells */
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
        
        .edit-column-btn {
            cursor: pointer;
            font-size: 11px;
            opacity: 0.7;
            margin-top: 2px;
        }
        
        .edit-column-btn:hover {
            opacity: 1;
            color: #ffc107;
        }
        
        .stats-box {
            border-left: 4px solid;
        }
        
        .table-scroll-wrapper {
            overflow-x: auto;
            margin-bottom: 15px;
        }

        /* Sticky Save Button */
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

        /* Badge for online quiz columns */
        .badge-info {
            padding: 4px 8px;
            font-size: 12px;
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
                <div class="col-md-8">
                    <h3 class="mb-1">
                        <i class="fas fa-book"></i> <span id="className">{{ $class->class_name }}</span>
                    </h3>
                    <p class="text-muted mb-0">{{ $class->class_code }}</p>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-info btn-sm" id="importExcelBtn">
                        <i class="fas fa-upload"></i> Import from Excel
                    </button>
                    <input type="file" id="excelFileInput" accept=".xlsx,.xls" style="display: none;">
                </div>
            </div>
        </div>
    </div>

    <!-- Sticky Save Button -->
    <button class="btn btn-success" id="saveChangesBtn" style="display: none;">
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
                        <button class="btn btn-dark btn-sm add-column-btn" data-type="WW">
                            <i class="fas fa-plus"></i> Add Column
                        </button>
                        <span class="text-muted ml-2" id="wwColumnCount"></span>
                    </div>
                    <div class="table-scroll-wrapper">
                        <div id="wwGrid"></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pt" role="tabpanel">
                    <div class="mb-2">
                        <button class="btn btn-dark btn-sm add-column-btn" data-type="PT">
                            <i class="fas fa-plus"></i> Add Column
                        </button>
                        <span class="text-muted ml-2" id="ptColumnCount"></span>
                    </div>
                    <div class="table-scroll-wrapper">
                        <div id="ptGrid"></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="qa" role="tabpanel">
                    <div class="mb-2">
                        <button class="btn btn-dark btn-sm add-column-btn" data-type="QA">
                            <i class="fas fa-plus"></i> Add Column
                        </button>
                        <span class="text-muted ml-2" id="qaColumnCount"></span>
                    </div>
                    <div class="table-scroll-wrapper">
                        <div id="qaGrid"></div>
                    </div>
                </div>

                <div class="tab-pane fade" id="summary" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm">
                            <thead class="thead-dark">
                                <tr>
                                    <th>USN</th>
                                    <th>Student Name</th>
                                    <th class="text-center">Written Work<br><small id="wwPercLabel">({{ $class->ww_perc }}%)</small></th>
                                    <th class="text-center">Performance Task<br><small id="ptPercLabel">({{ $class->pt_perc }}%)</small></th>
                                    <th class="text-center">Quarterly Assessment<br><small id="qaPercLabel">({{ $class->qa_perce }}%)</small></th>
                                    <th class="text-center">Initial Grade</th>
                                    <th class="text-center bg-info">Quarterly Grade</th>
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

                    <div class="row mt-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-secondary stats-box">
                                <div class="inner">
                                    <h3 id="avgGrade">0.00</h3>
                                    <p>Class Average</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info stats-box">
                                <div class="inner">
                                    <h3 id="highestGrade">0.00</h3>
                                    <p>Highest Grade</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning stats-box">
                                <div class="inner">
                                    <h3 id="lowestGrade">0.00</h3>
                                    <p>Lowest Grade</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success stats-box">
                                <div class="inner">
                                    <h3 id="passingRate">0%</h3>
                                    <p>Passing Rate (≥75)</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Column Modal -->
    <div class="modal fade" id="editColumnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-dark">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Column</h5>
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
                            <small class="text-muted">Linking to a quiz will auto-sync scores</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-save"></i> Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Import Confirmation Modal -->
    <div class="modal fade" id="importConfirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Existing Data Found</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Some columns already contain data. What would you like to do?</p>
                    <div class="alert alert-info">
                        <strong>Replace:</strong> Existing scores will be deleted and replaced with imported data<br>
                        <strong>Keep:</strong> Only empty cells will be filled with imported data
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="keepDataBtn">
                        <i class="fas fa-layer-group"></i> Keep Existing
                    </button>
                    <button type="button" class="btn btn-danger" id="replaceDataBtn">
                        <i class="fas fa-sync"></i> Replace All
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('plugins/toastr/toastr.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid.min.js"></script>
    <script>
        const CLASS_ID = {{ $classId }};
        const MAX_COLUMNS = 10;
        const API_ROUTES = {
            getGradebook: "{{ route('teacher.gradebook.data', ['classId' => $classId]) }}",
            addColumn: "{{ route('teacher.gradebook.column.add', ['classId' => $classId]) }}",
            updateColumn: "{{ route('teacher.gradebook.column.update', ['columnId' => '__COLUMN_ID__']) }}",
            batchUpdate: "{{ route('teacher.gradebook.scores.batch', ['classId' => $classId]) }}",
            getQuizzes: "{{ route('teacher.gradebook.quizzes', ['classId' => $classId]) }}",
            importGradebook: "{{ route('teacher.gradebook.import', ['classId' => $classId]) }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection