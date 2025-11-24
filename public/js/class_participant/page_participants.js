// Teacher Participants JS
$(document).ready(function() {
    const classId = API_ROUTES.classId;

    // Load participants on page load
    loadParticipants();

    function loadParticipants() {
        $.ajax({
            url: API_ROUTES.getParticipants,
            method: 'GET',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
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
                console.error('Status:', xhr.status);
                console.error('Response:', xhr.responseText);
                
                
                showErrorState();
            }
        });
    }

    function renderParticipantsTable(participants) {
        let rows = '';
        
        participants.forEach(function(participant) {
            const genderBadge = participant.gender.toLowerCase() === 'male' 
                ? '<span class="">Male</span>'  
                : '<span class="">Female</span>';
            
            const typeBadge = participant.student_type === 'regular'
                ? '<span class="badge badge-primary">Regular</span>'
                : '<span class="badge badge-secondary">Irregular</span>';

            const sectionDisplay = participant.section_name !== 'No Section'
                ? `${participant.section_name}`
                : '<span class="text-muted">No Section</span>';

            rows += `
                <tr>
                    <td class="text-center">${participant.row_number}</td>
                    <td>
                        <strong>${participant.student_number}</strong>
                    </td>
                    <td>
                        ${participant.full_name}
                    </td>
                    <td>
                        <small>${participant.email}</small>
                    </td>
                    <td>${sectionDisplay}</td>
                    <td class="text-center">${genderBadge}</td>

                    <td class="text-center">${typeBadge}</td>
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