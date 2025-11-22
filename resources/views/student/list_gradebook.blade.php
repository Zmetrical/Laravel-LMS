@extends('layouts.main-student')

@section('styles')
    @if(isset($styles))
        @foreach($styles as $style)
            <link rel="stylesheet" href="{{ asset('css/' . $style) }}">
        @endforeach
    @endif
    <style>
        .grade-card {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .grade-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .component-badge {
            font-size: 0.75rem;
            padding: 0.35rem 0.65rem;
        }
        .score-display {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .grade-progress {
            height: 8px;
        }
        @media (max-width: 768px) {
            .score-display {
                font-size: 1.2rem;
            }
            .grade-card {
                margin-bottom: 1rem;
            }
        }
    </style>
@endsection

@section('breadcrumb')
    <ol class="breadcrumb breadcrumb-custom">
        <li class="breadcrumb-item"><a href="{{ route('student.home') }}">Home</a></li>
        <li class="breadcrumb-item active">My Grades</li>
    </ol>
@endsection

@section('content')
<br>
<div class="container-fluid">
    <!-- Active Semester Info -->
    <div class="alert alert-dark alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <h5>
            <i class="fas fa-calendar-alt"></i> 
            {{ $activeSemesterDisplay ?? 'No Active Semester' }}
        </h5>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 col-12 mb-3">
            <div class="info-box ">
                <span class="info-box-icon"><i class="fas fa-book"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Classes</span>
                    <span class="info-box-number" id="totalClassesCount">-</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-3">
            <div class="info-box ">
                <span class="info-box-icon"><i class="fas fa-hourglass-half"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending Grades</span>
                    <span class="info-box-number" id="pendingCount">-</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-3">
            <div class="info-box">
                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">With Final Grades</span>
                    <span class="info-box-number" id="withFinalCount">-</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-12 mb-3">
            <div class="info-box ">
                <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Overall Average</span>
                    <span class="info-box-number" id="overallAverage">-</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Grades List -->
    <div id="gradesContainer">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading your grades...</p>
        </div>
    </div>
</div>

<!-- Grade Details Modal -->
<div class="modal fade" id="gradeDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">
                    <i class="fas fa-chart-bar"></i> Grade Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Class Info -->
                <div class="card card-outline card-primary mb-3">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 id="detailClassName" class="mb-1"></h5>
                            </div>
                            <div class="col-md-4 text-right">
                                <div id="detailFinalGrade"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Component Breakdown -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Written Works</h6>
                                <div class="score-display text-primary" id="detailWWScore">-</div>
                                <small class="text-muted" id="detailWWPerc"></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Performance Tasks</h6>
                                <div class="score-display text-primary" id="detailPTScore">-</div>
                                <small class="text-muted" id="detailPTPerc"></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Quarterly Assessment</h6>
                                <div class="score-display text-primary" id="detailQAScore">-</div>
                                <small class="text-muted" id="detailQAPerc"></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Scores Table -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-list"></i> Individual Scores
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Component</th>
                                        <th>Item</th>
                                        <th class="text-center">Score</th>
                                        <th class="text-center">Max Points</th>
                                        <th class="text-center">Percentage</th>
                                        <th class="text-center">Source</th>
                                    </tr>
                                </thead>
                                <tbody id="detailedScoresBody">
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-spinner fa-spin"></i> Loading...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
        const API_ROUTES = {
            getGrades: "{{ route('student.grades.list') }}",
            getDetails: "{{ route('student.grades.details', ['classId' => ':classId']) }}"
        };
    </script>
    
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection