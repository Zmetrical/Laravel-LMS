console.log("list_strand");

let strands = [];
let editingStrandId = null;

$(document).ready(function() {
    loadStrands();
    setupEventListeners();
});

function setupEventListeners() {
    $('#strandForm').on('submit', function(e) {
        e.preventDefault();
        saveStrand();
    });

    $('#strandCode').on('input', function() {
        this.value = this.value.toUpperCase();
    });

    $('#createStrandModal').on('hidden.bs.modal', function() {
        resetStrandForm();
    });
}

// Load strands from database
function loadStrands() {
    $.ajax({
        url: API_ROUTES.getStrands,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                strands = response.data;
                renderStrandsTable();
            }
        },
        error: function(xhr) {
            console.error('Failed to load strands:', xhr);
            $('#strandsTableBody').html(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <i class="fas fa-exclamation-circle"></i> Failed to load strands
                    </td>
                </tr>
            `);
        }
    });
}

// Render strands table
function renderStrandsTable() {
    const tbody = $('#strandsTableBody');
    tbody.empty();

    if (strands.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    <i class="fas fa-info-circle"></i> No strands available. Create one to get started.
                </td>
            </tr>
        `);
        return;
    }

    strands.forEach((strand, index) => {
        const sectionCount = strand.sections_count || 0;
        
        tbody.append(`
            <tr>
                <td>${index + 1}</td>
                <td><strong>${strand.code}</strong></td>
                <td>${strand.name}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-dark" onclick="viewStrandSections(${strand.id})" title="View Sections">
                        <i class="fas fa-eye"></i> ${sectionCount}
                    </button>
                </td>
                <td class="text-center">

                    <button class="btn btn-sm btn-outline-dark" onclick="editStrand(${strand.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

// Save strand (create or update)
function saveStrand() {
    const formData = {
        code: $('#strandCode').val().trim(),
        name: $('#strandName').val().trim(),
        _token: $('input[name="_token"]').val()
    };

    // Validation
    if (!formData.code || !formData.name) {
        Swal.fire({
            icon: 'warning',
            title: 'Validation Error',
            text: 'Please fill in all required fields.',
            confirmButtonColor: '#17a2b8'
        });
        return;
    }

    const isEdit = editingStrandId !== null;
    const url = isEdit ? API_ROUTES.updateStrand.replace(':id', editingStrandId) : API_ROUTES.createStrand;
    const method = isEdit ? 'PUT' : 'POST';

    if (isEdit) {
        formData.id = editingStrandId;
    }

    // Disable submit button
    const submitBtn = $('#strandForm button[type="submit"]');
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

    $.ajax({
        url: url,
        method: method,
        data: formData,
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });

                $('#createStrandModal').modal('hide');
                loadStrands();
            }
        },
        error: function(xhr) {
            let errorMessage = 'Failed to save strand.';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = xhr.responseJSON.errors;
                errorMessage = Object.values(errors).flat().join('<br>');
            }

            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: errorMessage,
                confirmButtonColor: '#17a2b8'
            });
        },
        complete: function() {
            submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Strand');
        }
    });
}

// Edit strand - populate modal with strand data
function editStrand(id) {
    const strand = strands.find(s => s.id === id);
    
    if (!strand) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Strand not found.',
            confirmButtonColor: '#17a2b8'
        });
        return;
    }

    editingStrandId = id;
    
    // Populate form
    $('#strandId').val(strand.id);
    $('#strandCode').val(strand.code);
    $('#strandName').val(strand.name);
    
    // Update modal title
    $('#strandModalTitle').html('<i class="fas fa-edit"></i> Edit Strand');
    
    // Show modal
    $('#createStrandModal').modal('show');
}

// View strand sections
function viewStrandSections(strandId) {
    const strand = strands.find(s => s.id === strandId);
    
    if (!strand) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Strand not found.',
            confirmButtonColor: '#17a2b8'
        });
        return;
    }

    // Update modal title with strand info
    $('#sectionsModalTitle').html(`<i class="fas fa-list"></i> ${strand.code} - ${strand.name}`);
    
    // Show loading state
    $('#sectionsTableBody').html(`
        <tr>
            <td colspan="4" class="text-center">
                <i class="fas fa-spinner fa-spin"></i> Loading sections...
            </td>
        </tr>
    `);
    
    // Show modal
    $('#viewSectionsModal').modal('show');
    
    // Load sections via AJAX
    $.ajax({
        url: API_ROUTES.getStrandSections.replace(':id', strandId),
        method: 'GET',
        success: function(response) {
            if (response.success) {
                renderSectionsTable(response.data);
            }
        },
        error: function(xhr) {
            console.error('Failed to load sections:', xhr);
            $('#sectionsTableBody').html(`
                <tr>
                    <td colspan="4" class="text-center text-danger">
                        <i class="fas fa-exclamation-circle"></i> Failed to load sections
                    </td>
                </tr>
            `);
        }
    });
}

// Render sections table in modal
function renderSectionsTable(sections) {
    const tbody = $('#sectionsTableBody');
    tbody.empty();

    if (sections.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="4" class="text-center text-muted py-4">
                    <i class="fas fa-info-circle"></i> No sections available for this strand.
                </td>
            </tr>
        `);
        return;
    }

    sections.forEach((section, index) => {
        const statusBadge = section.status == 1 
            ? '<span class="badge badge-success">Active</span>' 
            : '<span class="badge badge-secondary">Inactive</span>';
        
        tbody.append(`
            <tr>
                <td>${index + 1}</td>
                <td>${escapeHtml(section.name)}</td>
                <td>${escapeHtml(section.level_name)}</td>
            </tr>
        `);
    });
}

// Reset form
function resetStrandForm() {
    $('#strandForm')[0].reset();
    $('#strandId').val('');
    $('#strandModalTitle').html('<i class="fas fa-plus"></i> Create New Strand');
    editingStrandId = null;
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}