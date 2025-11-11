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
                           placeholder="student@email.com" required>
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
                           placeholder="M.I." maxlength="2">
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
        
        if (!numRows || numRows < 1 || numRows > 50) {
            Swal.fire('Invalid Input', 'Please enter a number between 1 and 50', 'warning');
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

    // Form submission
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

        const formData = $(this).serialize();

        Swal.fire({
            title: 'Processing...',
            text: 'Creating student records',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        $.ajax({
            url: API_ROUTES.insertStudents,
            type: 'POST',
            data: formData,
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    showConfirmButton: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = API_ROUTES.redirectAfterSubmit;
                    }
                });
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while creating students';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage += '\n\n' + Object.values(errors).flat().join('\n');
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    });

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
                rowCounter = 0;

                worksheet.eachRow(function(row, rowNumber) {
                    if (rowNumber > 1) { // Skip header
                        const email = row.getCell(1).value;
                        const lastName = row.getCell(2).value;
                        const firstName = row.getCell(3).value;
                        const middleInitial = row.getCell(4).value || '';
                        const gender = row.getCell(5).value || 'Male';
                        const studentType = row.getCell(6).value || 'regular';

                        if (email && lastName && firstName) {
                            rowCounter++;
                            const newRow = generateStudentRow(rowCounter);
                            $('#studentTableBody').append(newRow);
                            
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
                    }
                });

                Swal.fire('Success', 'Excel data imported successfully', 'success');
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
            { header: 'Gender', key: 'gender', width: 15 },
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
            gender: 'Male',
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