console.log("list_section");



// Update strand select dropdowns
function updateStrandSelects() {
    const strandSelect = $('#strand');
    const filterSelect = $('#filterStrand');
    
    const strandValue = strandSelect.val();
    const filterValue = filterSelect.val();

    strandSelect.find('option:not(:first)').remove();
    filterSelect.find('option:not(:first)').remove();

    strands.forEach(strand => {
        strandSelect.append(`<option value="${strand.code}">${strand.code} - ${strand.name}</option>`);
        filterSelect.append(`<option value="${strand.code}">${strand.code} - ${strand.name}</option>`);
    });

    if (strandValue) strandSelect.val(strandValue);
    if (filterValue) filterSelect.val(filterValue);
}

// Save section (placeholder for CRUD)
function saveSection() {
    // TODO: Implement AJAX save
    console.log('Save section - to be implemented');
}

// Edit section (placeholder for CRUD)
function editSection(id) {
    // TODO: Implement edit functionality
    console.log('Edit section:', id);
}

// Confirm delete section (placeholder for CRUD)
function confirmDeleteSection(id, name) {
    // TODO: Implement delete confirmation
    console.log('Delete section:', id, name);
}

// Delete section (placeholder for CRUD)
function deleteSection() {
    // TODO: Implement AJAX delete
    console.log('Delete section - to be implemented');
}

// Reset form
function resetSectionForm() {
    $('#sectionForm')[0].reset();
    $('#sectionId').val('');
    $('#sectionModalTitle').html('<i class="fas fa-plus-circle"></i> Create New Section');
    editingSectionId = null;
}