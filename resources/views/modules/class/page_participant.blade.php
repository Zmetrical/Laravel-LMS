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
                                <th style="width: 10%;" class="text-center">Type</th>
                                <th style="width: 10%;" class="text-center">Gender</th>
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
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Showing all students enrolled in this class (both direct enrollment and section-based enrollment)
                </small>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-scripts')
<script>
$(document).ready(function() {
    const classId = {{ $class->id }};

    // Load participants on page load
    loadParticipants();

    function loadParticipants() {
        $.ajax({
            url: `/teacher/class/${classId}/participants/list`,
            method: 'GET',
            dataType: 'json',
            beforeSend: function() {
                $('#participantsTableBody').html(`
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Loading participants...</p>
                        </td>
                    </tr>
                `);
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    renderParticipantsTable(response.data);
                    $('#participantCount').html(`
                        <i class="fas fa-users"></i> ${response.total} Participant${response.total > 1 ? 's' : ''}
                    `);
                } else {
                    showEmptyState();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading participants:', error);
                showErrorState();
            }
        });
    }

    function renderParticipantsTable(participants) {
        let rows = '';
        
        participants.forEach(function(participant) {
            const genderBadge = participant.gender.toLowerCase() === 'male' 
                ? '<span class="badge badge-info">Male</span>' 
                : '<span class="badge badge-primary">Female</span>';
            
            const typeBadge = participant.student_type === 'regular'
                ? '<span class="badge badge-success">Regular</span>'
                : '<span class="badge badge-warning">Irregular</span>';

            const enrollmentIcon = participant.enrollment_type === 'direct'
                ? '<i class="fas fa-user-check text-success" title="Direct Enrollment"></i>'
                : '<i class="fas fa-users text-info" title="Section Enrollment"></i>';

            const sectionDisplay = participant.section_name !== 'No Section'
                ? `<strong>${participant.section_code}</strong><br><small class="text-muted">${participant.section_name}</small>`
                : '<span class="text-muted">No Section</span>';

            rows += `
                <tr>
                    <td class="text-center">${participant.row_number}</td>
                    <td>
                        ${enrollmentIcon}
                        <strong>${participant.student_number}</strong>
                    </td>
                    <td>
                        ${participant.full_name}
                    </td>
                    <td>
                        <small>${participant.email}</small>
                    </td>
                    <td>${sectionDisplay}</td>
                    <td class="text-center">${typeBadge}</td>
                    <td class="text-center">${genderBadge}</td>
                </tr>
            `;
        });

        $('#participantsTableBody').html(rows);
    }

    function showEmptyState() {
        $('#participantsTableBody').html(`
            <tr>
                <td colspan="7" class="text-center py-5">
                    <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Participants Found</h5>
                    <p class="text-muted">There are no students enrolled in this class yet.</p>
                </td>
            </tr>
        `);
        $('#participantCount').html('<i class="fas fa-users"></i> 0 Participants');
    }

    function showErrorState() {
        $('#participantsTableBody').html(`
            <tr>
                <td colspan="7" class="text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                    <h5 class="text-danger">Error Loading Participants</h5>
                    <p class="text-muted">Unable to load participant data. Please try refreshing the page.</p>
                    <button class="btn btn-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Refresh Page
                    </button>
                </td>
            </tr>
        `);
        $('#participantCount').html('<i class="fas fa-exclamation-circle"></i> Error');
    }
});
</script>
@endsection