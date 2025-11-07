const sampleClasses = [
    { id: 'c1', name: 'English for Academic Purposes' },
    { id: 'c2', name: 'Philippine History' },
    { id: 'c3', name: 'Calculus 1' },
    { id: 'c4', name: 'Chemistry' },
    { id: 'c5', name: 'Business Management' },
    { id: 'c6', name: 'Economics' },
    { id: 'c7', name: 'Accounting' },
    { id: 'c8', name: 'Physics 1' },
    { id: 'c9', name: 'Calculus 2' },
    { id: 'c10', name: 'Entrepreneurship' }
];

const irregularStudents = [
    {
        id: '2024-001-0000015',
        name: 'Carlos Mendoza',
        grade: 11,
        strand: 'STEM',
        reason: 'Failed Math subject',
        classes: ['c1', 'c2', 'c3', 'c4']
    },
    {
        id: '2024-002-0000024',
        name: 'Isabella Santos',
        grade: 12,
        strand: 'ABM',
        reason: 'Transferred from another school',
        classes: ['c1', 'c2', 'c5', 'c6', 'c7']
    },
    {
        id: '2024-003-0000033',
        name: 'Miguel Rodriguez',
        grade: 11,
        strand: 'HUMSS',
        reason: 'Medical leave last year',
        classes: ['c1', 'c2', 'c12', 'c13']
    },
    {
        id: '2024-004-0000041',
        name: 'Sofia Martinez',
        grade: 12,
        strand: 'STEM',
        reason: 'Shifted from ABM',
        classes: ['c3', 'c4', 'c8', 'c9']
    },
    {
        id: '2024-005-0000052',
        name: 'Daniel Cruz',
        grade: 11,
        strand: 'GAS',
        reason: 'Repeated student',
        classes: ['c1', 'c2', 'c10']
    }
];

let filters = {
    irregularSearch: '',
    irregularGrade: '',
    irregularStrand: ''
};

$(document).ready(function () {
    setupEventListeners();
    renderIrregularStudents();
});

function setupEventListeners() {
    $('#irregularSearchInput').on('keyup', function () {
        filters.irregularSearch = $(this).val().toLowerCase();
        renderIrregularStudents();
    });

    $('#irregularGradeFilter').change(function () {
        filters.irregularGrade = $(this).val();
        renderIrregularStudents();
    });

    $('#irregularStrandFilter').change(function () {
        filters.irregularStrand = $(this).val();
        renderIrregularStudents();
    });

    $('#resetFiltersBtn').click(function () {
        filters.irregularSearch = '';
        filters.irregularGrade = '';
        filters.irregularStrand = '';
        $('#irregularSearchInput').val('');
        $('#irregularGradeFilter').val('');
        $('#irregularStrandFilter').val('');
        renderIrregularStudents();
    });
}

function renderIrregularStudents() {
    const container = $('#irregularStudentsContainer');
    container.empty();

    let filteredStudents = irregularStudents.filter(student => {
        if (filters.irregularSearch) {
            const searchStr = `${student.name} ${student.id}`.toLowerCase();
            if (!searchStr.includes(filters.irregularSearch)) {
                return false;
            }
        }
        if (filters.irregularGrade && student.grade.toString() !== filters.irregularGrade) {
            return false;
        }
        if (filters.irregularStrand && student.strand !== filters.irregularStrand) {
            return false;
        }
        return true;
    });

    if (filteredStudents.length === 0) {
        container.html(`
            <div class="alert alert-warning mt-3">
                <i class="fas fa-exclamation-triangle"></i> No irregular students found matching your filters.
            </div>
        `);
        return;
    }

    filteredStudents.forEach(student => {
        const strandColor = getStrandColor(student.strand);

        container.append(`
            <div class="card card-info card-outline mt-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-user"></i> <strong>${student.name}</strong>
                    </h3>
                    <div class="card-tools">
                        <span class="badge badge-secondary">Grade ${student.grade}</span>
                        <span class="badge badge-${strandColor}">${student.strand}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Student ID:</strong><br>${student.id}</p>
                            <p><strong>Status:</strong><br>
                                <span class="badge badge-warning">Irregular</span>
                            </p>
                            <p><strong>Reason:</strong><br>
                                <span class="text-muted">${student.reason}</span>
                            </p>
                        </div>
                        <div class="col-md-8">
                            <p><strong><i class="fas fa-book"></i> Enrolled Classes (${student.classes.length})</strong></p>
                            <ul class="list-group">
                                ${student.classes.map(classId => {
                                    const cls = sampleClasses.find(c => c.id === classId);
                                    return cls ? `
                                        <li class="list-group-item py-2">
                                            <i class="fas fa-check-circle text-success"></i> ${cls.name}
                                        </li>
                                    ` : '';
                                }).join('')}
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-sm btn-info" onclick="viewStudentDetails('${student.id}')">
                        <i class="fas fa-eye"></i> View Full Details
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="manageClasses('${student.id}')">
                        <i class="fas fa-edit"></i> Manage Classes
                    </button>
                </div>
            </div>
        `);
    });
}

function getStrandColor(strand) {
    const colors = {
        'STEM': 'primary',
        'ABM': 'success',
        'HUMSS': 'info',
        'GAS': 'warning'
    };
    return colors[strand] || 'secondary';
}

function viewStudentDetails(studentId) {
    const student = irregularStudents.find(s => s.id === studentId);
    if (!student) return;

    let html = `
        <div class="row">
            <div class="col-md-6">
                <h4><i class="fas fa-user"></i> ${student.name}</h4>
                <hr>
                <p><strong>Student ID:</strong> ${student.id}</p>
                <p><strong>Grade Level:</strong> ${student.grade}</p>
                <p><strong>Strand:</strong> <span class="badge badge-${getStrandColor(student.strand)}">${student.strand}</span></p>
                <p><strong>Status:</strong> <span class="badge badge-warning">Irregular</span></p>
                <p><strong>Reason:</strong> ${student.reason}</p>
            </div>
            <div class="col-md-6">
                <h5><i class="fas fa-book"></i> Enrolled Classes (${student.classes.length})</h5>
                <ul class="list-group">
                    ${student.classes.map(classId => {
                        const cls = sampleClasses.find(c => c.id === classId);
                        return cls ? `
                            <li class="list-group-item">
                                <i class="fas fa-check-circle text-success"></i> ${cls.name}
                            </li>
                        ` : '';
                    }).join('')}
                </ul>
            </div>
        </div>
    `;

    $('#studentDetailsBody').html(html);
    $('#studentDetailsModal').modal('show');
}

function manageClasses(studentId) {
    alert(`Redirecting to class management for student ${studentId}`);
}