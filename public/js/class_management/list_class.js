console.log("class_management");

$(document).ready(function() {
    // ========================================================================
    // WEIGHT VALIDATION FUNCTION (Unified for both Create & Edit)
    // ========================================================================
    
    function validateWeights() {
        const ww = parseFloat($('#ww_perc').val()) || 0;
        const pt = parseFloat($('#pt_perc').val()) || 0;
        const qa = parseFloat($('#qa_perce').val()) || 0;
        const total = ww + pt + qa;

        const alertDiv = $('#weightAlert');
        const statusSpan = $('#weightStatus');
        const messageSpan = $('#weightMessage');

        if (total === 100) {
            alertDiv.removeClass('alert-danger alert-warning').addClass('alert-success').show();
            statusSpan.html('<i class="fas fa-check-circle"></i> Perfect!');
            messageSpan.text(' Total weight is 100%');
            return true;
        } else if (total < 100) {
            alertDiv.removeClass('alert-success alert-danger').addClass('alert-warning').show();
            statusSpan.html('<i class="fas fa-exclamation-triangle"></i> Warning:');
            messageSpan.text(' Total weight is ' + total + '%. Need ' + (100 - total) + '% more.');
            return false;
        } else {
            alertDiv.removeClass('alert-success alert-warning').addClass('alert-danger').show();
            statusSpan.html('<i class="fas fa-times-circle"></i> Error:');
            messageSpan.text(' Total weight is ' + total + '%. Exceeds 100% by ' + (total - 100) + '%.');
            return false;
        }
    }

    // ========================================================================
    // UNIFIED MODAL FUNCTIONS
    // ========================================================================

    // Real-time validation on input
    $('.weight-input').on('input', function() {
        validateWeights();
    });

    // Open Create Modal
    window.openClassModal = function() {
        resetModal();
        $('#classModalTitle').html('<i class="fas fa-plus-circle"></i> Create New Class');
        $('#submitBtn').html('<i class="fas fa-save"></i> Save Class');
        $('#classCodeGroup').hide();
        $('#form_method').val('');
        validateWeights();
        $('#classModal').modal('show');
    };

    // Reset Modal to Default State
    function resetModal() {
        $('#classForm')[0].reset();
        $('#class_id').val('');
        $('#class_code').val('');
        $('#ww_perc').val(30);
        $('#pt_perc').val(50);
        $('#qa_perce').val(20);
        $('#weightAlert').hide();
    }

    // When modal closes
    $('#classModal').on('hidden.bs.modal', function() {
        resetModal();
    });

    // ========================================================================
    // EDIT FUNCTIONALITY
    // ========================================================================

    $(document).on('click', '.btn-edit', function() {
        const classId = $(this).data('id');

        $.ajax({
            url: API_ROUTES.getClass.replace(':id', classId),
            method: 'GET',
            beforeSend: function() {
                Swal.fire({
                    title: 'Loading...',
                    text: 'Fetching class data',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                Swal.close();
                
                if (response.success) {
                    const classData = response.data;

                    // Populate form fields
                    $('#class_id').val(classData.id);
                    $('#class_code').val(classData.class_code);
                    $('#class_name').val(classData.class_name);
                    $('#ww_perc').val(classData.ww_perc);
                    $('#pt_perc').val(classData.pt_perc);
                    $('#qa_perce').val(classData.qa_perce);

                    // Update modal UI for editing
                    $('#classModalTitle').html('<i class="fas fa-edit"></i> Edit Class');
                    $('#submitBtn').html('<i class="fas fa-save"></i> Update Class');
                    $('#classCodeGroup').show();
                    $('#form_method').val('PUT');

                    // Validate and show modal
                    validateWeights();
                    $('#classModal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Failed to load class data'
                    });
                }
            },
            error: function(xhr) {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to load class data. Please try again.'
                });
            }
        });
    });

    // ========================================================================
    // FORM SUBMISSION (Create/Update)
    // ========================================================================

    $('#classForm').on('submit', function(e) {
        e.preventDefault();

        // Validate weights
        if (!validateWeights()) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Weights',
                text: 'Grade weight distribution must total exactly 100%'
            });
            return false;
        }

        // Check required fields
        if (!$('#class_name').val().trim()) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Data',
                text: 'Please fill in all required fields'
            });
            return false;
        }

        const formData = new FormData(this);
        const classId = $('#class_id').val();
        const isEdit = classId !== '';

        // Show loading
        Swal.fire({
            title: 'Processing...',
            text: isEdit ? 'Updating class record' : 'Creating class record',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Determine URL and method
        const url = isEdit ? API_ROUTES.updateClass.replace(':id', classId) : API_ROUTES.insertClass;

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        $('#classModal').modal('hide');
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Something went wrong'
                    });
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMessage = '';

                    $.each(errors, function(key, value) {
                        errorMessage += value[0] + '<br>';
                    });

                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        html: errorMessage
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Something went wrong. Please try again.'
                    });
                }
            }
        });
    });

    // ========================================================================
    // SEARCH & FILTER
    // ========================================================================

    $('#searchInput').on('keyup', function() {
        const searchValue = $(this).val().toLowerCase();
        
        $('#classesTable tbody tr').filter(function() {
            const row = $(this);
            const matchesSearch = row.text().toLowerCase().indexOf(searchValue) > -1;
            row.toggle(matchesSearch);
        });

        updateTableInfo();
    });

    $('#sortBy').on('change', function() {
        const sortValue = $(this).val();
        const tbody = $('#classesTable tbody');
        const rows = tbody.find('tr').get();

        rows.sort(function(a, b) {
            if (sortValue === 'name') {
                const aName = $(a).find('td:eq(1)').text();
                const bName = $(b).find('td:eq(1)').text();
                return aName.localeCompare(bName);
            } else if (sortValue === 'newest') {
                return $(b).data('id') - $(a).data('id');
            } else if (sortValue === 'oldest') {
                return $(a).data('id') - $(b).data('id');
            }
        });

        $.each(rows, function(index, row) {
            tbody.append(row);
            $(row).find('td:first').text(index + 1);
        });
    });

    // ========================================================================
    // TABLE INFO UPDATE
    // ========================================================================

    function updateTableInfo() {
        const visibleRows = $('#classesTable tbody tr:visible').length;
        const totalRows = $('#classesTable tbody tr').length;
        
        $('#showingFrom').text(visibleRows > 0 ? 1 : 0);
        $('#showingTo').text(visibleRows);
        $('#totalEntries').text(totalRows);
    }

    // Initial update
    updateTableInfo();
});