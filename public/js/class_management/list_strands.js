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

    $('#confirmDeleteStrand').on('click', function() {
        deleteStrand();
    });

    $('#createStrandModal').on('hidden.bs.modal', function() {
        resetStrandForm();
    });
}

// Load strands from database
function loadStrands() {
    $.ajax({
        url: '/strand_management/get_strands',
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
                <td><span class="badge badge-primary badge-lg">${strand.code}</span></td>
                <td><strong>${strand.name}</strong></td>
                <td class="text-center">
                    <span class="badge badge-info">${sectionCount}</span>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-info" onclick="editStrand(${strand.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="confirmDeleteStrand(${strand.id}, '${strand.code}')" title="Delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

// Save strand (placeholder for CRUD)
function saveStrand() {
    // TODO: Implement AJAX save
    console.log('Save strand - to be implemented');
}

// Edit strand (placeholder for CRUD)
function editStrand(id) {
    // TODO: Implement edit functionality
    console.log('Edit strand:', id);
}

// Confirm delete strand (placeholder for CRUD)
function confirmDeleteStrand(id, code) {
    // TODO: Implement delete confirmation
    console.log('Delete strand:', id, code);
}

// Delete strand (placeholder for CRUD)
function deleteStrand() {
    // TODO: Implement AJAX delete
    console.log('Delete strand - to be implemented');
}

// Reset form
function resetStrandForm() {
    $('#strandForm')[0].reset();
    $('#strandId').val('');
    $('#strandModalTitle').html('<i class="fas fa-plus-circle"></i> Create New Strand');
    editingStrandId = null;
}