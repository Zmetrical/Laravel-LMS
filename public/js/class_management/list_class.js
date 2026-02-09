console.log("class_management");

let dataTable;

$(document).ready(function() {
    // ========================================================================
    // DATATABLE INITIALIZATION WITH AJAX
    // ========================================================================
    
    function updateClassCount() {
        if (dataTable && dataTable.rows) {
            const count = dataTable.rows({ filter: 'applied' }).count();
            $('#classCount').text(count + ' Class' + (count !== 1 ? 'es' : ''));
        }
    }

    dataTable = $('#classesTable').DataTable({
        ajax: {
            url: API_ROUTES.getClassesList, // Add this route
            dataSrc: 'data'
        },
        columns: [
            { 
                data: 'class_name',
                render: function(data, type, row) {
                    return data;
                }
            },
            { 
                data: 'ww_perc',
                render: function(data) {
                    return '<span class="badge badge-secondary">' + data + '%</span>';
                }
            },
            { 
                data: 'pt_perc',
                render: function(data) {
                    return '<span class="badge badge-secondary">' + data + '%</span>';
                }
            },
            { 
                data: 'qa_perce',
                render: function(data) {
                    return '<span class="badge badge-secondary">' + data + '%</span>';
                }
            },
            { 
                data: 'id',
                orderable: false,
                className: 'text-center',
                render: function(data) {
                    return '<button class="btn btn-sm btn-outline-primary btn-edit" data-id="' + data + '" title="Edit">' +
                           '<i class="fas fa-edit"></i></button>';
                }
            }
        ],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        scrollX: true,
        autoWidth: false,
        order: [[0, 'asc']],
        searching: true,
        processing: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6">>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            emptyTable: "No classes found",
            zeroRecords: "No matching classes found",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ classes",
            infoEmpty: "Showing 0 to 0 of 0 classes",
            infoFiltered: "(filtered from _MAX_ total classes)",
            processing: "Loading classes...",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        drawCallback: function() {
            updateClassCount();
        }
    });

    // ========================================================================
    // SEARCH FILTER
    // ========================================================================

    $('#searchInput').on('keyup', function() {
        dataTable.search(this.value).draw();
    });

    // Clear Filters
    $('#clearFilters').on('click', function() {
        $('#searchInput').val('');
        dataTable.search('').draw();
    });

    // Initial count
    updateClassCount();

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
            alertDiv.removeClass('alert-danger').addClass('alert-success').show();
            statusSpan.html('<i class="fas fa-check-circle"></i> Perfect!');
            messageSpan.text(' Total weight is 100%');
            return true;
        } else if (total < 100) {
            alertDiv.removeClass('alert-success').addClass('alert-danger').show();
            statusSpan.html('<i class="fas fa-exclamation-triangle"></i> Error:');
            messageSpan.text(' Total weight is ' + total + '%. Need ' + (100 - total) + '% more.');
            return false;
        } else {
            alertDiv.removeClass('alert-success').addClass('alert-danger').show();
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
                        // Reload DataTable data via AJAX
                        dataTable.ajax.reload(null, false); // false = stay on current page
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
});