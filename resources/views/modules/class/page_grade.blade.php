@extends('modules.class.main', [
    'activeTab' => 'grades', 
    'userType' => $userType, 
    'class' => $class])

@section('tab-content')
<div class="callout callout-success">
    <h5><i class="fas fa-chart-line"></i> Student Grades</h5>
    <p>Track and manage student performance and grades.</p>
    <p class="mb-0"><em>This section is under development.</em></p>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar"></i> Grade Overview
                </h3>
            </div>
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-chart-line fa-4x text-muted mb-3"></i>
                    <h4>Grade Management</h4>
                    <p class="text-muted">
                        @if($userType === 'teacher')
                            View and manage student grades here.
                        @else
                            View your grades and performance here.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
    console.log('Grades page loaded');
</script>
@endsection