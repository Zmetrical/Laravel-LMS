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
                <div class="alert alert-primary">
                    <i class="fas fa-primary-circle"></i> Select Strand and Level to view sections
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
                        <div class="alert alert-primary">
                             No sections found for selected strand and level
                        </div>
                    `);
                    return;
                }

                let html = '<div class="mb-2">';
                html += `<div class="alert alert-'primary' py-2">`;
                html += `<strong><i class="fas fa-users"></i> Total Available: ${totalAvailableSlots} slots</strong>`;
                html += '</div></div>';

                sections.forEach(function(section) {
                    const percentage = (section.enrolled_count / section.capacity) * 100;
                    let statusClass = 'primary';

                    html += `
                        <div class="card mb-2">
                            <div class="card-body p-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge badge-${statusClass}">
                                        ${section.available_slots} / ${section.capacity}
                                    </span>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-${statusClass}" 
                                         style="width: ${percentage}%">
                                        ${section.enrolled_count} enrolled
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                $('#sectionCapacityContainer').html(html);
            },
            error: function(xhr) {
                Swal.fire('Error', 'Failed to load sections', 'error');
            }
        });
    }

    // Generate student rows
    function generateStudentRow(index) {
        rowCounter++;
        const row = `
            <tr data-row="${rowCounter}">
                <td class="text-center align-middle">${index}</td>
                <td>
                    <input type="email" class="form-control" 
                           name="students[${rowCounter}][email]" 
                           placeholder="student@email.com (optional)">
                </td>
                <td>
                    <input type="text" class="form-control" 
                           name="students[${rowCounter}][lastName]" 
                           placeholder="Last Name" required>
                </td>
                <td>
                    <input type="text" class="form-control" 
                           name="students[${rowCounter}][firstName]" 
                           placeholder="First Name" required>
                </td>
                <td>
                    <input type="text" class="form-control" 
                           name="students[${rowCounter}][middleInitial]" 
                           placeholder="M.I." maxlength="3">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-secondary btn-block gender-toggle" data-gender="Male">
                        <i class="fas fa-mars"></i> Male
                    </button>
                    <input type="hidden" name="students[${rowCounter}][gender]" value="Male" class="gender-input">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-secondary btn-block type-toggle" data-type="regular">
                        <i class="fas fa-user-check"></i> Regular
                    </button>
                    <input type="hidden" name="students[${rowCounter}][studentType]" value="regular" class="type-input">
                </td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-secondary btn-sm remove-row">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        return row;
    }

    // Generate rows button - with capacity check
    $('#generateRowsBtn').on('click', function() {
        const numRows = parseInt($('#numRows').val());
        
        if (!numRows || numRows < 1 || numRows > 100) {
            Swal.fire('Invalid Input', 'Please enter a number between 1 and 100', 'warning');
            return;
        }

        if (!$('#strand').val() || !$('#level').val()) {
            Swal.fire('Validation Error', 'Please select Strand and Year Level first', 'warning');
            return;
        }

        if (numRows > totalAvailableSlots) {
            Swal.fire({
                icon: 'warning',
                title: 'Insufficient Capacity',
                html: `
                    You're trying to add <strong>${numRows}</strong> students, 
                    but only <strong>${totalAvailableSlots}</strong> slots are available.<br><br>
                    <small class="text-muted">Please adjust the number of students or select a different strand/level.</small>
                `,
                confirmButtonText: 'OK'
            });
            return;
        }

        $('#studentTableBody').empty();
        rowCounter = 0;
        
        for (let i = 1; i <= numRows; i++) {
            $('#studentTableBody').append(generateStudentRow(i));
        }
        
        attachRowEvents();
    });

    // Add single row - with capacity check
    $('#addRowBtn').on('click', function() {
        if (!$('#strand').val() || !$('#level').val()) {
            Swal.fire('Validation Error', 'Please select Strand and Year Level first', 'warning');
            return;
        }

        const currentRows = $('#studentTableBody tr').length;
        
        if (currentRows >= totalAvailableSlots) {
            Swal.fire({
                icon: 'warning',
                title: 'Capacity Reached',
                text: `All ${totalAvailableSlots} available slots are already filled.`,
                confirmButtonText: 'OK'
            });
            return;
        }

        $('#studentTableBody').append(generateStudentRow(currentRows + 1));
        renumberRows();
        attachRowEvents();
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        renumberRows();
    });

    // Gender toggle
    $(document).on('click', '.gender-toggle', function() {
        const $btn = $(this);
        const $row = $btn.closest('tr');
        const currentGender = $btn.data('gender');
        const newGender = currentGender === 'Male' ? 'Female' : 'Male';
        const newIcon = newGender === 'Male' ? 'fa-mars' : 'fa-venus';
        
        $btn.data('gender', newGender);
        $btn.html(`<i class="fas ${newIcon}"></i> ${newGender}`);
        $row.find('.gender-input').val(newGender);
    });

    // Type toggle
    $(document).on('click', '.type-toggle', function() {
        const $btn = $(this);
        const $row = $btn.closest('tr');
        const currentType = $btn.data('type');
        const newType = currentType === 'regular' ? 'irregular' : 'regular';
        const newIcon = newType === 'regular' ? 'fa-user-check' : 'fa-user-clock';
        const newText = newType === 'regular' ? 'Regular' : 'Irregular';
        
        $btn.data('type', newType);
        $btn.html(`<i class="fas ${newIcon}"></i> ${newText}`);
        $row.find('.type-input').val(newType);
    });

    function attachRowEvents() {
        // Events are handled by delegated events above
    }

    function renumberRows() {
        $('#studentTableBody tr').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }

    // =========================================================================
    // FORM SUBMISSION WITH CAPACITY VALIDATION
    // =========================================================================
    
    $('#insert_students').on('submit', function(e) {
        e.preventDefault();

        // Validate academic info
        const strandId = $('#selectedStrand').val();
        const levelId = $('#selectedLevel').val();

        if (!strandId || !levelId) {
            Swal.fire('Validation Error', 'Please select Strand and Year Level', 'warning');
            return;
        }

        // Check if there are students
        const studentCount = $('#studentTableBody tr').length;
        if (studentCount === 0) {
            Swal.fire('No Students', 'Please add at least one student', 'warning');
            return;
        }

        // Check capacity
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

        // Collect all student data
        const students = [];
        let hasErrors = false;

        $('#studentTableBody tr').each(function() {
            const $row = $(this);
            const firstName = $row.find('input[name*="[firstName]"]').val().trim();
            const lastName = $row.find('input[name*="[lastName]"]').val().trim();

            if (!firstName || !lastName) {
                hasErrors = true;
                $row.addClass('table-danger');
                return;
            }

            $row.removeClass('table-danger');

            students.push({
                email: $row.find('input[name*="[email]"]').val().trim(),
                firstName: firstName,
                lastName: lastName,
                middleInitial: $row.find('input[name*="[middleInitial]"]').val().trim(),
                gender: $row.find('.gender-input').val(),
                studentType: $row.find('.type-input').val()
            });
        });

        if (hasErrors) {
            Swal.fire('Validation Error', 'Please fill in all required fields (highlighted in red)', 'error');
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
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
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
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
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

                // Show shortage information if capacity error
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

    // Excel Import (updated with capacity check)
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
                            email, lastName, firstName, middleInitial, gender, studentType
                        });
                        importedCount++;
                    }
                });

                // Check capacity before importing
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

                // If capacity is OK, populate the table
                $('#studentTableBody').empty();
                rowCounter = 0;

                tempStudents.forEach((studentData, index) => {
                    const newRow = generateStudentRow(index + 1);
                    $('#studentTableBody').append(newRow);
                    
                    const $lastRow = $('#studentTableBody tr:last');
                    $lastRow.find('input[name*="[email]"]').val(studentData.email);
                    $lastRow.find('input[name*="[lastName]"]').val(studentData.lastName);
                    $lastRow.find('input[name*="[firstName]"]').val(studentData.firstName);
                    $lastRow.find('input[name*="[middleInitial]"]').val(studentData.middleInitial);
                    
                    const $genderBtn = $lastRow.find('.gender-toggle');
                    const genderIcon = studentData.gender === 'Male' ? 'fa-mars' : 'fa-venus';
                    $genderBtn.data('gender', studentData.gender);
                    $genderBtn.html(`<i class="fas ${genderIcon}"></i> ${studentData.gender}`);
                    $lastRow.find('.gender-input').val(studentData.gender);
                    
                    const $typeBtn = $lastRow.find('.type-toggle');
                    const typeIcon = studentData.studentType === 'regular' ? 'fa-user-check' : 'fa-user-clock';
                    const typeText = studentData.studentType === 'regular' ? 'Regular' : 'Irregular';
                    $typeBtn.data('type', studentData.studentType);
                    $typeBtn.html(`<i class="fas ${typeIcon}"></i> ${typeText}`);
                    $lastRow.find('.type-input').val(studentData.studentType);
                });

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
            { header: 'Gender (M/F or Male/Female)', key: 'gender', width: 25 },
            { header: 'Student Type', key: 'studentType', width: 15 }
        ];

        worksheet.getRow(1).font = { bold: true };
        worksheet.getRow(1).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF4472C4' }
        };
        worksheet.getRow(1).font = { color: { argb: 'FFFFFFFF' }, bold: true };

        worksheet.addRow({
            email: 'student@example.com',
            lastName: 'Dela Cruz',
            firstName: 'Juan',
            middleInitial: 'P',
            gender: 'M',
            studentType: 'regular'
        });

        worksheet.addRow({
            email: '',
            lastName: 'Santos',
            firstName: 'Maria',
            middleInitial: 'A',
            gender: 'F',
            studentType: 'regular'
        });

        workbook.xlsx.writeBuffer().then(function(buffer) {
            const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'student_template.xlsx';
            a.click();
            window.URL.revokeObjectURL(url);
        });
    });
});