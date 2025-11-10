$(document).ready(function() {
    // View Results Button
    $('.view-results-btn').on('click', function() {
        const attemptId = $(this).data('attempt-id');
        loadResults(attemptId);
    });

    function loadResults(attemptId) {
        const url = API_ROUTES.getResults.replace(':attemptId', attemptId);
        
        $('#resultsModal').modal('show');
        $('#resultsContent').html(`
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Loading...</span>
                </div>
                <p class="mt-3">Loading results...</p>
            </div>
        `);

        $.ajax({
            url: url,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            success: function(response) {
                if (response.success) {
                    displayResults(response.data);
                } else {
                    $('#resultsContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            ${response.message || 'Failed to load results'}
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                console.error('Error loading results:', xhr);
                $('#resultsContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        Failed to load results. Please try again.
                    </div>
                `);
            }
        });
    }

});