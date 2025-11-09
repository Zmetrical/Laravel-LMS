@extends('modules.class.main', [
    'activeTab' => 'participants', 
    'userType' => $userType, 
    'class' => $class])

@section('tab-content')
<div class="callout callout-warning">
    <h5><i class="fas fa-users"></i> Course Participants</h5>
    <p>Manage students and instructors enrolled in this course.</p>
    <p class="mb-0"><em>This section is under development.</em></p>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users"></i> Enrolled Students
                </h3>
                <div class="card-tools">
                    <button class="btn btn-primary btn-sm">
                        <i class="fas fa-user-plus"></i> Add Student
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                    <h4>Participant Management</h4>
                    <p class="text-muted">Manage enrolled students and track their participation.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
    console.log('Participants page loaded');
</script>
@endsection