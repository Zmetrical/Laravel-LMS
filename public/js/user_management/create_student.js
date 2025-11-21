$(document).ready(function() {
    let rowCounter = 0;

    // Load sections when strand or level changes
    $('#strand, #level').on('change', function() {
        const strandId = $('#strand').val();
        const levelId = $('#level').val();

        if (strandId && levelId) {
            loadSections(strandId, levelId);
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
            success: function(sections) {
                $('#section').html('<option value="" selected disabled>Select Section</option>');
                sections.forEach(function(section) {
                    $('#section').append(`<option value="${section.id}">${section.name}</option>`);
                });
            },
            error: function() {
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
                    <button type="button" class="btn btn-default btn-block gender-toggle" data-gender="Male">
                        <i class="fas fa-mars"></i> Male
                    </button>
                    <input type="hidden" name="students[${rowCounter}][gender]" value="Male" class="gender-input">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-default btn-block type-toggle" data-type="regular">
                        <i class="fas fa-user-check"></i> Regular
                    </button>
                    <input type="hidden" name="students[${rowCounter}][studentType]" value="regular" class="type-input">
                </td>
                <td class="text-center align-middle">
                    <button type="button" class="btn btn-default btn-sm remove-row">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        return row;
    }

    // Generate rows button
    $('#generateRowsBtn').on('click', function() {
        const numRows = parseInt($('#numRows').val());
        
        if (!numRows || numRows < 1 || numRows > 100) {
            Swal.fire('Invalid Input', 'Please enter a number between 1 and 100', 'warning');
            return;
        }

        $('#studentTableBody').empty();
        rowCounter = 0;
        
        for (let i = 1; i <= numRows; i++) {
            $('#studentTableBody').append(generateStudentRow(i));
        }
        
        attachRowEvents();
    });

    // Add single row
    $('#addRowBtn').on('click', function() {
        const currentRows = $('#studentTableBody tr').length;
        $('#studentTableBody').append(generateStudentRow(currentRows + 1));
        renumberRows();
        attachRowEvents();
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        $(this).closest('tr').remove();
        renumberRows();
    });

    // Gender toggle - single button
    $(document).on('click', '.gender-toggle', function() {
        const $btn = $(this);
        const $row = $btn.closest('tr');
        const currentGender = $btn.data('gender');
        const newGender = currentGender === 'Male' ? 'Female' : 'Male';
        const newIcon = newGender === 'Male' ? 'fa-mars' : 'fa-venus';
        
        // Update button
        $btn.data('gender', newGender);
        $btn.html(`<i class="fas ${newIcon}"></i> ${newGender}`);
        
        // Update hidden input
        $row.find('.gender-input').val(newGender);
    });

    // Type toggle - single button
    $(document).on('click', '.type-toggle', function() {
        const $btn = $(this);
        const $row = $btn.closest('tr');
        const currentType = $btn.data('type');
        const newType = currentType === 'regular' ? 'irregular' : 'regular';
        const newIcon = newType === 'regular' ? 'fa-user-check' : 'fa-user-clock';
        const newText = newType === 'regular' ? 'Regular' : 'Irregular';
        
        // Update button
        $btn.data('type', newType);
        $btn.html(`<i class="fas ${newIcon}"></i> ${newText}`);
        
        // Update hidden input
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
    // BATCH PROCESSING FORM SUBMISSION
    // =========================================================================
    
    $('#insert_students').on('submit', function(e) {
        e.preventDefault();

        // Validate academic info
        if (!$('#strand').val() || !$('#level').val() || !$('#section').val()) {
            Swal.fire('Validation Error', 'Please select Strand, Year Level, and Section', 'warning');
            return;
        }

        // Check if there are students
        if ($('#studentTableBody tr').length === 0) {
            Swal.fire('No Students', 'Please add at least one student', 'warning');
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

        const section = $('#section').val();
        const batchSize = 25; // Process 25 students at a time
        const batches = [];
        
        for (let i = 0; i < students.length; i += batchSize) {
            batches.push(students.slice(i, i + batchSize));
        }

        // Show processing modal with progress
        showProcessingModal(batches.length, students.length);
        
        // Start processing batches
        processBatches(batches, section, 0, students.length, 0);
    });

    function showProcessingModal(totalBatches, totalStudents) {
        Swal.fire({
            title: '<i class="fas fa-spinner fa-spin"></i> Processing Students',
            html: `
                <div class="mb-3">
                    <h5>Creating ${totalStudents} student records</h5>
                    <p class="text-muted">Processing in ${totalBatches} batch(es)</p>
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
        $('#statusText').html(`<i class="fas fa-check-circle text-success"></i> Created ${current} of ${total} students`);
        $('#detailText').text(`Processing batch ${batchNum} of ${totalBatches}`);
    }

    function processBatches(batches, section, currentBatch, totalStudents, processedCount) {
        if (currentBatch >= batches.length) {
            // All batches completed - success!
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                html: `
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4>All ${totalStudents} students created successfully!</h4>
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
                section: section,
                students: batch
            },
            success: function(response) {
                // Update progress
                processedCount += batch.length;
                const actualProcessed = Math.min(processedCount, totalStudents);
                updateProgress(actualProcessed, totalStudents, batchNumber, batches.length);
                
                // Small delay for visual feedback, then process next batch
                setTimeout(function() {
                    processBatches(batches, section, currentBatch + 1, totalStudents, processedCount);
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

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: `
                        <p>${errorMessage}</p>
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
            $('#studentTableBody').empty();
            rowCounter = 0; // Reset counter
            
            let importedCount = 0;

            worksheet.eachRow(function(row, rowNumber) {
                if (rowNumber > 1) { // Skip header
                    const email = row.getCell(1).value || '';
                    const lastName = row.getCell(2).value;
                    const firstName = row.getCell(3).value;
                    const middleInitial = row.getCell(4).value || '';
                    let genderRaw = row.getCell(5).value || 'Male';
                    const studentTypeRaw = row.getCell(6).value || 'regular';

                    // Skip empty rows
                    if (!lastName || !firstName) {
                        return;
                    }

                    // Normalize gender: M/F to Male/Female
                    let gender = 'Male';
                    if (genderRaw) {
                        const genderStr = String(genderRaw).trim().toUpperCase();
                        if (genderStr === 'F' || genderStr === 'FEMALE') {
                            gender = 'Female';
                        } else if (genderStr === 'M' || genderStr === 'MALE') {
                            gender = 'Male';
                        }
                    }

                    // Normalize student type
                    const studentType = String(studentTypeRaw).toLowerCase().trim() === 'irregular' ? 'irregular' : 'regular';

                    importedCount++;
                    
                    // Generate the row HTML
                    const newRow = generateStudentRow(importedCount);
                    $('#studentTableBody').append(newRow);
                    
                    // Fill in the data for the last added row
                    const $lastRow = $('#studentTableBody tr:last');
                    $lastRow.find('input[name*="[email]"]').val(email);
                    $lastRow.find('input[name*="[lastName]"]').val(lastName);
                    $lastRow.find('input[name*="[firstName]"]').val(firstName);
                    $lastRow.find('input[name*="[middleInitial]"]').val(middleInitial);
                    
                    // Set gender
                    const $genderBtn = $lastRow.find('.gender-toggle');
                    const genderIcon = gender === 'Male' ? 'fa-mars' : 'fa-venus';
                    $genderBtn.data('gender', gender);
                    $genderBtn.html(`<i class="fas ${genderIcon}"></i> ${gender}`);
                    $lastRow.find('.gender-input').val(gender);
                    
                    // Set type
                    const $typeBtn = $lastRow.find('.type-toggle');
                    const typeIcon = studentType === 'regular' ? 'fa-user-check' : 'fa-user-clock';
                    const typeText = studentType === 'regular' ? 'Regular' : 'Irregular';
                    $typeBtn.data('type', studentType);
                    $typeBtn.html(`<i class="fas ${typeIcon}"></i> ${typeText}`);
                    $lastRow.find('.type-input').val(studentType);
                }
            });

            if (importedCount > 0) {
                Swal.fire('Success', `${importedCount} students imported from Excel`, 'success');
            } else {
                Swal.fire('No Data', 'No valid student records found in the Excel file', 'warning');
            }
            
            // Reset the file input
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

        // Add headers
        worksheet.columns = [
            { header: 'Email', key: 'email', width: 30 },
            { header: 'Last Name', key: 'lastName', width: 20 },
            { header: 'First Name', key: 'firstName', width: 20 },
            { header: 'Middle Initial', key: 'middleInitial', width: 15 },
            { header: 'Gender (M/F or Male/Female)', key: 'gender', width: 25 },
            { header: 'Student Type', key: 'studentType', width: 15 }
        ];

        // Style header
        worksheet.getRow(1).font = { bold: true };
        worksheet.getRow(1).fill = {
            type: 'pattern',
            pattern: 'solid',
            fgColor: { argb: 'FF4472C4' }
        };
        worksheet.getRow(1).font = { color: { argb: 'FFFFFFFF' }, bold: true };

        // Add sample data
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

        // Generate file
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

    // Initialize with 5 rows
    $('#generateRowsBtn').trigger('click');
});