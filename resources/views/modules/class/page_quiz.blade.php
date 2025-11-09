@extends('modules.class.main', ['activeTab' => 'lessons', 'userType' => $userType, 'class' => $class])

@section('tab-content')
<div class="callout callout-info">
    <h5><i class="fas fa-info-circle"></i> All Quizzes</h5>
    <p>View and manage all quizzes across all lessons here.</p>
    <p class="mb-0"><em>This section is under development.</em></p>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clipboard-list"></i> Quiz List
                </h3>
            </div>
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-list fa-4x text-muted mb-3"></i>
                    <h4>Quiz Management</h4>
                    <p class="text-muted">Quiz management functionality will be implemented here.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
    console.log('Quizzes page loaded');
</script>
@endsection