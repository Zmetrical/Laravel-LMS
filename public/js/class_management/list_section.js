console.log("list_section.js loaded - Tab Version");

let activeSectionDataTable;
let inactiveSectionDataTable;

let sections = [];
let strands = [];
let levels = [];
let editingSectionId = null;

// Initialize on page load
$(document).ready(function() {
    initializeDataTables();
    setupEventListeners();
    loadStrands();
    loadLevels();
    loadSections();
});

// Column index mapping for Active Sections
const COL_ACTIVE = {
    NAME: 0,
    STRAND: 1,
    LEVEL: 2,
    ACTIONS: 3
};

// Column index mapping for Inactive Sections
const COL_INACTIVE = {
    NAME: 0,
    STRAND: 1,
    LEVEL: 2,
    ACTIONS: 3
};

function initializeDataTables() {
    // Active Sections DataTable Configuration
    const activeConfig = {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        scrollX: true,
        autoWidth: false,
        order: [[0, 'asc']],
        searching: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6">>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            emptyTable: "No active sections found",
            zeroRecords: "No matching sections found",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ sections",
            infoEmpty: "Showing 0 to 0 of 0 sections",
            infoFiltered: "(filtered from _MAX_ total sections)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        columnDefs: [
            { targets: 3, orderable: false }
        ]
    };

    // Inactive Sections DataTable Configuration
    const inactiveConfig = {
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        scrollX: true,
        autoWidth: false,
        order: [[0, 'asc']],
        searching: true,
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6">>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        language: {
            emptyTable: "No inactive sections found",
            zeroRecords: "No matching sections found",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ sections",
            infoEmpty: "Showing 0 to 0 of 0 sections",
            infoFiltered: "(filtered from _MAX_ total sections)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        columnDefs: [
            { targets: 3, orderable: false }
        ]
    };

    // Initialize Active Sections DataTable
    activeSectionDataTable = $('#activeSectionTable').DataTable(activeConfig);
    
    // Initialize Inactive Sections DataTable
    inactiveSectionDataTable = $('#inactiveSectionTable').DataTable(inactiveConfig);
    
    // Hide loading indicators and show tables
    $('#activeTableLoading').hide();
    $('#activeSectionTable').show();
    $('#inactiveTableLoading').hide();
    $('#inactiveSectionTable').show();
}

function setupEventListeners() {
    // Form submissions
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

    // ========================================
    // ACTIVE TAB FILTERS
    // ========================================
    
    // Active strand filter
    $('#filterActiveStrand').on('change', function() {
        applyActiveFilters();
    });

    // Active level filter
    $('#filterActiveLevel').on('change', function() {
        applyActiveFilters();
    });

    // Search Active Section Filter
    $('#searchActiveSection').on('keyup', function() {
        applyActiveFilters();
    });

    // Clear Active Filters
    $('#clearActiveFilters').on('click', function() {
        $('#searchActiveSection').val('');
        $('#filterActiveStrand').val('');
        $('#filterActiveLevel').val('');
        applyActiveFilters();
    });

    // ========================================
    // INACTIVE TAB FILTERS
    // ========================================
    
    // Inactive strand filter
    $('#filterInactiveStrand').on('change', function() {
        applyInactiveFilters();
    });

    // Inactive level filter
    $('#filterInactiveLevel').on('change', function() {
        applyInactiveFilters();
    });

    // Search Inactive Section Filter
    $('#searchInactiveSection').on('keyup', function() {
        applyInactiveFilters();
    });

    // Clear Inactive Filters
    $('#clearInactiveFilters').on('click', function() {
        $('#searchInactiveSection').val('');
        $('#filterInactiveStrand').val('');
        $('#filterInactiveLevel').val('');
        applyInactiveFilters();
    });

    // Fix header/body alignment on zoom/resize
    $(window).on('resize', function() {
        if (activeSectionDataTable) {
            activeSectionDataTable.columns.adjust().draw();
        }
        if (inactiveSectionDataTable) {
            inactiveSectionDataTable.columns.adjust().draw();
        }
    });
    
    // Adjust tables when tab is shown
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#activeSections') {
            activeSectionDataTable.columns.adjust().draw();
        } else if ($(e.target).attr('href') === '#inactiveSections') {
            inactiveSectionDataTable.columns.adjust().draw();
        }
    });
}

// Apply Active Filters
function applyActiveFilters() {
    const searchValue = $('#searchActiveSection').val().toLowerCase();
    const strandValue = $('#filterActiveStrand').val();
    const levelValue = $('#filterActiveLevel').val();
    
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'activeSectionTable') return true;
        
        // Search filter
        if (searchValue !== '') {
            const name = data[COL_ACTIVE.NAME].toLowerCase();
            if (!name.includes(searchValue)) {
                return false;
            }
        }
        
        // Strand filter
        if (strandValue !== '') {
            const strand = data[COL_ACTIVE.STRAND];
            if (strand !== strandValue) {
                return false;
            }
        }
        
        // Level filter
        if (levelValue !== '') {
            const level = data[COL_ACTIVE.LEVEL];
            if (level !== levelValue) {
                return false;
            }
        }
        
        return true;
    });
    
    activeSectionDataTable.draw();
    $.fn.dataTable.ext.search.pop();
}

// Apply Inactive Filters
function applyInactiveFilters() {
    const searchValue = $('#searchInactiveSection').val().toLowerCase();
    const strandValue = $('#filterInactiveStrand').val();
    const levelValue = $('#filterInactiveLevel').val();
    
    $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
        if (settings.nTable.id !== 'inactiveSectionTable') return true;
        
        // Search filter
        if (searchValue !== '') {
            const name = data[COL_INACTIVE.NAME].toLowerCase();
            if (!name.includes(searchValue)) {
                return false;
            }
        }
        
        // Strand filter
        if (strandValue !== '') {
            const strand = data[COL_INACTIVE.STRAND];
            if (strand !== strandValue) {
                return false;
            }
        }
        
        // Level filter
        if (levelValue !== '') {
            const level = data[COL_INACTIVE.LEVEL];
            if (level !== levelValue) {
                return false;
            }
        }
        
        return true;
    });
    
    inactiveSectionDataTable.draw();
    $.fn.dataTable.ext.search.pop();
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
    const filters = {
        strand: $('#filterActiveStrand').val(),
        level: $('#filterActiveLevel').val()
    };

    $.ajax({
        url: API_ROUTES.getSections,
        type: 'GET',
        data: filters,
        success: function(response) {
            if (response.success) {
                sections = response.data;
                renderSectionsTables();
            }
        },
        error: function(xhr) {
            console.error('Failed to load sections:', xhr);
            showError('activeSectionsTableBody', 4);
            showError('inactiveSectionsTableBody', 4);
        }
    });
}

// Render sections into both tables
function renderSectionsTables() {
    // Check if DataTables are initialized
    if (!activeSectionDataTable || !inactiveSectionDataTable) {
        console.warn('DataTables not initialized yet');
        return;
    }
    
    const activeSections = sections.filter(s => s.is_active == 1);
    const inactiveSections = sections.filter(s => s.is_active == 0);
    
    // Update counts
    $('#activeCount').text(activeSections.length);
    $('#inactiveCount').text(inactiveSections.length);
    
    // Clear tables
    activeSectionDataTable.clear();
    inactiveSectionDataTable.clear();
    
    // Render Active Sections
    activeSections.forEach((section) => {
        const rowNode = activeSectionDataTable.row.add([
            `<strong>${escapeHtml(section.name)}</strong>`,
            escapeHtml(section.strand_code),
            escapeHtml(section.level_name),
            `<div class="text-center">
                <button class="btn btn-sm btn-outline-primary" 
                        onclick="editSection(${section.id})" 
                        title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-primary" 
                        onclick="toggleSectionStatus(${section.id}, 'deactivate')" 
                        title="Deactivate Section">
                    <i class="fas fa-toggle-on"></i>
                </button>
            </div>`
        ]).node();
        
        $(rowNode).data('section-id', section.id);
    });
    
    // Render Inactive Sections
    inactiveSections.forEach((section) => {
        const rowNode = inactiveSectionDataTable.row.add([
            `<strong>${escapeHtml(section.name)}</strong>`,
            escapeHtml(section.strand_code),
            escapeHtml(section.level_name),
            `<div class="text-center">
                <button class="btn btn-sm btn-outline-primary" 
                        onclick="editSection(${section.id})" 
                        title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary" 
                        onclick="toggleSectionStatus(${section.id}, 'activate')" 
                        title="Activate Section">
                    <i class="fas fa-toggle-off"></i>
                </button>
            </div>`
        ]).node();
        
        $(rowNode).data('section-id', section.id);
    });
    
    activeSectionDataTable.draw();
    inactiveSectionDataTable.draw();
}

// Show error message
function showError(tableBodyId, colspan) {
    $(`#${tableBodyId}`).html(`
        <tr>
            <td colspan="${colspan}" class="text-center text-danger">
                <i class="fas fa-exclamation-circle"></i> Failed to load sections
            </td>
        </tr>
    `);
}

// Update strand select dropdowns
function updateStrandSelects() {
    const strandSelect = $('#strandSelect');
    const filterActiveSelect = $('#filterActiveStrand');
    const filterInactiveSelect = $('#filterInactiveStrand');
    
    const strandValue = strandSelect.val();
    const filterActiveValue = filterActiveSelect.val();
    const filterInactiveValue = filterInactiveSelect.val();

    strandSelect.find('option:not(:first)').remove();
    filterActiveSelect.find('option:not(:first)').remove();
    filterInactiveSelect.find('option:not(:first)').remove();

    strands.forEach(strand => {
        if (strand.status == 1) {
            strandSelect.append(`<option value="${strand.id}">${strand.code} - ${strand.name}</option>`);
            filterActiveSelect.append(`<option value="${strand.code}">${strand.code} - ${strand.name}</option>`);
            filterInactiveSelect.append(`<option value="${strand.code}">${strand.code} - ${strand.name}</option>`);
        }
    });

    if (strandValue) strandSelect.val(strandValue);
    if (filterActiveValue) filterActiveSelect.val(filterActiveValue);
    if (filterInactiveValue) filterInactiveSelect.val(filterInactiveValue);
}

// Update level select dropdowns
function updateLevelSelects() {
    const levelSelect = $('#levelSelect');
    const filterActiveSelect = $('#filterActiveLevel');
    const filterInactiveSelect = $('#filterInactiveLevel');
    
    const levelValue = levelSelect.val();
    const filterActiveValue = filterActiveSelect.val();
    const filterInactiveValue = filterInactiveSelect.val();

    levelSelect.find('option:not(:first)').remove();
    filterActiveSelect.find('option:not(:first)').remove();
    filterInactiveSelect.find('option:not(:first)').remove();

    levels.forEach(level => {
        levelSelect.append(`<option value="${level.id}">${level.name}</option>`);
        filterActiveSelect.append(`<option value="${level.name}">${level.name}</option>`);
        filterInactiveSelect.append(`<option value="${level.name}">${level.name}</option>`);
    });

    if (levelValue) levelSelect.val(levelValue);
    if (filterActiveValue) filterActiveSelect.val(filterActiveValue);
    if (filterInactiveValue) filterInactiveSelect.val(filterInactiveValue);
}

// Open create modal
function openCreateModal() {
    resetSectionForm();
    $('#sectionModalTitle').html('<i class="fas fa-plus"></i> Create New Section');
    
    // Enable fields for creation
    $('#strandSelect').prop('disabled', false).removeClass('bg-light');
    $('#levelSelect').prop('disabled', false).removeClass('bg-light');
    $('#strandRequired').show();
    $('#levelRequired').show();
    $('#strandEditNote').addClass('d-none');
    $('#levelEditNote').addClass('d-none');
    
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
    
    $('#sectionId').val(section.id);
    $('#sectionName').val(section.name);
    $('#strandSelect').val(section.strand_id);
    $('#levelSelect').val(section.level_id);
    
    // Disable strand and level fields in edit mode
    $('#strandSelect').prop('disabled', true).addClass('bg-light');
    $('#levelSelect').prop('disabled', true).addClass('bg-light');
    $('#strandRequired').hide();
    $('#levelRequired').hide();
    $('#strandEditNote').removeClass('d-none');
    $('#levelEditNote').removeClass('d-none');
    
    $('#sectionModalTitle').html('<i class="fas fa-edit"></i> Edit Section');
    
    $('#sectionModal').modal('show');
}

// Save section (create or update)
function saveSection() {
    const formData = {
        name: $('#sectionName').val().trim(),
        _token: $('input[name="_token"]').val()
    };
    
    const isEdit = editingSectionId !== null;
    
    // Only include strand_id and level_id for creation
    if (!isEdit) {
        formData.strand_id = $('#strandSelect').val();
        formData.level_id = $('#levelSelect').val();
        
        if (!formData.name || !formData.strand_id || !formData.level_id) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please fill in all required fields.',
                confirmButtonColor: '#007bff'
            });
            return;
        }
    } else {
        // For edit, only check section name
        if (!formData.name) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please enter a section name.',
                confirmButtonColor: '#007bff'
            });
            return;
        }
        formData.id = editingSectionId;
    }
    
    const url = isEdit ? API_ROUTES.updateSection.replace(':id', editingSectionId) : API_ROUTES.createSection;
    const method = isEdit ? 'PUT' : 'POST';
    
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

// Toggle section status (activate/deactivate)
function toggleSectionStatus(sectionId, action) {
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

    const actionText = action === 'activate' ? 'activate' : 'deactivate';
    const newStatus = action === 'activate' ? 1 : 0;

    Swal.fire({
        title: `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} Section?`,
        text: `Are you sure you want to ${actionText} section "${section.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#007bff',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, ${actionText} it!`,
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: TOGGLE_STATUS_URL,
                type: 'POST',
                data: {
                    section_id: sectionId,
                    is_active: newStatus,
                    _token: $('input[name="_token"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // Reload sections to update both tables
                        loadSections();
                    }
                },
                error: function(xhr) {
                    let errorMessage = `Failed to ${actionText} section.`;
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMessage,
                        confirmButtonColor: '#007bff'
                    });
                }
            });
        }
    });
}

// Reset form
function resetSectionForm() {
    $('#sectionForm')[0].reset();
    $('#sectionId').val('');
    $('#sectionModalTitle').html('<i class="fas fa-plus"></i> Create New Section');
    editingSectionId = null;
    
    // Reset field states
    $('#strandSelect').prop('disabled', false).removeClass('bg-light');
    $('#levelSelect').prop('disabled', false).removeClass('bg-light');
    $('#strandRequired').show();
    $('#levelRequired').show();
    $('#strandEditNote').addClass('d-none');
    $('#levelEditNote').addClass('d-none');
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