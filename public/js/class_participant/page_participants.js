console.log("page participants");

// Teacher Participants JS
$(document).ready(function() {
    const classId = API_ROUTES.classId;
    let allParticipants = [];
    let uniqueSections = new Set();

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
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-spinner fa-spin fa-2x"></i>
                            <p class="mt-2">Loading participants...</p>
                        </td>
                    </tr>
                `);
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    allParticipants = response.data;
                    populateSectionFilter();
                    renderParticipantsTable(allParticipants);
                    updateParticipantCount(allParticipants.length, response.total);
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

    function populateSectionFilter() {
        uniqueSections.clear();
        allParticipants.forEach(function(participant) {
            if (participant.section_name && participant.section_name !== 'No Section') {
                uniqueSections.add(participant.section_name);
            }
        });

        const $sectionFilter = $('#sectionFilter');
        $sectionFilter.find('option:not(:first)').remove();
        
        Array.from(uniqueSections).sort().forEach(function(section) {
            $sectionFilter.append(`<option value="${section}">${section}</option>`);
        });
    }

    function renderParticipantsTable(participants) {
        let rows = '';
        
        participants.forEach(function(participant, index) {
            const genderBadge = participant.gender.toLowerCase() === 'male' 
                ? '<span class="badge badge-primary">Male</span>'  
                : '<span class="badge badge-secondary">Female</span>';
            
            const typeBadge = participant.student_type === 'regular'
                ? '<span class="badge badge-primary">Regular</span>'
                : '<span class="badge badge-secondary">Irregular</span>';

            const sectionDisplay = participant.section_name !== 'No Section'
                ? `${participant.section_name}`
                : '<span class="text-muted">No Section</span>';

            rows += `
                <tr>
                    <td class="text-center"><strong>${index + 1}</strong></td>
                    <td>
                        <strong>${participant.full_name}</strong>
                        <br>
                        <small class="text-muted">${participant.student_number}</small>
                    </td>
                    <td><small>${participant.email}</small></td>
                    <td>${sectionDisplay}</td>
                    <td class="text-center">${genderBadge}</td>
                    <td class="text-center">${typeBadge}</td>
                </tr>
            `;
        });

        $('#participantsTableBody').html(rows);
        updateFilterResultText(participants.length);
    }

    function updateParticipantCount(filtered, total) {
        const text = filtered === total 
            ? `<i class="fas fa-users"></i> ${total} Participant${total !== 1 ? 's' : ''}`
            : `<i class="fas fa-users"></i> ${filtered} of ${total} Participant${total !== 1 ? 's' : ''}`;
        $('#participantCount').html(text);
    }

    function updateFilterResultText(count) {
        const total = allParticipants.length;
        if (count === total) {
            $('#filterResultText').text(`Showing all ${total} participant${total !== 1 ? 's' : ''}`);
        } else {
            $('#filterResultText').text(`Showing ${count} of ${total} participant${total !== 1 ? 's' : ''}`);
        }
    }

    function showEmptyState() {
        $('#participantsTableBody').html(`
            <tr>
                <td colspan="6" class="text-center py-5">
                    <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No Participants Found</h5>
                    <p class="text-muted">There are no students enrolled in this class yet.</p>
                </td>
            </tr>
        `);
        $('#participantCount').html('<i class="fas fa-users"></i> 0 Participants');
        $('#filterResultText').text('');
    }

    function showErrorState() {
        $('#participantsTableBody').html(`
            <tr>
                <td colspan="6" class="text-center py-5">
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
        $('#filterResultText').text('');
    }

    // ========================================================================
    // FILTERS
    // ========================================================================
    function applyFilters() {
        const searchTerm = $('#participantSearchFilter').val().toLowerCase();
        const sectionFilter = $('#sectionFilter').val();
        const genderFilter = $('#genderFilter').val();
        const typeFilter = $('#typeFilter').val();

        const filtered = allParticipants.filter(function(participant) {
            const matchSearch = !searchTerm || 
                participant.student_number.toLowerCase().includes(searchTerm) ||
                participant.full_name.toLowerCase().includes(searchTerm);

            const matchSection = !sectionFilter || 
                participant.section_name === sectionFilter;

            const matchGender = !genderFilter || 
                participant.gender.toLowerCase() === genderFilter.toLowerCase();

            const matchType = !typeFilter || 
                participant.student_type === typeFilter;

            return matchSearch && matchSection && matchGender && matchType;
        });

        renderParticipantsTable(filtered);
        updateParticipantCount(filtered.length, allParticipants.length);
    }

    $('#participantSearchFilter, #sectionFilter, #genderFilter, #typeFilter').on('input change', function() {
        applyFilters();
    });

    $('#resetFiltersBtn').click(function() {
        $('#participantSearchFilter').val('');
        $('#sectionFilter').val('');
        $('#genderFilter').val('');
        $('#typeFilter').val('');
        renderParticipantsTable(allParticipants);
        updateParticipantCount(allParticipants.length, allParticipants.length);
    });
});