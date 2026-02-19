$(document).ready(function () {
    // Initialize DataTable
    const table = $('#irregularStudentsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: API_ROUTES.getStudents,
            dataSrc: 'data'
        },
        columns: [
            { data: 'student_number' },
            {
                data: null,
                render: function (data) {
                    return `${data.last_name}, ${data.first_name} ${data.middle_name}`;
                }
            },
            { data: 'level_name' },
            { data: 'strand_name' },
            {
                data: 'section_name',
                render: function (data) {
                    return data || '<span class="text-muted">N/A</span>';
                }
            },
            {
                data: 'class_count',
                render: function (data) {
                    const badgeClass = data > 0 ? 'badge-primary' : 'badge-secondary';
                    return `<span class="badge ${badgeClass}">${data} Classes</span>`;
                }
            },
            {
                data: null,
                orderable: false,
                render: function (data) {
                    const url = API_ROUTES.enrollmentPage.replace(':id', data.id);
                    return `
                        <a href="${url}" class="btn btn-sm btn-primary" title="Manage Classes">
                            <i class="fas fa-book"></i> Enroll
                        </a>
                    `;
                }
            }
        ],
        responsive: true,
        autoWidth: false,
        order: [[1, 'asc']],
        pageLength: 25,
        language: {
            emptyTable: "No irregular students found",
            processing: '<i class="fas fa-spinner fa-spin"></i> Loading students...'
        }
    });
});