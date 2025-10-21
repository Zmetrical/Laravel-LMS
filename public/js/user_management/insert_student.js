console.log("insert user");

$(document).ready(function () {
    const strandSelect = $('#strand');
    const levelSelect = $('#level');
    const sectionSelect = $('#section');

    function render_Sections() {
        const strandId = strandSelect.val();
        const levelId = levelSelect.val();

        // Reset section dropdown
        sectionSelect.html('<option hidden disabled selected>Select Section</option>');

        // Build query dynamically
        const params = {};
        if (strandId) params.strand_id = strandId;
        if (levelId) params.level_id = levelId;

        if (Object.keys(params).length > 0) {
            $.ajax({
                url: '/procedure/get_sections',
                method: 'GET',
                data: params,
                success: function (data) {
                    if (data.length > 0) {
                        data.forEach(section => {
                            sectionSelect.append(
                                $('<option>', { value: section.id, text: section.name })
                            );
                        });
                    } else {
                        sectionSelect.append(
                            $('<option>', { text: 'No sections available', disabled: true })
                        );
                    }
                },
                error: function () {
                    sectionSelect.append(
                        $('<option>', { text: 'Error loading sections', disabled: true })
                    );
                }
            });
        } else {
            sectionSelect.append(
                $('<option>', { text: 'Please select strand and level first', disabled: true })
            );
        }
    }

    strandSelect.on('change', render_Sections);
    levelSelect.on('change', render_Sections);


    let rowCounter = 0;
    generate_Rows();

    function generate_Rows() {
        const numRows = parseInt($('#numRows').val()) || 5;

        if (numRows < 1 || numRows > 50) {
            alert('Please enter a number between 1 and 50');
            return;
        }

        $('#studentTableBody').empty();
        rowCounter = 0;

        for (let i = 0; i < numRows; i++) {
            render_Row();
        }
    }

    function render_Row() {
        rowCounter++;

        const row = `
        <tr>
            <td class="text-center">${rowCounter}</td>
            <td><input type="email" class="form-control form-control-sm student-email" placeholder="student@email.com" required></td>
            <td><input type="text" class="form-control form-control-sm student-lastname" placeholder="Dela Cruz" required></td>
            <td><input type="text" class="form-control form-control-sm student-firstname" placeholder="Juan" required></td>
            <td><input type="text" class="form-control form-control-sm student-mi" placeholder="A" maxlength="2"></td>
            <td>
                <select class="form-control form-control-sm student-gender" required>
                    <option value="">Select</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-danger btn-sm delete-row">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;

        $('#studentTableBody').append(row);
    }

    // Generate rows button
    $('#generateRowsBtn').on('click', function () {
        generate_Rows();
    });

    // Add single row button
    $('#addRowBtn').on('click', function () {
        render_Row();
    });

    // Delete row (event delegation)
    $(document).on('click', '.delete-row', function () {
        $(this).closest('tr').remove();
        $('#studentTableBody tr').each(function (index) {
            $(this).find('td:first-child').text(index + 1);
        });

        rowCounter = $('#studentTableBody tr').length;
    });

    function collectStudentData() {
        const students = [];

        $('#studentTableBody tr').each(function () {
            const $row = $(this);

            // Get values with fallback to empty string
            const email = $row.find('.student-email').val() || '';
            const lastName = $row.find('.student-lastname').val() || '';
            const firstName = $row.find('.student-firstname').val() || '';
            const middleInitial = $row.find('.student-mi').val() || '';
            const gender = $row.find('.student-gender').val() || '';

            const studentData = {
                email: email.trim(),
                lastName: lastName.trim(),
                firstName: firstName.trim(),
                middleInitial: middleInitial.trim(),
                gender: gender
            };

            // Only add if required fields are filled
            if (studentData.email && studentData.lastName &&
                studentData.firstName && studentData.gender) {
                students.push(studentData);
            }
        });

        return students;
    }

    // Form submission
    $('#insert_students').on('submit', function (e) {
        e.preventDefault();


        const students = collectStudentData();
        const section = $('#section').val();

        if (students.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Data',
                text: 'Please add at least one student'
            });
            return false;
        }

        // Show loading
        Swal.fire({
            title: 'Processing...',
            text: 'Creating student records',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const submitUrl = $(this).data('submit-url');
        const redirectUrl = $(this).data('redirect-url');

        $.ajax({
            url: submitUrl,
            method: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                section: section,
                students: students
            },
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = redirectUrl;
                    });
                }
                else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message,

                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = redirectUrl;
                    });
                }

            },
            error: function (xhr) {
                let errorMsg = 'An error occurred';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }

                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMsg += '\n\n' + xhr.responseJSON.errors.join('\n');
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
            }
        });
    });
});
