$(document).ready(function() {
    let rowCounter = 0;
    let selectedSections = [];
    let totalAvailableSlots = 0;

    // Load sections when strand or level changes
    $('#strand, #level').on('change', function() {
        const strandId = $('#strand').val();
        const levelId = $('#level').val();

        $('#selectedStrand').val(strandId);
        $('#selectedLevel').val(levelId);

        if (strandId && levelId) {
            loadSections(strandId, levelId);
        } else {
            $('#sectionCapacityContainer').html(`
                <div class="alert alert-primary mb-0">
                    <i class="fas fa-info-circle"></i> Select Strand and Level to view available sections
                </div>
            `);
        }
    });

    function loadSections(strandId, levelId) {
        $.ajax({
            url: API_ROUTES.getSections,
            type: 'GET',
            data: {
                strand_id: strandId,
                level_id: levelId
            },
            success: function(response) {
                if (!response.success) {
                    Swal.fire('Error', response.message, 'error');
                    return;
                }

                const sections = response.sections;
                selectedSections = sections;
                totalAvailableSlots = sections.reduce((sum, s) => sum + s.available_slots, 0);

                if (sections.length === 0) {
                    $('#sectionCapacityContainer').html(`
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle"></i> No sections found for selected strand and level
                        </div>
                    `);
                    return;
                }

                let html = '<div class="row">';
                
                // Total capacity card
                html += `
                    <div class="col-md-12 mb-3">
                        <div class="alert alert-primary mb-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-users"></i> <strong>Total Available Slots:</strong></span>
                                <span class="badge badge-primary badge-lg">${totalAvailableSlots} slots</span>
                            </div>
                        </div>
                    </div>
                `;

                // Individual section cards
                sections.forEach(function(section) {
                    const percentage = (section.enrolled_count / section.capacity) * 100;
                    let statusBadge = 'light';
                    
                    html += `
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card section-capacity-card mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0">${section.name}</h6>
                                        <span class="badge badge-${statusBadge}">${section.available_slots}</span>
                                    </div>
                                    <small class="text-muted d-block mb-2">
                                        ${section.enrolled_count} / ${section.capacity} enrolled
                                    </small>
                                    <div class="capacity-bar">
                                        <div class="capacity-bar-fill" style="width: ${percentage}%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                html += '</div>';
                $('#sectionCapacityContainer').html(html);
            },
            error: function(xhr) {
                Swal.fire('Error', 'Failed to load sections', 'error');
            }
        });
    }

    // Generate student card
    function generateStudentCard(index) {
        rowCounter++;
        const card = `
            <div class="card student-card mb-3" data-row="${rowCounter}">
                <div class="card-header py-2">
                    <h3 class="card-title">Student #${index}</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool text-danger remove-card" title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Student Information -->
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3"><i class="fas fa-user mr-2"></i>Student Information</h6>
                            
                            <div class="form-group">
                                <label>Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" 
                                       name="students[${rowCounter}][lastName]" 
                                       placeholder="Last Name" required>
                            </div>

                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" 
                                               name="students[${rowCounter}][firstName]" 
                                               placeholder="First Name" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>M.I.</label>
                                        <input type="text" class="form-control" 
                                               name="students[${rowCounter}][middleInitial]" 
                                               placeholder="M.I." maxlength="3">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" 
                                       name="students[${rowCounter}][email]" 
                                       placeholder="student@email.com (optional)">
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Gender <span class="text-danger">*</span></label>
                                        <button type="button" class="btn btn-secondary btn-block gender-toggle" data-gender="Male">
                                            <i class="fas fa-mars"></i> Male
                                        </button>
                                        <input type="hidden" name="students[${rowCounter}][gender]" value="Male" class="gender-input">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Student Type <span class="text-danger">*</span></label>
                                        <button type="button" class="btn btn-secondary btn-block type-toggle" data-type="regular">
                                            <i class="fas fa-user-check"></i> Regular
                                        </button>
                                        <input type="hidden" name="students[${rowCounter}][studentType]" value="regular" class="type-input">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parent/Guardian Information -->
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3"><i class="fas fa-user-friends mr-2"></i>Parent/Guardian Information</h6>
                            
                            <div class="form-group">
                                <label>Parent Last Name</label>
                                <input type="text" class="form-control" 
                                       name="students[${rowCounter}][parentLastName]" 
                                       placeholder="Parent/Guardian Last Name">
                            </div>

                            <div class="form-group">
                                <label>Parent First Name</label>
                                <input type="text" class="form-control" 
                                       name="students[${rowCounter}][parentFirstName]" 
                                       placeholder="Parent/Guardian First Name">
                            </div>

                            <div class="form-group">
                                <label>Parent Email</label>
                                <input type="email" class="form-control" 
                                       name="students[${rowCounter}][parentEmail]" 
                                       placeholder="parent@email.com">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        return card;
    }

    // Add students button (unified - works for 1 or multiple)
    $('#addStudentsBtn').on('click', function() {
        const numRows = parseInt($('#numRows').val());
        
        if (!numRows || numRows < 1 || numRows > 100) {
            Swal.fire('Invalid Input', 'Please enter a number between 1 and 100', 'warning');
            return;
        }

        if (!$('#strand').val() || !$('#level').val()) {
            Swal.fire('Validation Error', 'Please select Strand and Year Level first', 'warning');
            return;
        }

        const currentRows = $('.student-card').length;
        const newTotal = currentRows + numRows;
        
        if (newTotal > totalAvailableSlots) {
            Swal.fire({
                icon: 'warning',
                title: 'Insufficient Capacity',
                html: `
                    Adding <strong>${numRows}</strong> student${numRows > 1 ? 's' : ''} would exceed available capacity.<br><br>
                    <strong>Current students:</strong> ${currentRows}<br>
                    <strong>Trying to add:</strong> ${numRows}<br>
                    <strong>Available slots:</strong> ${totalAvailableSlots}<br>
                    <strong>Would exceed by:</strong> ${newTotal - totalAvailableSlots}<br><br>
                    <small class="text-muted">Please reduce the number or select a different strand/level.</small>
                `,
                confirmButtonText: 'OK'
            });
            return;
        }

        $('#emptyState').hide();
        
        for (let i = 1; i <= numRows; i++) {
            $('#studentsContainer').append(generateStudentCard(currentRows + i));
        }
        
        updateStudentCount();
        
        // Show success message for multiple students
        if (numRows > 1) {
            Swal.fire({
                icon: 'success',
                title: 'Students Added',
                text: `${numRows} student forms have been added successfully!`,
                timer: 1500,
                showConfirmButton: false
            });
        }
    });

    // Remove card
    $(document).on('click', '.remove-card', function() {
        $(this).closest('.student-card').remove();
        renumberCards();
        updateStudentCount();
        
        if ($('.student-card').length === 0) {
            $('#emptyState').show();
        }
    });

    // Gender toggle
    $(document).on('click', '.gender-toggle', function() {
        const $btn = $(this);
        const $card = $btn.closest('.student-card');
        const currentGender = $btn.data('gender');
        const newGender = currentGender === 'Male' ? 'Female' : 'Male';
        const newIcon = newGender === 'Male' ? 'fa-mars' : 'fa-venus';
        
        $btn.data('gender', newGender);
        $btn.html(`<i class="fas ${newIcon}"></i> ${newGender}`);
        $card.find('.gender-input').val(newGender);
    });

    // Type toggle
    $(document).on('click', '.type-toggle', function() {
        const $btn = $(this);
        const $card = $btn.closest('.student-card');
        const currentType = $btn.data('type');
        const newType = currentType === 'regular' ? 'irregular' : 'regular';
        const newIcon = newType === 'regular' ? 'fa-user-check' : 'fa-user-clock';
        const newText = newType === 'regular' ? 'Regular' : 'Irregular';
        
        $btn.data('type', newType);
        $btn.html(`<i class="fas ${newIcon}"></i> ${newText}`);
        $card.find('.type-input').val(newType);
    });

    function renumberCards() {
        $('.student-card').each(function(index) {
            $(this).find('.card-title').text(`Student #${index + 1}`);
        });
    }

    function updateStudentCount() {
        const count = $('.student-card').length;
        $('#studentCount').text(`${count} Student${count !== 1 ? 's' : ''}`);
        $('#submitBtn').prop('disabled', count === 0);
    }

    // Form submission
    $('#insert_students').on('submit', function(e) {
        e.preventDefault();

        const strandId = $('#selectedStrand').val();
        const levelId = $('#selectedLevel').val();

        if (!strandId || !levelId) {
            Swal.fire('Validation Error', 'Please select Strand and Year Level', 'warning');
            return;
        }

        const studentCount = $('.student-card').length;
        if (studentCount === 0) {
            Swal.fire('No Students', 'Please add at least one student', 'warning');
            return;
        }

        if (studentCount > totalAvailableSlots) {
            Swal.fire({
                icon: 'error',
                title: 'Capacity Exceeded',
                html: `
                    You're trying to add <strong>${studentCount}</strong> students, 
                    but only <strong>${totalAvailableSlots}</strong> slots are available.<br><br>
                    <strong>Shortage: ${studentCount - totalAvailableSlots} students</strong>
                `,
                confirmButtonText: 'OK'
            });
            return;
        }

        // Collect student data
        const students = [];
        let hasErrors = false;

        $('.student-card').each(function() {
            const $card = $(this);
            const firstName = $card.find('input[name*="[firstName]"]').val().trim();
            const lastName = $card.find('input[name*="[lastName]"]').val().trim();

            if (!firstName || !lastName) {
                hasErrors = true;
                $card.addClass('border-danger');
                return;
            }

            $card.removeClass('border-danger');

            students.push({
                email: $card.find('input[name*="[email]"]').val().trim(),
                firstName: firstName,
                lastName: lastName,
                middleInitial: $card.find('input[name*="[middleInitial]"]').val().trim(),
                gender: $card.find('.gender-input').val(),
                studentType: $card.find('.type-input').val(),
                parentLastName: $card.find('input[name*="[parentLastName]"]').val().trim(),
                parentFirstName: $card.find('input[name*="[parentFirstName]"]').val().trim(),
                parentEmail: $card.find('input[name*="[parentEmail]"]').val().trim(),
            });
        });

        if (hasErrors) {
            Swal.fire('Validation Error', 'Please fill in all required fields (cards with red border)', 'error');
            return;
        }

        const batchSize = 25;
        const batches = [];
        
        for (let i = 0; i < students.length; i += batchSize) {
            batches.push(students.slice(i, i + batchSize));
        }

        showProcessingModal(batches.length, students.length);
        processBatches(batches, strandId, levelId, 0, students.length, 0);
    });

    function showProcessingModal(totalBatches, totalStudents) {
        Swal.fire({
            title: '<i class="fas fa-spinner fa-spin"></i> Processing Students',
            html: `
                <div class="mb-3">
                    <h5>Creating ${totalStudents} student records</h5>
                    <p class="text-muted">Processing in ${totalBatches} batch(es)</p>
                    <p class="text-muted">Auto-distributing across available sections</p>
                </div>
                <div class="progress" style="height: 30px;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                         role="progressbar" style="width: 0%">
                        <span id="progressText" class="font-weight-bold">0%</span>
                    </div>
                </div>
                <div class="mt-3">
                    <p id="statusText" class="mb-1">
                        <i class="fas fa-clock"></i> Starting...
                    </p>
                    <small id="detailText" class="text-muted">Batch 0 of ${totalBatches}</small>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false
        });
    }

    function updateProgress(current, total, batchNum, totalBatches) {
        const percentage = Math.round((current / total) * 100);
        $('#progressBar').css('width', percentage + '%');
        $('#progressText').text(percentage + '%');
        $('#statusText').html(`<i class="fas fa-check-circle"></i> Created ${current} of ${total} students`);
        $('#detailText').text(`Processing batch ${batchNum} of ${totalBatches}`);
    }

    function processBatches(batches, strandId, levelId, currentBatch, totalStudents, processedCount) {
        if (currentBatch >= batches.length) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                html: `
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-3x text-primary mb-3"></i>
                        <h4>All ${totalStudents} students created successfully!</h4>
                        <p class="text-muted">Students have been distributed across available sections</p>
                        <p class="text-muted">Redirecting to student list...</p>
                    </div>
                `,
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                window.location.href = API_ROUTES.redirectAfterSubmit;
            });
            return;
        }

        const batch = batches[currentBatch];
        const batchNumber = currentBatch + 1;
        
        $.ajax({
            url: API_ROUTES.insertStudents,
            type: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                strand_id: strandId,
                level_id: levelId,
                students: batch
            },
            success: function(response) {
                processedCount += batch.length;
                const actualProcessed = Math.min(processedCount, totalStudents);
                updateProgress(actualProcessed, totalStudents, batchNumber, batches.length);
                
                setTimeout(function() {
                    processBatches(batches, strandId, levelId, currentBatch + 1, totalStudents, processedCount);
                }, 300);
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while creating students';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage += '<br><br><small>' + Object.values(errors).flat().join('<br>') + '</small>';
                }

                let extraInfo = '';
                if (xhr.responseJSON && xhr.responseJSON.shortage) {
                    extraInfo = `
                        <hr>
                        <div class="alert alert-danger">
                            <strong>Capacity Issue:</strong><br>
                            Required: ${xhr.responseJSON.required} students<br>
                            Available: ${xhr.responseJSON.available} slots<br>
                            Shortage: ${xhr.responseJSON.shortage} students
                        </div>
                    `;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: `
                        <p>${errorMessage}</p>
                        ${extraInfo}
                        <hr>
                        <p class="text-muted">
                            <strong>Progress:</strong> ${processedCount} of ${totalStudents} students created<br>
                            <strong>Failed at:</strong> Batch ${batchNumber} of ${batches.length}
                        </p>
                    `,
                    confirmButtonText: 'OK'
                });
            }
        });
    }

    // Excel Import
    $('#importExcel').on('click', function() {
        if (!$('#strand').val() || !$('#level').val()) {
            Swal.fire('Validation Error', 'Please select Strand and Year Level first', 'warning');
            return;
        }
        $('#excelFile').click();
    });

    $('#excelFile').on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = function(e) {
            const data = new Uint8Array(e.target.result);
            const workbook = new ExcelJS.Workbook();
            
            workbook.xlsx.load(data).then(function() {
                const worksheet = workbook.worksheets[0];
                let importedCount = 0;
                let tempStudents = [];

                worksheet.eachRow(function(row, rowNumber) {
                    if (rowNumber > 1) {
                        const email = row.getCell(1).value || '';
                        const lastName = row.getCell(2).value;
                        const firstName = row.getCell(3).value;
                        const middleInitial = row.getCell(4).value || '';
                        let genderRaw = row.getCell(5).value || 'Male';
                        const studentTypeRaw = row.getCell(6).value || 'regular';
                        const parentLastName = row.getCell(7).value || '';
                        const parentFirstName = row.getCell(8).value || '';
                        const parentEmail = row.getCell(9).value || '';

                        if (!lastName || !firstName) {
                            return;
                        }

                        let gender = 'Male';
                        if (genderRaw) {
                            const genderStr = String(genderRaw).trim().toUpperCase();
                            if (genderStr === 'F' || genderStr === 'FEMALE') {
                                gender = 'Female';
                            }
                        }

                        const studentType = String(studentTypeRaw).toLowerCase().trim() === 'irregular' ? 'irregular' : 'regular';
                        tempStudents.push({
                            email, lastName, firstName, middleInitial, gender, studentType,
                            parentLastName, parentFirstName, parentEmail
                        });
                        importedCount++;
                    }
                });

                if (importedCount > totalAvailableSlots) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Insufficient Capacity',
                        html: `
                            Excel file contains <strong>${importedCount}</strong> students, 
                            but only <strong>${totalAvailableSlots}</strong> slots are available.<br><br>
                            <strong>Shortage: ${importedCount - totalAvailableSlots} students</strong><br><br>
                            <small class="text-muted">Please select a different strand/level or reduce the number of students in the Excel file.</small>
                        `,
                        confirmButtonText: 'OK'
                    });
                    $('#excelFile').val('');
                    return;
                }

                $('#studentsContainer').empty();
                $('#emptyState').hide();
                rowCounter = 0;

                tempStudents.forEach((studentData, index) => {
                    const newCard = generateStudentCard(index + 1);
                    $('#studentsContainer').append(newCard);
                    
                    const $lastCard = $('.student-card:last');
                    $lastCard.find('input[name*="[email]"]').val(studentData.email);
                    $lastCard.find('input[name*="[lastName]"]').val(studentData.lastName);
                    $lastCard.find('input[name*="[firstName]"]').val(studentData.firstName);
                    $lastCard.find('input[name*="[middleInitial]"]').val(studentData.middleInitial);
                    $lastCard.find('input[name*="[parentLastName]"]').val(studentData.parentLastName);
                    $lastCard.find('input[name*="[parentFirstName]"]').val(studentData.parentFirstName);
                    $lastCard.find('input[name*="[parentEmail]"]').val(studentData.parentEmail);
                    
                    const $genderBtn = $lastCard.find('.gender-toggle');
                    const genderIcon = studentData.gender === 'Male' ? 'fa-mars' : 'fa-venus';
                    $genderBtn.data('gender', studentData.gender);
                    $genderBtn.html(`<i class="fas ${genderIcon}"></i> ${studentData.gender}`);
                    $lastCard.find('.gender-input').val(studentData.gender);
                    
                    const $typeBtn = $lastCard.find('.type-toggle');
                    const typeIcon = studentData.studentType === 'regular' ? 'fa-user-check' : 'fa-user-clock';
                    const typeText = studentData.studentType === 'regular' ? 'Regular' : 'Irregular';
                    $typeBtn.data('type', studentData.studentType);
                    $typeBtn.html(`<i class="fas ${typeIcon}"></i> ${typeText}`);
                    $lastCard.find('.type-input').val(studentData.studentType);
                });

                updateStudentCount();

                if (importedCount > 0) {
                    Swal.fire('Success', `${importedCount} students imported from Excel`, 'success');
                }
                
                $('#excelFile').val('');
            }).catch(function(error) {
                Swal.fire('Error', 'Failed to read Excel file: ' + error.message, 'error');
                $('#excelFile').val('');
            });
        };
        reader.readAsArrayBuffer(file);
    });

    // Generate Excel Template
    $('#generateTemplate').on('click', function() {
        const workbook = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet('Student Template');

        worksheet.columns = [
            { header: 'Email', key: 'email', width: 30 },
            { header: 'Last Name', key: 'lastName', width: 20 },
            { header: 'First Name', key: 'firstName', width: 20 },
            { header: 'Middle Initial', key: 'middleInitial', width: 15 },
            { header: 'Gender (M/F)', key: 'gender', width: 15 },
            { header: 'Student Type', key: 'studentType', width: 15 },
            { header: 'Parent Last Name', key: 'parentLastName', width: 20 },
            { header: 'Parent First Name', key: 'parentFirstName', width: 20 },
            { header: 'Parent Email', key: 'parentEmail', width: 30 }
        ];

        worksheet.getRow(1).font = { bold: true, color: { argb: 'FFFFFFFF' } };
        worksheet.getRow(1).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF007BFF' }
        };

        worksheet.addRow({
            email: 'student@example.com',
            lastName: 'Dela Cruz',
            firstName: 'Juan',
            middleInitial: 'P',
            gender: 'M',
            studentType: 'regular',
            parentLastName: 'Dela Cruz',
            parentFirstName: 'Pedro',
            parentEmail: 'parent@example.com'
        });

        worksheet.addRow({
            email: '',
            lastName: 'Santos',
            firstName: 'Maria',
            middleInitial: 'A',
            gender: 'F',
            studentType: 'regular',
            parentLastName: 'Santos',
            parentFirstName: 'Rosa',
            parentEmail: 'rosa.santos@example.com'
        });

        workbook.xlsx.writeBuffer().then(function(buffer) {
            const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'student_import_template.xlsx';
            a.click();
            window.URL.revokeObjectURL(url);
        });
    });
});