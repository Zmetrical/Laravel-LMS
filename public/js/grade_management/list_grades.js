$(document).ready(function() {
    let currentGradesData = [];

    // Load initial data
    loadClasses();

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
     * Search grades
     */
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        
        const searchData = {
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
        searchData.semester_id = activeSemester.id;

        $.ajax({
            url: API_ROUTES.searchGrades,
            method: 'GET',
            data: searchData,
            success: function(response) {
                if (response.success) {
                    currentGradesData = response.data;
                    displayResults(response.data);
                    updateAverageGrade(response.stats.average_grade);
                    
                    // Enable export button if data exists
                    $('#exportBtn').prop('disabled', response.data.length === 0);
                    
                    if (response.data.length === 0) {
                        toastr.info('No records found matching your search criteria');
                    } else {
                        toastr.success(`Found ${response.data.length} student(s)`);
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
        
        const tbody = $('#gradesTableBody');
        tbody.empty();

        if (data.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="11" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p>No students found</p>
                    </td>
                </tr>
            `);
            $('#recordsCount').text('No records');
            $('#gradesTableFooter').hide();
            return;
        }

        data.forEach(function(grade) {
            const row = $('<tr>');
            
            // Add light background for students without grades
            if (!grade.has_grade) {
            }
            
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
                    <strong>${escapeHtml(grade.class_name)}</strong>
                    <br><small class="text-muted">${escapeHtml(grade.class_code)}</small>
                </td>
            `);
            
            // Section with strand and level
            const sectionInfo = grade.section_name ? 
                `${grade.section_name}${grade.strand_code ? ' - ' + grade.strand_code : ''}` : 
                '<span class="text-muted">N/A</span>';
            row.append(`<td><small>${sectionInfo}</small></td>`);
            
            // Student Type
            const typeClass = grade.student_type === 'regular' ? 'primary' : 'secondary';
            row.append(`
                <td class="text-center">
                    <span class="badge badge-${typeClass}">
                        ${grade.student_type.toUpperCase()}
                    </span>
                </td>
            `);
            
            // Component Scores
            if (grade.has_grade) {
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
                        <button class="btn btn-sm btn-info view-details-btn" data-id="${grade.grade_id}">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                `);
            } else {
                // No grade yet
                row.append(`<td class="text-center text-muted">-</td>`);
                row.append(`<td class="text-center text-muted">-</td>`);
                row.append(`<td class="text-center text-muted">-</td>`);
                row.append(`
                    <td class="text-center">
                        <span class="text-muted">-</span>
                    </td>
                `);
                row.append(`
                    <td class="text-center">
                        <span class="badge badge-secondary">NO GRADE</span>
                    </td>
                `);
                row.append(`
                    <td class="text-center">
                        <span class="text-muted">-</span>
                    </td>
                `);
            }
            
            tbody.append(row);
        });

        $('#recordsCount').text(`${data.length} student${data.length !== 1 ? 's' : ''}`);
        $('#gradesTableFooter').show();
    }

    /**
     * Update average grade display
     */
    function updateAverageGrade(average) {
        const avgDisplay = $('#averageGradeDisplay');
        avgDisplay.text(average > 0 ? average : 'N/A');
        
        if (average > 0) {
            const gradeClass = getGradeClass(average);
            avgDisplay.removeClass('text-primary text-success text-danger');
            avgDisplay.addClass(`text-${gradeClass}`);
        } else {
            avgDisplay.removeClass('text-success text-danger');
            avgDisplay.addClass('text-primary');
        }
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
        
        const typeClass = grade.student_type === 'regular' ? 'primary' : 'secondary';
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
        $('#exportBtn').prop('disabled', true);
    });

    /**
     * Helper functions
     */
    function getGradeClass(grade) {
        if (!grade) return 'muted';
        if (grade >= 90) return 'success';
        if (grade >= 75) return 'primary';
        return 'danger';
    }

    function getRemarksClass(remarks) {
        if (!remarks) return 'secondary';
        switch(remarks) {
            case 'PASSED': return 'success';
            case 'FAILED': return 'danger';
            case 'INC': return 'warning';
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
                    <p class="mt-2 mb-0">Loading student records...</p>
                </td>
            </tr>
        `);
        $('#gradesTableFooter').hide();
    }

    function hideLoading() {
        $('#gradesTableBody').empty();
    }

    // Export button (placeholder for future implementation)
    $('#exportBtn').on('click', function() {
        toastr.info('Export functionality will be implemented soon');
    });
});