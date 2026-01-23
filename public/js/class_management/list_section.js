console.log("list_section.js loaded");

let sections = [];
let filteredSections = [];
let strands = [];
let levels = [];
let semesters = [];
let editingSectionId = null;
let currentViewingSectionId = null;
let currentViewingSectionCode = '';
let currentSemesterId = null;

// Initialize on page load
$(document).ready(function() {
    loadStrands();
    loadLevels();
    loadSemesters();
    loadSections();
    setupEventListeners();
});

function setupEventListeners() {
    // Form submissions
    $('#sectionForm').on('submit', function(e) {
        e.preventDefault();
        saveSection();
    });

    $('#addClassForm').on('submit', function(e) {
        e.preventDefault();
        assignClassToSection();
    });

    // Auto-uppercase section name
    $('#sectionName').on('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Modal reset on close
    $('#sectionModal').on('hidden.bs.modal', function() {
        resetSectionForm();
    });

    $('#addClassModal').on('hidden.bs.modal', function() {
        resetAddClassForm();
    });

    // Filter changes
    $('#filterSemester, #filterStrand, #filterLevel').on('change', function() {
        applyFilters();
    });

    // Clear filters button
    $('#clearFilters').on('click', function() {
        $('#filterSemester').val('');
        $('#filterStrand').val('');
        $('#filterLevel').val('');
        applyFilters();
    });

    // Add class button
    $('#addClassBtn').on('click', function() {
        openAddClassModal();
    });
}

// Load semesters
function loadSemesters() {
    $.ajax({
        url: API_ROUTES.getSemesters,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                semesters = response.data;
                updateSemesterSelects();
            }
        },
        error: function(xhr) {
            console.error('Failed to load semesters:', xhr);
        }
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
    const filters = {
        semester: $('#filterSemester').val(),
        strand: $('#filterStrand').val(),
        level: $('#filterLevel').val()
    };

    $.ajax({
        url: API_ROUTES.getSections,
        type: 'GET',
        data: filters,
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
    loadSections();
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
        const semesterFilter = $('#filterSemester').val();
        const countLabel = semesterFilter ? `${classCount}` : `${classCount}`;
        
        tbody.append(`
            <tr>
                <td>${index + 1}</td>
                <td><strong>${escapeHtml(section.name)}</strong></td>
                <td>${escapeHtml(section.strand_code)}</td>
                <td>${escapeHtml(section.level_name)}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-secondary" 
                            onclick="viewSectionClasses(${section.id}, '${escapeHtml(section.code)}')" 
                            title="Manage Classes">
                        <i class="fas fa-book"></i> ${countLabel}
                    </button>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" 
                            onclick="editSection(${section.id})" 
                            title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

// Update semester select dropdowns
function updateSemesterSelects() {
    const filterSelect = $('#filterSemester');
    
    const filterValue = filterSelect.val();

    filterSelect.find('option:not(:first)').remove();

    // Find active semester
    let activeSemester = semesters.find(s => s.status === 'active');

    semesters.forEach(semester => {
        const label = `${semester.school_year_code} - ${semester.name}`;
        const badge = semester.status === 'active' ? ' (Active)' : '';
        
        filterSelect.append(`<option value="${semester.id}">${label}${badge}</option>`);
    });

    // Set active semester as default if no filter selected
    if (!filterValue && activeSemester) {
        filterSelect.val(activeSemester.id);
    } else if (filterValue) {
        filterSelect.val(filterValue);
    }
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
    
    $('#sectionId').val(section.id);
    $('#sectionName').val(section.name);
    $('#strandSelect').val(section.strand_id);
    $('#levelSelect').val(section.level_id);
    
    $('#sectionModalTitle').html('<i class="fas fa-edit"></i> Edit Section');
    
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

    // Get current semester filter
    const semesterId = $('#filterSemester').val();
    
    if (!semesterId) {
        Swal.fire({
            icon: 'warning',
            title: 'No Semester Selected',
            text: 'Please select a semester from the filter above first.',
            confirmButtonColor: '#007bff'
        });
        return;
    }

    currentViewingSectionId = sectionId;
    currentViewingSectionCode = sectionCode;
    currentSemesterId = semesterId;

    // Get semester name for display
    const semester = semesters.find(s => s.id == semesterId);
    const semesterLabel = semester ? `${semester.school_year_code} - ${semester.name}` : '';

    $('#classesModalTitle').html(`<i class="fas fa-book"></i> ${sectionCode} - Manage Classes`);
    $('#currentSemesterLabel').text(semesterLabel);
    
    loadSectionClasses(sectionId, semesterId);
    
    $('#viewClassesModal').modal('show');
}

// Load section classes for specific semester
function loadSectionClasses(sectionId, semesterId) {
    $('#classesTableBody').html(`
        <tr>
            <td colspan="5" class="text-center">
                <i class="fas fa-spinner fa-spin"></i> Loading classes...
            </td>
        </tr>
    `);
    
    const url = API_ROUTES.getSectionClasses.replace(':id', sectionId);
    
    $.ajax({
        url: url,
        type: 'GET',
        data: { semester_id: semesterId },
        success: function(response) {
            if (response.success) {
                renderClassesTable(response.data);
            }
        },
        error: function(xhr) {
            console.error('Failed to load classes:', xhr);
            $('#classesTableBody').html(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
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
                <td colspan="5" class="text-center text-muted py-4">
                    <i class="fas fa-info-circle"></i> No classes assigned for this semester.
                </td>
            </tr>
        `);
        return;
    }
    
    classes.forEach((classItem, index) => {
        tbody.append(`
            <tr>
                <td>${index + 1}</td>
                <td><strong>${escapeHtml(classItem.class_code)}</strong></td>
                <td>${escapeHtml(classItem.class_name)}</td>
                <td>
                    <small>
                        WW: ${classItem.ww_perc}% | 
                        PT: ${classItem.pt_perc}% | 
                        QA: ${classItem.qa_perce}%
                    </small>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-danger" 
                            onclick="removeClassFromSection(${classItem.matrix_id})"
                            title="Remove">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);
    });
}

// Open add class modal
function openAddClassModal() {
    if (!currentSemesterId) {
        Swal.fire({
            icon: 'warning',
            title: 'No Semester Selected',
            text: 'Please select a semester first.',
            confirmButtonColor: '#007bff'
        });
        return;
    }

    $('#addClassSectionId').val(currentViewingSectionId);
    $('#addClassSemesterId').val(currentSemesterId);
    
    loadAvailableClasses(currentViewingSectionId, currentSemesterId);
    
    $('#addClassModal').modal('show');
}

// Load available classes for section
function loadAvailableClasses(sectionId, semesterId) {
    $('#availableClasses').html('<option value="">Loading...</option>');
    
    const url = API_ROUTES.getAvailableClasses.replace(':id', sectionId);
    
    $.ajax({
        url: url,
        type: 'GET',
        data: { semester_id: semesterId },
        success: function(response) {
            if (response.success) {
                const select = $('#availableClasses');
                select.empty();
                select.append('<option value="">Choose a class...</option>');
                
                if (response.data.length === 0) {
                    select.append('<option value="" disabled>No available classes</option>');
                } else {
                    response.data.forEach(classItem => {
                        select.append(`<option value="${classItem.id}">${classItem.class_code} - ${classItem.class_name}</option>`);
                    });
                }
            }
        },
        error: function(xhr) {
            console.error('Failed to load available classes:', xhr);
            $('#availableClasses').html('<option value="">Failed to load classes</option>');
        }
    });
}

// Assign class to section
function assignClassToSection() {
    const formData = {
        section_id: $('#addClassSectionId').val(),
        class_id: $('#availableClasses').val(),
        semester_id: $('#addClassSemesterId').val(),
        _token: $('input[name="_token"]').val()
    };
    
    if (!formData.class_id) {
        Swal.fire({
            icon: 'warning',
            title: 'Validation Error',
            text: 'Please select a class.',
            confirmButtonColor: '#007bff'
        });
        return;
    }
    
    const submitBtn = $('#addClassForm button[type="submit"]');
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Adding...');
    
    $.ajax({
        url: API_ROUTES.assignClass,
        type: 'POST',
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
                
                $('#addClassModal').modal('hide');
                loadSectionClasses(currentViewingSectionId, formData.semester_id);
                loadSections(); // Refresh count
            }
        },
        error: function(xhr) {
            let errorMessage = 'Failed to assign class.';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error',
                html: errorMessage,
                confirmButtonColor: '#007bff'
            });
        },
        complete: function() {
            submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Add Class');
        }
    });
}

// Remove class from section
function removeClassFromSection(matrixId) {
    Swal.fire({
        title: 'Remove Class?',
        text: 'Are you sure you want to remove this class from the section?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = API_ROUTES.removeClass.replace(':id', matrixId);
            
            $.ajax({
                url: url,
                type: 'DELETE',
                data: { _token: $('input[name="_token"]').val() },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Removed!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        loadSectionClasses(currentViewingSectionId, currentSemesterId);
                        loadSections(); // Refresh count
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Failed to remove class.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        html: errorMessage,
                        confirmButtonColor: '#007bff'
                    });
                }
            });
        }
    });
}

// Reset forms
function resetSectionForm() {
    $('#sectionForm')[0].reset();
    $('#sectionId').val('');
    $('#sectionModalTitle').html('<i class="fas fa-plus"></i> Create New Section');
    editingSectionId = null;
}

function resetAddClassForm() {
    $('#addClassForm')[0].reset();
    $('#availableClasses').html('<option value="">Choose a class...</option>');
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