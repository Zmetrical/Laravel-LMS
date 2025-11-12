@extends('modules.class.main', [
    'activeTab' => 'grades', 
    'userType' => $userType, 
    'class' => $class])

@section('tab-content')

<div class="row">
    <div class="col-12">
        <!-- Performance Overview -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-pie"></i> Performance Overview
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="progress-group">
                            <span class="progress-text">Overall Progress</span>
                            <span class="float-right"><b id="progressScored">0</b>/<span id="progressTotal">0</span></span>
                            <div class="progress">
                                <div class="progress-bar bg-primary" id="overallProgress" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grades Table -->
        <div class="card card-info card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> My Grades
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-info" id="refreshGrades">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" id="gradeTableContainer" style="display: none;">
                    <table class="table table-bordered table-hover" id="gradeTable">
                        <thead class="bg-info">
                            <tr>
                                <th>Lesson</th>
                                <th>Quiz</th>
                                <th class="text-center">Score</th>
                                <th class="text-center">Percentage</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Attempts</th>
                                <th class="text-center">Date Submitted</th>
                            </tr>
                        </thead>
                        <tbody id="gradeTableBody">
                            <!-- Rows will be added dynamically -->
                        </tbody>
                    </table>
                </div>

                <div id="loadingState" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                    <h5>Loading your grades...</h5>
                </div>

                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h4>No Grades Yet</h4>
                    <p class="text-muted">Your grades will appear here once you complete quizzes.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
    // API Routes Configuration
    const API_ROUTES = {
        getGrades: "{{ route('student.class.grades.student', ['classId' => $class->id, 'studentNumber' => $studentNumber]) }}",
        classId: {{ $class->id }},
        studentNumber: "{{ $studentNumber }}"
    };
</script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection