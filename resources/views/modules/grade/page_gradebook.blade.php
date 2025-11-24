@extends('modules.class.main', [
    'activeTab' => 'grades', 
    'userType' => $userType, 
    'class' => $class])

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
            <div class="card-body p-0 m-0">
                <div class="table-responsive" id="gradeTableContainer" style="display: none;">
                    <table class="table table-bordered table-hover" id="gradeTable">
                        <thead class="">
                            <tr>
                                <th style="position: sticky; left: 0; z-index: 10;">Student Name</th>
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
<script>
    // API Routes Configuration
    const API_ROUTES = {
        getGrades: "{{ route('teacher.class.grades.list', ['classId' => $class->id]) }}",
        classId: {{ $class->id }}
    };
</script>
    @if(isset($scripts))
        @foreach($scripts as $script)
            <script src="{{ asset('js/' . $script) }}"></script>
        @endforeach
    @endif
@endsection