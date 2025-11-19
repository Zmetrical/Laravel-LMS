$(document).ready(function() {
    let currentGradesData = [];

    // Load initial data
    loadSemesters();
    loadClasses();

    /**
     * Load semesters for filter
     */
    function loadSemesters() {
        $.ajax({
            url: API_ROUTES.getSemesters,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const select = $('#semesterFilter');
                    select.empty().append('<option value="">-- Select Semester --</option>');
                    
                    response.data.forEach(function(semester) {
                        const label = `${semester.school_year_code} - ${semester.name}`;
                        const isActive = semester.status === 'active';
                        const option = $('<option>')
                            .val(semester.id)
                            .text(label + (isActive ? ' (Active)' : ''));
                        
                        if (isActive) {
                            option.attr('selected', true);
                            updateActiveSemesterDisplay(label);
                        }
                        
                        select.append(option);
                    });
                    
                    select.trigger('change');
                }
            },
            error: function() {
                toastr.error('Failed to load semesters');
            }
        });
    }

    /**
     * Load classes for filter
     */
    function loadClasses() {
        $.ajax({
            url: API_ROUTES.getClasses,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const select = $('#classFilter');
                    select.empty().append('<option value="">All Classes</option>');
                    
                    response.data.forEach(function(cls) {
                        select.append(
                            $('<option>')
                                .val(cls.class_code)
                                .text(`${cls.class_code} - ${cls.class_name}`)
                        );
                    });
                }
            },
            error: function() {
                toastr.error('Failed to load classes');
            }
        });
    }

    /**
     * Update active semester display
     */
    function updateActiveSemesterDisplay(semesterLabel) {
        $('#activeSemesterDisplay').text(semesterLabel);
    }

    /**
     * Search grades
     */
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        
        const semesterId = $('#semesterFilter').val();
        
        if (!semesterId) {
            toastr.warning('Please select a semester');
            return;
        }

        const searchData = {
            semester_id: semesterId,
            class_code: $('#classFilter').val(),
            status_filter: $('#statusFilter').val(),
            search: $('#searchInput').val().trim()
        };

        performSearch(searchData);
    });

    /**
     * Perform search AJAX
     */
    function performSearch(searchData) {
        showLoading();
        
        $.ajax({
            url: API_ROUTES.searchGrades,
            method: 'GET',
            data: searchData,
            success: function(response) {
                if (response.success) {
                    currentGradesData = response.data;
                    displayResults(response.data);
                    updateStatistics(response.stats);
                    
                    // Enable export button if data exists
                    $('#exportBtn').prop('disabled', response.data.length === 0);
                    
                    if (response.data.length === 0) {
                        toastr.info('No records found matching your search criteria');
                    }
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to search grades';
                toastr.error(message);
                hideLoading();
            }
        });
    }

    /**
     * Display search results
     */
    function displayResults(data) {
        $('#noSearchYet').hide();
        $('#resultsSection').show();
        $('#statsCards').show();
        
        const tbody = $('#gradesTableBody');
        tbody.empty();

        if (data.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="11" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>No grade records found</p>
                    </td>
                </tr>
            `);
            $('#recordsCount').text('No records');
            return;
        }

        data.forEach(function(grade) {
            const row = $('<tr>');
            
            // Student Number
            row.append(`<td>${escapeHtml(grade.student_number)}</td>`);
            
            // Student Name
            row.append(`
                <td>
                    <strong>${escapeHtml(grade.full_name)}</strong>
                    ${grade.section_code ? `<br><small class="text-muted">${escapeHtml(grade.section_code)}</small>` : ''}
                </td>
            `);
            
            // Class
            row.append(`
                <td>
                    <strong>${escapeHtml(grade.class_code)}</strong>
                    <br><small class="text-muted">${escapeHtml(grade.class_name)}</small>
                </td>
            `);
            
            // Section with strand and level
            const sectionInfo = grade.section_name ? 
                `${grade.section_name}${grade.strand_code ? ' - ' + grade.strand_code : ''}${grade.level_name ? ' (' + grade.level_name + ')' : ''}` : 
                '<span class="text-muted">N/A</span>';
            row.append(`<td><small>${sectionInfo}</small></td>`);
            
            // Student Type
            const typeClass = grade.student_type === 'regular' ? 'success' : 'warning';
            row.append(`
                <td class="text-center">
                    <span class="badge badge-${typeClass}">
                        ${grade.student_type.toUpperCase()}
                    </span>
                </td>
            `);
            
            // Component Scores
            row.append(`<td class="text-center">${grade.ww_score || '-'}</td>`);
            row.append(`<td class="text-center">${grade.pt_score || '-'}</td>`);
            row.append(`<td class="text-center">${grade.qa_score || '-'}</td>`);
            
            // Final Grade
            const gradeClass = getGradeClass(grade.final_grade);
            row.append(`
                <td class="text-center">
                    <strong class="text-${gradeClass}">${grade.final_grade}</strong>
                    ${grade.is_locked ? '<br><i class="fas fa-lock text-muted" title="Locked"></i>' : ''}
                </td>
            `);
            
            // Remarks
            const remarksClass = getRemarksClass(grade.remarks);
            row.append(`
                <td class="text-center">
                    <span class="badge badge-${remarksClass}">
                        ${grade.remarks}
                    </span>
                </td>
            `);
            
            // Actions
            row.append(`
                <td class="text-center">
                    <button class="btn btn-sm btn-info view-details-btn" data-id="${grade.id}">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            `);
            
            tbody.append(row);
        });

        $('#recordsCount').text(`${data.length} record${data.length !== 1 ? 's' : ''}`);
    }

    /**
     * Update statistics cards
     */
    function updateStatistics(stats) {
        $('#totalRecords').text(stats.total_records);
        $('#passedCount').text(stats.passed);
        $('#failedCount').text(stats.failed);
        $('#incCount').text(stats.inc);
        $('#drpCount').text(stats.drp + stats.w);
        $('#averageGrade').text(stats.average_grade);
    }

    /**
     * View grade details
     */
    $(document).on('click', '.view-details-btn', function() {
        const gradeId = $(this).data('id');
        loadGradeDetails(gradeId);
    });

    /**
     * Load grade details
     */
    function loadGradeDetails(gradeId) {
        const url = API_ROUTES.getGradeDetails.replace(':id', gradeId);
        
        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    displayGradeDetails(response.data, response.components);
                    $('#gradeDetailsModal').modal('show');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to load grade details';
                toastr.error(message);
            }
        });
    }

    /**
     * Display grade details in modal
     */
    function displayGradeDetails(grade, components) {
        // Student info
        $('#detailStudentNumber').text(grade.student_number);
        $('#detailStudentName').text(
            `${grade.first_name} ${grade.middle_name ? grade.middle_name + ' ' : ''}${grade.last_name}`
        );
        
        const typeClass = grade.student_type === 'regular' ? 'success' : 'warning';
        $('#detailStudentType').html(
            `<span class="badge badge-${typeClass}">${grade.student_type.toUpperCase()}</span>`
        );
        
        const sectionText = grade.section_name ? 
            `${grade.section_name} - ${grade.strand_code || ''} (${grade.level_name || ''})` : 
            'N/A';
        $('#detailSection').text(sectionText);
        
        // Class info
        $('#detailClass').html(`<strong>${grade.class_code}</strong> - ${grade.class_name}`);
        $('#detailComputedBy').text(grade.computed_by_name || 'System');
        $('#detailComputedAt').text(formatDateTime(grade.computed_at));
        
        const lockStatus = grade.is_locked ? 
            '<span class="badge badge-secondary"><i class="fas fa-lock"></i> Locked</span>' : 
            '<span class="badge badge-success"><i class="fas fa-unlock"></i> Unlocked</span>';
        $('#detailStatus').html(lockStatus);
        
        // Grade breakdown
        $('#detailWW').text(grade.ww_score || '0.00');
        $('#detailWWPerc').text(grade.ww_percentage);
        $('#detailPT').text(grade.pt_score || '0.00');
        $('#detailPTPerc').text(grade.pt_percentage);
        $('#detailQA').text(grade.qa_score || '0.00');
        $('#detailQAPerc').text(grade.qa_percentage);
        
        // Final grade
        const gradeClass = getGradeClass(grade.final_grade);
        $('#detailFinalGrade').html(`<span class="text-${gradeClass}">${grade.final_grade}</span>`);
        
        const remarksClass = getRemarksClass(grade.remarks);
        $('#detailRemarks').html(`<span class="badge badge-${remarksClass} badge-lg">${grade.remarks}</span>`);
        
        // Component breakdown
        if (components && components.length > 0) {
            $('#componentsSection').show();
            const tbody = $('#componentsTableBody');
            tbody.empty();
            
            components.forEach(function(comp) {
                const percentage = comp.max_score > 0 ? 
                    ((comp.score / comp.max_score) * 100).toFixed(2) : 
                    '0.00';
                
                tbody.append(`
                    <tr>
                        <td><span class="badge badge-secondary">${comp.component_type}</span></td>
                        <td>${escapeHtml(comp.item_name || 'N/A')}</td>
                        <td class="text-center">${comp.score || '-'}</td>
                        <td class="text-center">${comp.max_score}</td>
                        <td class="text-center">${percentage}%</td>
                        <td>${formatDateTime(comp.created_at)}</td>
                    </tr>
                `);
            });
        } else {
            $('#componentsSection').hide();
        }
    }

    /**
     * Reset filters
     */
    $('#resetFiltersBtn').on('click', function() {
        $('#searchForm')[0].reset();
        $('#classFilter').val('').trigger('change');
        $('#statusFilter').val('all');
        $('#searchInput').val('');
        
        // Hide results
        $('#noSearchYet').show();
        $('#resultsSection').hide();
        $('#statsCards').hide();
        $('#exportBtn').prop('disabled', true);
    });

    /**
     * Helper functions
     */
    function getGradeClass(grade) {
        if (grade >= 90) return 'success';
        if (grade >= 75) return 'primary';
        return 'danger';
    }

    function getRemarksClass(remarks) {
        switch(remarks) {
            case 'PASSED': return 'success';
            case 'FAILED': return 'danger';
            case 'INC': return 'warning';
            case 'DRP': return 'secondary';
            case 'W': return 'secondary';
            default: return 'secondary';
        }
    }

    function formatDateTime(datetime) {
        if (!datetime) return 'N/A';
        const date = new Date(datetime);
        return date.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    function showLoading() {
        $('#gradesTableBody').html(`
            <tr>
                <td colspan="11" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 mb-0">Loading grade records...</p>
                </td>
            </tr>
        `);
    }

    function hideLoading() {
        $('#gradesTableBody').empty();
    }

    // Export button (placeholder for future implementation)
    $('#exportBtn').on('click', function() {
        toastr.info('Export functionality will be implemented soon');
    });
});