@extends('modules.class.main', [
    'activeTab' => 'participants', 
    'userType' => $userType, 
    'class' => $class])

@section('tab-content')
<div class="row">
    <div class="col-12">
        <!-- Participants List Card -->
        <div class="card">
            <div class="card-header bg-primary">
                <h3 class="card-title">
                    <i class="fas fa-users"></i> Enrolled Participants
                </h3>
                <div class="card-tools">
                    <span class="badge badge-light" id="participantCount">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th style="width: 5%;" class="text-center">#</th>
                                <th style="width: 15%;">Student Number</th>
                                <th style="width: 25%;">Name</th>
                                <th style="width: 20%;">Email</th>
                                <th style="width: 15%;">Section</th>
                                <th style="width: 10%;" class="text-center">Gender</th>
                                <th style="width: 10%;" class="text-center">Type</th>
                            </tr>
                        </thead>
                        <tbody id="participantsTableBody">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p class="mt-2">Loading participants...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
    // API Routes Configuration
    const API_ROUTES = {
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