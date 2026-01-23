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
        
        // Sort participants by gender (Male first, then Female)
        const sortedParticipants = participants.sort((a, b) => {
            if (a.gender.toLowerCase() === b.gender.toLowerCase()) return 0;
            return a.gender.toLowerCase() === 'male' ? -1 : 1;
        });

        // Separate by gender
        const maleParticipants = sortedParticipants.filter(p => p.gender.toLowerCase() === 'male');
        const femaleParticipants = sortedParticipants.filter(p => p.gender.toLowerCase() === 'female');

        let rowCounter = 1;

        // Render Male Section  
        if (maleParticipants.length > 0) {
            rows += `
                <tr class="bg-primary">
                    <td colspan="6" class="font-weight-bold">
                        <i class="fas fa-mars mr-2"></i>MALE (${maleParticipants.length})
                    </td>
                </tr>
            `;

            maleParticipants.forEach(function(participant) {
                rows += renderParticipantRow(participant, rowCounter++);
            });
        }

        // Render Female Section
        if (femaleParticipants.length > 0) {
            rows += `
                <tr class="bg-primary">
                    <td colspan="6" class="font-weight-bold">
                        <i class="fas fa-venus mr-2"></i>FEMALE (${femaleParticipants.length})
                    </td>
                </tr>
            `;

            femaleParticipants.forEach(function(participant) {
                rows += renderParticipantRow(participant, rowCounter++);
            });
        }

        $('#participantsTableBody').html(rows);
        updateFilterResultText(participants.length);
    }

    function renderParticipantRow(participant, index) {
        
        const typeBadge = participant.student_type === 'regular'
            ? '<span class="badge badge-primary">Regular</span>'
            : '<span class="badge badge-secondary">Irregular</span>';

        const sectionDisplay = participant.section_name !== 'No Section'
            ? `${participant.section_name}`
            : '<span class="text-muted">No Section</span>';

        return `
            <tr>
                <td >
                    <strong>${participant.student_number}</strong>
                </td>
                <td>
                    ${participant.full_name}
                </td>
                <td>${sectionDisplay}</td>
                <td class="text-center">${typeBadge}</td>
            </tr>
        `;
    }

    function updateParticipantCount(filtered, total) {
        
        const text = filtered === total 
            ? `<i class="fas fa-users"></i> ${total} Participant${total !== 1 ? 's' : ''}`
            : `<i class="fas fa-users"></i> ${filtered} of ${total} Participant${total !== 1 ? 's' : ''}`;
        $('#participantCount').html(text);
    }

    function updateFilterResultText(count) {
        const total = allParticipants.length;
        const maleCount = allParticipants.filter(p => p.gender.toLowerCase() === 'male').length;
        const femaleCount = allParticipants.filter(p => p.gender.toLowerCase() === 'female').length;
        
        if (count === total) {
            $('#filterResultText').text(`Showing all ${total} participant${total !== 1 ? 's' : ''} (Male: ${maleCount}, Female: ${femaleCount})`);
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