@extends('modules.class.main', [
    'activeTab' => 'participants', 
    'userType' => $userType, 
    'class' => $class])

@section('tab-content')
<div class="row">
    <div class="col-12">
        <!-- Participants List Card -->
        <div class="card">
            <div class="card-header ">
                <h3 class="card-title">
                    <i class="fas fa-users"></i> Enrolled Participants
                </h3>
                <div class="card-tools">
                    <span class="badge badge-light" id="participantCount">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </span>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="card-body pb-2">
                <div class="row">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control" id="participantSearchFilter"
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
                        <button class="btn btn-secondary btn-sm btn-block" id="resetFiltersBtn">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead>
                            <tr>
                                <th style="width: 25%;">Student Number</th>
                                <th style="width: 30%;">Student Information</th>
                                <th style="width: 20%;">Section</th>
                                <th style="width: 10%;" class="text-center">Type</th>
                            </tr>
                        </thead>
                        <tbody id="participantsTableBody">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                                    <p class="mt-2">Loading participants...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <small class="text-muted" id="filterResultText"></small>
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