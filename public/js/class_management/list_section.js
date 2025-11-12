console.log("list_section.js loaded");

let sections = [];
let strands = [];
let levels = [];
let editingSectionId = null;

// Initialize on page load
$(document).ready(function() {
    loadStrands();
    loadLevels();
    loadSections();
    
    // Event listeners
    $('#filterStrand').on('change', loadSections);
    $('#filterLevel').on('change', loadSections);
    $('#searchSection').on('keyup', function(e) {
        if (e.key === 'Enter') {
            loadSections();
        }
    });
    
    // Form change listeners for code generation
    $('#sectionName, #strandSelect, #levelSelect').on('change keyup', generateSectionCode);
    
    // Form submit
    $('#sectionForm').on('submit', function(e) {
        e.preventDefault();
        saveSection();
    });
});

// Load strands
function loadStrands() {
    $.ajax({
        url: API_ROUTES.getStrands,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                strands = response.data;
                updateStrandSelects();
            }
        },
        error: function(xhr) {
            console.error('Failed to load strands:', xhr);
        }
    });
}

// Load levels
function loadLevels() {
    $.ajax({
        url: API_ROUTES.getLevels,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                levels = response.data;
                updateLevelSelects();
            }
        },
        error: function(xhr) {
            console.error('Failed to load levels:', xhr);
        }
    });
}

// Load sections with filters
function loadSections() {
    const filters = {
        strand: $('#filterStrand').val(),
        level: $('#filterLevel').val(),
        search: $('#searchSection').val()
    };
    
    $.ajax({
        url: API_ROUTES.getSections,
        type: 'GET',
        data: filters,
        success: function(response) {
            if (response.success) {
                sections = response.data;
                renderSectionsTable();
            }
        },
        error: function(xhr) {
            console.error('Failed to load sections:', xhr);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load sections. Please try again.'
            });
        }
    });
}

// Render sections table
function renderSectionsTable() {
    const tbody = $('#sectionsTableBody');
    tbody.empty();
    
    if (sections.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="8" class="text-center text-muted">
                    <i class="fas fa-inbox"></i> No sections found
                </td>
            </tr>
        `);
        return;
    }
    
    sections.forEach((section, index) => {

        const row = `
            <tr>
                <td class="text-center">${index + 1}</td>
                <td>${section.name}</td>
                <td>${section.strand_code}</td>
                <td>${section.level_name}</td>
                <td class="text-center">
                    <button class="btn btn-xs btn-secondary" onclick="viewSectionClasses(${section.id}, '${section.code}')">
                        <i class="fas fa-eye"></i> ${section.classes_count}
                    </button>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-primary" onclick="editSection(${section.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Update strand select dropdowns
function updateStrandSelects() {
    const strandSelect = $('#strandSelect');
    const filterSelect = $('#filterStrand');
    
    const strandValue = strandSelect.val();
    const filterValue = filterSelect.val();

    strandSelect.find('option:not(:first)').remove();
    filterSelect.find('option:not(:first)').remove();

    strands.forEach(strand => {
        if (strand.status == 1) {
            strandSelect.append(`<option value="${strand.id}">${strand.code} - ${strand.name}</option>`);
            filterSelect.append(`<option value="${strand.code}">${strand.code} - ${strand.name}</option>`);
        }
    });

    if (strandValue) strandSelect.val(strandValue);
    if (filterValue) filterSelect.val(filterValue);
}

// Update level select dropdowns
function updateLevelSelects() {
    const levelSelect = $('#levelSelect');
    const filterSelect = $('#filterLevel');
    
    const levelValue = levelSelect.val();
    const filterValue = filterSelect.val();

    levelSelect.find('option:not(:first)').remove();
    filterSelect.find('option:not(:first)').remove();

    levels.forEach(level => {
        levelSelect.append(`<option value="${level.id}">${level.name}</option>`);
        filterSelect.append(`<option value="${level.id}">${level.name}</option>`);
    });

    if (levelValue) levelSelect.val(levelValue);
    if (filterValue) filterSelect.val(filterValue);
}

// Generate section code preview
function generateSectionCode() {
    const name = $('#sectionName').val().trim().toUpperCase();
    const strandId = $('#strandSelect').val();
    const levelId = $('#levelSelect').val();
    
    if (name && strandId && levelId) {
        const strand = strands.find(s => s.id == strandId);
        const level = levels.find(l => l.id == levelId);
        
        if (strand && level) {
            const code = `${strand.code}-${level.name}-${name}`;
            $('#generatedCode').val(code);
        }
    } else {
        $('#generatedCode').val('');
    }
}

// Open create modal
function openCreateModal() {
    resetSectionForm();
    $('#sectionModalTitle').html('<i class="fas fa-plus-circle"></i> Create New Section');
    $('#submitBtn').html('<i class="fas fa-save"></i> Save Section');
    $('#sectionModal').modal('show');
}

// Edit section
function editSection(id) {
    const section = sections.find(s => s.id === id);
    if (!section) return;
    
    editingSectionId = id;
    $('#sectionId').val(id);
    $('#sectionName').val(section.name);
    $('#strandSelect').val(section.strand_id);
    $('#levelSelect').val(section.level_id);
    generateSectionCode();
    
    $('#sectionModalTitle').html('<i class="fas fa-edit"></i> Edit Section');
    $('#submitBtn').html('<i class="fas fa-save"></i> Update Section');
    $('#sectionModal').modal('show');
}

// Save section (create or update)
function saveSection() {
    const formData = {
        name: $('#sectionName').val().trim(),
        strand_id: $('#strandSelect').val(),
        level_id: $('#levelSelect').val()
    };
    
    // Validation
    if (!formData.name || !formData.strand_id || !formData.level_id) {
        Swal.fire({
            icon: 'warning',
            title: 'Incomplete Form',
            text: 'Please fill in all required fields.'
        });
        return;
    }
    
    const isEdit = editingSectionId !== null;
    const url = isEdit ? API_ROUTES.updateSection.replace(':id', editingSectionId) : API_ROUTES.createSection;
    const method = isEdit ? 'PUT' : 'POST';
    
    // Disable submit button
    $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    
    $.ajax({
        url: url,
        type: method,
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                $('#sectionModal').modal('hide');
                loadSections();
            }
        },
        error: function(xhr) {
            let errorMessage = 'Failed to save section. Please try again.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = Object.values(xhr.responseJSON.errors).flat();
                errorMessage = errors.join('<br>');
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: errorMessage
            });
        },
        complete: function() {
            $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Save Section');
        }
    });
}

// View section classes
function viewSectionClasses(sectionId, sectionCode) {
    $('#sectionNameDisplay').text(sectionCode);
    $('#classesTableBody').html('<tr><td colspan="4" class="text-center">Loading...</td></tr>');
    $('#viewClassesModal').modal('show');
    
    const url = API_ROUTES.getSectionClasses.replace(':id', sectionId);
    
    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                renderClassesTable(response.data);
            }
        },
        error: function(xhr) {
            $('#classesTableBody').html('<tr><td colspan="4" class="text-center text-danger">Failed to load classes</td></tr>');
            console.error('Failed to load classes:', xhr);
        }
    });
}

// Render classes table in modal
function renderClassesTable(classes) {
    const tbody = $('#classesTableBody');
    tbody.empty();
    
    if (classes.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="fas fa-inbox"></i> No classes enrolled yet
                </td>
            </tr>
        `);
        return;
    }
    
    classes.forEach((classItem, index) => {
        const row = `
            <tr>
                <td>${index + 1}</td>
                <td><strong>${classItem.class_code}</strong></td>
                <td>${classItem.class_name}</td>
                <td>
                    <small>
                        WW: ${classItem.ww_perc}% | 
                        PT: ${classItem.pt_perc}% | 
                        QA: ${classItem.qa_perce}%
                    </small>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Reset form
function resetSectionForm() {
    $('#sectionForm')[0].reset();
    $('#sectionId').val('');
    $('#generatedCode').val('');
    editingSectionId = null;
}