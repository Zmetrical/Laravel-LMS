$(document).ready(function () {
    let selectedSectionId = null;
    let sections = [];
    let levels = [];
    let strands = [];

    // Initialize
    loadSections();

    // Load sections
    function loadSections() {
        $.ajax({
            url: API_ROUTES.getSections,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    sections = response.sections || response.data || [];
                    levels = response.levels || [];
                    strands = response.strands || [];

                    populateFilters();
                    renderSectionsList();
                }
            },
            error: function (xhr) {
                showError('Failed to load sections');
            }
        });
    }

    // Populate filter dropdowns
    function populateFilters() {
        // Levels
        $('#levelFilter').html('<option value="">All Levels</option>');
        levels.forEach(level => {
            $('#levelFilter').append(`<option value="${level.id}">${level.name}</option>`);
        });

        // Strands
        $('#strandFilter').html('<option value="">All Strands</option>');
        strands.forEach(strand => {
            $('#strandFilter').append(`<option value="${strand.id}">${strand.name}</option>`);
        });
    }

    // Render sections list
    function renderSectionsList() {
        const search = $('#sectionSearch').val().toLowerCase();
        const levelFilter = $('#levelFilter').val();
        const strandFilter = $('#strandFilter').val();

        let filtered = sections.filter(section => {
            const matchSearch = !search ||
                section.code.toLowerCase().includes(search) ||
                section.name.toLowerCase().includes(search);
            const matchLevel = !levelFilter || section.level_id == levelFilter;
            const matchStrand = !strandFilter || section.strand_id == strandFilter;
            return matchSearch && matchLevel && matchStrand;
        });

        let html = '';
        if (filtered.length === 0) {
            html = '<div class="text-center py-3 text-muted">No sections found</div>';
        } else {
            filtered.forEach(section => {
                const isActive = selectedSectionId === section.id;
                html += `
                    <div class="section-item ${isActive ? 'active' : ''}" data-section-id="${section.id}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>${section.code}</strong>
                                <div class="small text-muted">${section.full_name}</div>
                            </div>
                            <span class="badge badge-primary">${section.class_count} classes</span>
                        </div>
                    </div>
                `;
            });
        }

        $('#sectionsListContainer').html(html);
    }

    // Section item click
    $(document).on('click', '.section-item', function () {
        selectedSectionId = $(this).data('section-id');
        $('.section-item').removeClass('active');
        $(this).addClass('active');

        $('#enrollClassBtn').prop('disabled', false);
        loadSectionClasses(selectedSectionId);
    });

    // Load section classes
    function loadSectionClasses(sectionId) {
        $('#noSectionSelected').hide();
        $('#enrolledClassesContainer').show();
        $('#classesLoadingIndicator').show();
        $('#classesTableContainer').hide();
        $('#noClassesMessage').hide();

        const url = API_ROUTES.getSectionClasses.replace(':id', sectionId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    displaySectionInfo(response.section);
                    displayEnrolledClasses(response.classes);
                }
            },
            error: function (xhr) {
                showError('Failed to load section classes');
                $('#classesLoadingIndicator').hide();
            }
        });
    }

    // Display section info
    function displaySectionInfo(section) {
        $('#selectedSectionName').text(section.full_name);
        $('#selectedSectionDetails').html(`
            <strong>Code:</strong> ${section.code} | 
            <strong>Level:</strong> ${section.level} | 
            <strong>Strand:</strong> ${section.strand}
        `);
        $('#sectionInfoContainer').show();
    }

    // Display enrolled classes
    function displayEnrolledClasses(classes) {
        $('#classesLoadingIndicator').hide();

        if (classes.length === 0) {
            $('#noClassesMessage').show();
            $('#classesTableContainer').hide();
            return;
        }

        let html = '';
        classes.forEach(cls => {
            const teachers = cls.teachers.map(t => t.name).join(', ') || 'No teacher assigned';
            html += `
                <tr>
                    <td>${cls.class_code}</td>
                    <td>${cls.class_name}</td>
                    <td>${teachers}</td>
                    <td class="text-center">
                        <button class="btn btn-danger btn-sm remove-class-btn" 
                                data-class-id="${cls.id}" 
                                data-class-name="${cls.class_name}">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        $('#enrolledClassesBody').html(html);
        $('#classesTableContainer').show();
        $('#noClassesMessage').hide();
    }

    // Enroll class button
    $('#enrollClassBtn').click(function () {
        if (!selectedSectionId) return;
        loadAvailableClasses();
        $('#enrollClassModal').modal('show');
    });

    // Load available classes
    function loadAvailableClasses() {
        $('#availableClassesSelect').html('<option value="">Loading...</option>');
        $('#classInfoContainer').hide();
        $('#confirmEnrollBtn').prop('disabled', true);

        const url = API_ROUTES.getAvailableClasses.replace(':id', selectedSectionId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function (response) {
                if (response.success) {
                    let html = '<option value="">-- Select Class --</option>';
                    response.classes.forEach(cls => {
                        html += `<option value="${cls.id}" 
                                        data-code="${cls.class_code}" 
                                        data-teachers="${cls.teachers}">
                                    ${cls.class_code} - ${cls.class_name}
                                 </option>`;
                    });
                    $('#availableClassesSelect').html(html);
                }
            },
            error: function (xhr) {
                $('#availableClassesSelect').html('<option value="">Error loading classes</option>');
                showError('Failed to load available classes');
            }
        });
    }

    // Available classes select change
    $('#availableClassesSelect').change(function () {
        const selected = $(this).find(':selected');
        if (selected.val()) {
            $('#modalClassCode').text(selected.data('code'));
            $('#modalClassTeachers').text(selected.data('teachers') || 'No teacher assigned');
            $('#classInfoContainer').show();
            $('#confirmEnrollBtn').prop('disabled', false);
        } else {
            $('#classInfoContainer').hide();
            $('#confirmEnrollBtn').prop('disabled', true);
        }
    });

    // Confirm enroll
    $('#confirmEnrollBtn').click(function () {
        const classId = $('#availableClassesSelect').val();
        if (!classId || !selectedSectionId) return;

        const url = API_ROUTES.enrollClass.replace(':id', selectedSectionId);

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                class_id: classId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    showSuccess(response.message);
                    $('#enrollClassModal').modal('hide');
                    loadSectionClasses(selectedSectionId);
                    loadSections(); // Refresh section list to update counts
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to enroll class';
                showError(message);
            }
        });
    });

    // Remove class
    $(document).on('click', '.remove-class-btn', function () {
        const classId = $(this).data('class-id');
        const className = $(this).data('class-name');

        if (!confirm(`Are you sure you want to remove "${className}" from this section?`)) {
            return;
        }

        const url = API_ROUTES.removeClass
            .replace(':sectionId', selectedSectionId)
            .replace(':classId', classId);

        $.ajax({
            url: url,
            method: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                if (response.success) {
                    showSuccess(response.message);
                    loadSectionClasses(selectedSectionId);
                    loadSections(); // Refresh section list to update counts
                }
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'Failed to remove class';
                showError(message);
            }
        });
    });

    // Filters
    $('#sectionSearch, #levelFilter, #strandFilter').on('input change', function () {
        renderSectionsList();
    });

    // Toast notifications
    function showSuccess(message) {
        toastr.success(message);
    }

    function showError(message) {
        toastr.error(message);
    }
});

// Add custom CSS for section items
$('<style>')
    .prop('type', 'text/css')
    .html(`
        .section-item {
            padding: 10px;
            margin-bottom: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .section-item:hover {
            background-color: #f8f9fa;
            border-color: #17a2b8;
        }
        .section-item.active {
            background-color: #d1ecf1;
            border-color: #17a2b8;
            font-weight: bold;
        }
    `)
    .appendTo('head');