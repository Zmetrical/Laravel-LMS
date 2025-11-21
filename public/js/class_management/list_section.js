console.log("list_section.js loaded");

let sections = [];
let filteredSections = [];
let strands = [];
let levels = [];
let editingSectionId = null;

// Initialize on page load
$(document).ready(function() {
    loadStrands();
    loadLevels();
    loadSections();
    setupEventListeners();
});

function setupEventListeners() {
    // Form submission
    $('#sectionForm').on('submit', function(e) {
        e.preventDefault();
        saveSection();
    });

    // Auto-uppercase section name
    $('#sectionName').on('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Modal reset on close
    $('#sectionModal').on('hidden.bs.modal', function() {
        resetSectionForm();
    });

    // Filter changes
    $('#filterStrand, #filterLevel').on('change', function() {
        applyFilters();
    });

    // Clear filters button
    $('#clearFilters').on('click', function() {
        $('#filterStrand').val('');
        $('#filterLevel').val('');
        applyFilters();
    });
}

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

// Load sections from database
function loadSections() {
    $.ajax({
        url: API_ROUTES.getSections,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                sections = response.data;
                filteredSections = [...sections];
                updateSectionCount();
                renderSectionsTable();
            }
        },
        error: function(xhr) {
            console.error('Failed to load sections:', xhr);
            $('#sectionsTableBody').html(`
                <tr>
                    <td colspan="6" class="text-center text-danger">
                        <i class="fas fa-exclamation-circle"></i> Failed to load sections
                    </td>
                </tr>
            `);
        }
    });
}

// Apply filters
function applyFilters() {
    const strandFilter = $('#filterStrand').val();
    const levelFilter = $('#filterLevel').val();
    
    filteredSections = sections.filter(section => {
        // Filter by strand
        const matchesStrand = !strandFilter || section.strand_code === strandFilter;
        
        // Filter by level
        const matchesLevel = !levelFilter || section.level_id == levelFilter;
        
        return matchesStrand && matchesLevel;
    });
    
    updateSectionCount();
    renderSectionsTable();
}

// Update section count badge
function updateSectionCount() {
    const count = filteredSections.length;
    $('#sectionsCount').text(count + ' Section' + (count !== 1 ? 's' : ''));
}

// Render sections table
function renderSectionsTable() {
    const tbody = $('#sectionsTableBody');
    tbody.empty();

    if (filteredSections.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="6" class="text-center text-muted py-4">
                    <i class="fas fa-info-circle"></i> No sections found.
                </td>
            </tr>
        `);
        return;
    }

    filteredSections.forEach((section, index) => {
        const classCount = section.classes_count || 0;
        
        tbody.append(`
            <tr>
                <td>${index + 1}</td>
                <td><strong>${escapeHtml(section.name)}</strong></td>
                <td>${escapeHtml(section.strand_code)}</td>
                <td>${escapeHtml(section.level_name)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-secondary" onclick="viewSectionClasses(${section.id}, '${escapeHtml(section.code)}')" title="View Classes">
                        <i class="fas fa-book"></i> ${classCount}
                    </button>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" onclick="editSection(${section.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `);
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

// Open create modal
function openCreateModal() {
    resetSectionForm();
    $('#sectionModalTitle').html('<i class="fas fa-plus"></i> Create New Section');
    $('#sectionModal').modal('show');
}

// Edit section
function editSection(id) {
    const section = sections.find(s => s.id === id);
    
    if (!section) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Section not found.',
            confirmButtonColor: '#007bff'
        });
        return;
    }

    editingSectionId = id;
    
    // Populate form
    $('#sectionId').val(section.id);
    $('#sectionName').val(section.name);
    $('#strandSelect').val(section.strand_id);
    $('#levelSelect').val(section.level_id);
    
    // Update modal title
    $('#sectionModalTitle').html('<i class="fas fa-edit"></i> Edit Section');
    
    // Show modal
    $('#sectionModal').modal('show');
}

// Save section (create or update)
function saveSection() {
    const formData = {
        name: $('#sectionName').val().trim(),
        strand_id: $('#strandSelect').val(),
        level_id: $('#levelSelect').val(),
        _token: $('input[name="_token"]').val()
    };
    
    // Validation
    if (!formData.name || !formData.strand_id || !formData.level_id) {
        Swal.fire({
            icon: 'warning',
            title: 'Validation Error',
            text: 'Please fill in all required fields.',
            confirmButtonColor: '#007bff'
        });
        return;
    }
    
    const isEdit = editingSectionId !== null;
    const url = isEdit ? API_ROUTES.updateSection.replace(':id', editingSectionId) : API_ROUTES.createSection;
    const method = isEdit ? 'PUT' : 'POST';
    
    if (isEdit) {
        formData.id = editingSectionId;
    }
    
    // Disable submit button
    const submitBtn = $('#sectionForm button[type="submit"]');
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
    
    $.ajax({
        url: url,
        type: method,
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
                
                $('#sectionModal').modal('hide');
                loadSections();
            }
        },
        error: function(xhr) {
            let errorMessage = 'Failed to save section.';
            
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
                confirmButtonColor: '#007bff'
            });
        },
        complete: function() {
            submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Section');
        }
    });
}

// View section classes
function viewSectionClasses(sectionId, sectionCode) {
    const section = sections.find(s => s.id === sectionId);
    
    if (!section) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Section not found.',
            confirmButtonColor: '#007bff'
        });
        return;
    }

    // Update modal title with section info
    $('#classesModalTitle').html(`<i class="fas fa-book"></i> ${sectionCode}`);
    
    // Show loading state
    $('#classesTableBody').html(`
        <tr>
            <td colspan="3" class="text-center">
                <i class="fas fa-spinner fa-spin"></i> Loading classes...
            </td>
        </tr>
    `);
    
    // Show modal
    $('#viewClassesModal').modal('show');
    
    // Load classes via AJAX
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
            console.error('Failed to load classes:', xhr);
            $('#classesTableBody').html(`
                <tr>
                    <td colspan="3" class="text-center text-danger">
                        <i class="fas fa-exclamation-circle"></i> Failed to load classes
                    </td>
                </tr>
            `);
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
                <td colspan="3" class="text-center text-muted py-4">
                    <i class="fas fa-info-circle"></i> No classes available for this section.
                </td>
            </tr>
        `);
        return;
    }
    
    classes.forEach((classItem, index) => {
        tbody.append(`
            <tr>
                <td>${index + 1}</td>
                <td><strong>${escapeHtml(classItem.class_name)}</strong></td>
                <td>
                    <small>
                        WW: ${classItem.ww_perc}% | 
                        PT: ${classItem.pt_perc}% | 
                        QA: ${classItem.qa_perce}%
                    </small>
                </td>
            </tr>
        `);
    });
}

// Reset form
function resetSectionForm() {
    $('#sectionForm')[0].reset();
    $('#sectionId').val('');
    $('#sectionModalTitle').html('<i class="fas fa-plus"></i> Create New Section');
    editingSectionId = null;
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