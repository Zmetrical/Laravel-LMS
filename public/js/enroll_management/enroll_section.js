let sections = [];
let filters = {
    grade: '',
    strand: '',
    search: ''
};

$(document).ready(function () {
    setupEventListeners();
    loadSections();
});

function setupEventListeners() {
    // Filter change events
    $('#sectionGradeFilter').change(function () {
        filters.grade = $(this).val();
        loadSections();
    });

    $('#sectionStrandFilter').change(function () {
        filters.strand = $(this).val();
        loadSections();
    });

    $('#sectionSearchInput').on('keyup', debounce(function () {
        filters.search = $(this).val();
        loadSections();
    }, 500));

    $('#resetFiltersBtn').click(function () {
        filters = { grade: '', strand: '', search: '' };
        $('#sectionGradeFilter').val('');
        $('#sectionStrandFilter').val('');
        $('#sectionSearchInput').val('');
        loadSections();
    });
}

function loadSections() {
    showLoading(true);

    $.ajax({
        url: API_ROUTES.getSections,
        method: 'GET',
        data: filters,
        success: function (response) {
            if (response.success) {
                sections = response.data;
                renderSections(sections);
                populateFilters(sections);
            } else {
                showError('Failed to load sections');
            }
        },
        error: function (xhr) {
            showError('Error loading sections: ' + (xhr.responseJSON?.message || 'Unknown error'));
        },
        complete: function () {
            showLoading(false);
        }
    });
}

function renderSections(data) {
    const container = $('#sectionsContainer');
    container.empty();

    if (data.length === 0) {
        container.html(`
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> No sections found matching your filters.
                </div>
            </div>
        `);
        return;
    }

    data.forEach(section => {
        const strandColor = getStrandColor(section.strand_code);

        container.append(`
            <div class="col-lg-4 col-md-6">
                <div class="card card-outline card-${strandColor}">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-users"></i> 
                            <strong>${section.grade} - ${section.strand_code} ${section.name}</strong>
                        </h3>
                        <div class="card-tools">
                            <span class="badge badge-${strandColor}">${section.student_count} Students</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="mb-2">
                            <strong><i class="fas fa-layer-group"></i> Strand:</strong> 
                            ${section.strand}
                        </p>
                        <p class="mb-2">
                            <strong><i class="fas fa-book"></i> Enrolled Classes:</strong> 
                            ${section.class_count}
                        </p>
                        <p class="mb-0">
                            <strong><i class="fas fa-code"></i> Section Code:</strong> 
                            ${section.code}
                        </p>
                    </div>
                    <div class="card-footer">
                        <button class="btn btn-sm btn-primary btn-block" onclick="viewSectionDetails(${section.id})">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </div>
                </div>
            </div>
        `);
    });
}

function populateFilters(data) {
    // Populate grade filter
    const grades = [...new Set(data.map(s => s.level_id))];
    const gradeSelect = $('#sectionGradeFilter');
    const currentGrade = gradeSelect.val();
    
    gradeSelect.find('option:not(:first)').remove();
    grades.forEach(levelId => {
        const section = data.find(s => s.level_id === levelId);
        gradeSelect.append(`<option value="${levelId}">${section.grade}</option>`);
    });
    gradeSelect.val(currentGrade);

    // Populate strand filter
    const strands = [...new Set(data.map(s => s.strand_id))];
    const strandSelect = $('#sectionStrandFilter');
    const currentStrand = strandSelect.val();
    
    strandSelect.find('option:not(:first)').remove();
    strands.forEach(strandId => {
        const section = data.find(s => s.strand_id === strandId);
        strandSelect.append(`<option value="${strandId}">${section.strand_code}</option>`);
    });
    strandSelect.val(currentStrand);
}

function viewSectionDetails(sectionId) {
    const detailsUrl = API_ROUTES.getDetails.replace(':id', sectionId);

    $.ajax({
        url: detailsUrl,
        method: 'GET',
        beforeSend: function () {
            $('#sectionDetailsBody').html(`
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="mt-2">Loading details...</p>
                </div>
            `);
            $('#sectionDetailsModal').modal('show');
        },
        success: function (response) {
            if (response.success) {
                renderSectionDetails(response.data);
            } else {
                $('#sectionDetailsBody').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Failed to load section details
                    </div>
                `);
            }
        },
        error: function (xhr) {
            $('#sectionDetailsBody').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> Error: ${xhr.responseJSON?.message || 'Unknown error'}
                </div>
            `);
        }
    });
}

function renderSectionDetails(section) {
    const strandColor = getStrandColor(section.strand_code);

    let html = `
        <div class="row">
            <div class="col-md-12">
                <h4><i class="fas fa-users"></i> ${section.full_name}</h4>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Section Code:</strong> ${section.code}</p>
                        <p><strong>Grade Level:</strong> ${section.grade}</p>
                        <p><strong>Strand:</strong> <span class="badge badge-${strandColor}">${section.strand_code}</span> ${section.strand}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Total Students:</strong> <span class="badge badge-info">${section.student_count}</span></p>
                        <p><strong>Enrolled Classes:</strong> <span class="badge badge-primary">${section.classes.length}</span></p>
                    </div>
                </div>
                <hr>
                <h5><i class="fas fa-book"></i> Class List:</h5>
                ${section.classes.length > 0 ? `
                    <ul class="list-group">
                        ${section.classes.map(cls => `
                            <li class="list-group-item">
                                <i class="fas fa-check-circle text-success"></i> 
                                <strong>${cls.name}</strong>
                                <span class="badge badge-secondary float-right">${cls.code}</span>
                            </li>
                        `).join('')}
                    </ul>
                ` : `
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle"></i> No classes enrolled yet
                    </div>
                `}
            </div>
        </div>
    `;

    $('#sectionDetailsBody').html(html);
}

function getStrandColor(strandCode) {
    const colors = {
        'STEM': 'primary',
        'ABM': 'success',
        'HUMSS': 'info',
        'GAS': 'warning'
    };
    return colors[strandCode] || 'secondary';
}

function showLoading(show) {
    if (show) {
        $('#loadingIndicator').show();
        $('#sectionsContainer').hide();
    } else {
        $('#loadingIndicator').hide();
        $('#sectionsContainer').show();
    }
}

function showError(message) {
    $('#sectionsContainer').html(`
        <div class="col-12">
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert">&times;</button>
                <i class="fas fa-exclamation-triangle"></i> ${message}
            </div>
        </div>
    `);
}

// Utility: Debounce function for search input
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}