$(document).ready(function () {

    // ---------------------------------------------------------------------------
    //  Load Enrollment History
    // ---------------------------------------------------------------------------

    function loadEnrollmentHistory() {
        $.ajax({
            url: API_ROUTES.getEnrollmentHistory,
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    populateEnrollmentHistory(response.data);
                } else {
                    showEnrollmentHistoryError('Failed to load enrollment history');
                }
            },
            error: function (xhr) {
                console.error('Error loading enrollment history:', xhr);
                showEnrollmentHistoryError('Error loading enrollment history');
            }
        });
    }

    function populateEnrollmentHistory(semesters) {
        const tbody = $('#enrollmentHistoryBody');
        tbody.empty();

        if (semesters.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="2" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        No enrollment history found
                    </td>
                </tr>
            `);
            return;
        }

        semesters.forEach(function(semester) {
            const row = `
                <tr>
                    <td>${semester.year_start} - ${semester.year_end}</td>
                    <td>${semester.semester_name}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function showEnrollmentHistoryError(message) {
        const tbody = $('#enrollmentHistoryBody');
        tbody.empty();
        tbody.append(`
            <tr>
                <td colspan="2" class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle mb-2 d-block"></i>
                    ${message}
                </td>
            </tr>
        `);
    }

    // ---------------------------------------------------------------------------
    //  Load Enrolled Classes
    // ---------------------------------------------------------------------------

    function loadEnrolledClasses() {
        $.ajax({
            url: API_ROUTES.getEnrolledClasses,
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    populateEnrolledClasses(response.data);
                } else {
                    showEnrolledClassesError('Failed to load enrolled classes');
                }
            },
            error: function (xhr) {
                console.error('Error loading enrolled classes:', xhr);
                showEnrolledClassesError('Error loading enrolled classes');
            }
        });
    }

    function populateEnrolledClasses(classes) {
        const tbody = $('#enrolledClassesBody');
        tbody.empty();

        if (classes.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        No enrolled classes found
                    </td>
                </tr>
            `);
            return;
        }

        classes.forEach(function(cls) {
            const row = `
                <tr>
                    <td>${cls.class_code}</td>
                    <td>${cls.class_name}</td>
                    <td>${cls.year_start} - ${cls.year_end}</td>
                    <td>${cls.semester_name}</td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    function showEnrolledClassesError(message) {
        const tbody = $('#enrolledClassesBody');
        tbody.empty();
        tbody.append(`
            <tr>
                <td colspan="4" class="text-center text-danger py-4">
                    <i class="fas fa-exclamation-triangle mb-2 d-block"></i>
                    ${message}
                </td>
            </tr>
        `);
    }

    // ---------------------------------------------------------------------------
    //  Initialize
    // ---------------------------------------------------------------------------

    loadEnrollmentHistory();
    loadEnrolledClasses();

});