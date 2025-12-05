@extends('modules.class.main', [
    'activeTab' => 'grades', 
    'userType' => $userType, 
    'class' => $class])
    
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">

@section('tab-content')
<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-table"></i> Grade Book
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-sm btn-primary" id="refreshGrades">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card-body pb-2">
                <div class="row">
                    <div class="col-md-3">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control" id="gradeSearchFilter"
                                placeholder="Search by name or student number...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control form-control-sm" id="sectionFilter">
                            <option value="">All Sections</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control form-control-sm" id="genderFilter">
                            <option value="">All Genders</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-control form-control-sm" id="typeFilter">
                            <option value="">All Types</option>
                            <option value="regular">Regular</option>
                            <option value="irregular">Irregular</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary btn-sm btn-block" id="resetGradeFiltersBtn">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>

            </div>

            <div class="card-body p-0 m-0">
                <div class="table-responsive" id="gradeTableContainer" style="display: none;">
                    <table class="table table-bordered table-hover mb-0" id="gradeTable">
                        <thead>
                            <tr>
                                <th style="position: sticky; left: 0; z-index: 10; background-color: white;" width="50">Student Number</th>
                                <th style="position: sticky; left: 50px; z-index: 10; background-color: white;">Student Name</th>
                                <!-- Quiz columns will be added dynamically -->
                            </tr>
                        </thead>
                        <tbody id="gradeTableBody">
                            <!-- Rows will be added dynamically -->
                        </tbody>
                    </table>
                </div>

                <div id="loadingState" class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                    <h5>Loading grades...</h5>
                </div>

                <div id="emptyState" class="text-center py-5" style="display: none;">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h4>No Grades Available</h4>
                    <p class="text-muted">Grades will appear here once students complete quizzes.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<script>

    // API Routes Configuration
    const API_ROUTES = {
        getGrades: "{{ route('teacher.class.grades.list', ['classId' => $class->id]) }}",
        getParticipants: "{{ route('teacher.class.participants.list', ['classId' => $class->id]) }}",
        classId: {{ $class->id }}
    };
</script>
@if(isset($scripts))
    @foreach($scripts as $script)
        <script src="{{ asset('js/' . $script) }}"></script>
    @endforeach
@endif
@endsection