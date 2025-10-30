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

        const sections = [
            { grade: 11, strand: 'STEM', section: 'A', studentCount: 38 },
            { grade: 11, strand: 'STEM', section: 'B', studentCount: 35 },
            { grade: 11, strand: 'ABM', section: 'A', studentCount: 40 },
            { grade: 11, strand: 'ABM', section: 'B', studentCount: 37 },
            { grade: 11, strand: 'HUMSS', section: 'A', studentCount: 42 },
            { grade: 11, strand: 'GAS', section: 'A', studentCount: 36 },
            { grade: 12, strand: 'STEM', section: 'A', studentCount: 35 },
            { grade: 12, strand: 'STEM', section: 'B', studentCount: 33 },
            { grade: 12, strand: 'ABM', section: 'A', studentCount: 38 },
            { grade: 12, strand: 'HUMSS', section: 'A', studentCount: 40 },
            { grade: 12, strand: 'GAS', section: 'A', studentCount: 34 }
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

        const sectionClassEnrollments = {
            '11-STEM-A': ['c1', 'c2', 'c3', 'c4', 'c8'],
            '11-STEM-B': ['c1', 'c2', 'c3', 'c4', 'c8'],
            '11-ABM-A': ['c1', 'c2', 'c5', 'c6', 'c7'],
            '11-ABM-B': ['c1', 'c2', 'c5', 'c6', 'c7'],
            '11-HUMSS-A': ['c1', 'c2', 'c12', 'c13', 'c14'],
            '11-GAS-A': ['c1', 'c2', 'c10', 'c14'],
            '12-STEM-A': ['c1', 'c2', 'c3', 'c4', 'c9'],
            '12-STEM-B': ['c1', 'c2', 'c3', 'c4', 'c9'],
            '12-ABM-A': ['c1', 'c2', 'c5', 'c7', 'c10'],
            '12-HUMSS-A': ['c1', 'c2', 'c13', 'c15'],
            '12-GAS-A': ['c1', 'c2', 'c10']
        };

        let filters = {
            sectionGrade: '',
            sectionStrand: '',
            sectionSearch: '',
            irregularSearch: '',
            irregularGrade: '',
            irregularStrand: ''
        };

        $(document).ready(function () {
            setupEventListeners();
            renderSections();
            renderIrregularStudents();
            updateAnalytics();
        });

        function setupEventListeners() {
            $('#sectionGradeFilter').change(function () {
                filters.sectionGrade = $(this).val();
                renderSections();
            });

            $('#sectionStrandFilter').change(function () {
                filters.sectionStrand = $(this).val();
                renderSections();
            });

            $('#sectionSearchInput').on('keyup', function () {
                filters.sectionSearch = $(this).val().toLowerCase();
                renderSections();
            });

            $('#resetFiltersBtn').click(function () {
                filters.sectionGrade = '';
                filters.sectionStrand = '';
                filters.sectionSearch = '';
                $('#sectionGradeFilter').val('');
                $('#sectionStrandFilter').val('');
                $('#sectionSearchInput').val('');
                renderSections();
            });

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
        }

        function renderSections() {
            const container = $('#sectionsContainer');
            container.empty();

            let filteredSections = sections.filter(section => {
                if (filters.sectionGrade && section.grade.toString() !== filters.sectionGrade) {
                    return false;
                }
                if (filters.sectionStrand && section.strand !== filters.sectionStrand) {
                    return false;
                }
                if (filters.sectionSearch) {
                    const searchStr = `${section.grade} ${section.strand} ${section.section}`.toLowerCase();
                    if (!searchStr.includes(filters.sectionSearch)) {
                        return false;
                    }
                }
                return true;
            });

            if (filteredSections.length === 0) {
                container.html(`
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> No sections found matching your filters.
                        </div>
                    </div>
                `);
                return;
            }

            filteredSections.forEach(section => {
                const sectionKey = `${section.grade}-${section.strand}-${section.section}`;
                const enrolledClasses = sectionClassEnrollments[sectionKey] || [];
                const strandColor = getStrandColor(section.strand);

                container.append(`
                    <div class="col-lg-4 col-md-6">
                        <div class="card card-outline card-${strandColor}">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-users"></i> 
                                    <strong>Grade ${section.grade} - ${section.strand} ${section.section}</strong>
                                </h3>
                                <div class="card-tools">
                                    <span class="badge badge-${strandColor}">${section.studentCount} Students</span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item bg-light">
                                        <strong><i class="fas fa-book"></i> Enrolled Classes (${enrolledClasses.length})</strong>
                                    </li>
                                    ${enrolledClasses.map(classId => {
                    const cls = sampleClasses.find(c => c.id === classId);
                    return cls ? `
                                            <li class="list-group-item">
                                                <i class="fas fa-check-circle text-success"></i> ${cls.name}
                                            </li>
                                        ` : '';
                }).join('')}
                                </ul>
                            </div>
                            <div class="card-footer">
                                <button class="btn btn-sm btn-primary btn-block" onclick="viewSectionDetails('${sectionKey}')">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            </div>
                        </div>
                    </div>
                `);
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
                    <div class="card card-warning card-outline mt-3">
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

        function updateAnalytics() {
            $('#totalSections').text(sections.length);

            const totalRegular = sections.reduce((sum, s) => sum + s.studentCount, 0);
            $('#totalRegularStudents').text(totalRegular);

            $('#totalIrregularStudents').text(irregularStudents.length);

            const uniqueClasses = new Set();
            Object.values(sectionClassEnrollments).forEach(classes => {
                classes.forEach(c => uniqueClasses.add(c));
            });
            $('#totalClasses').text(uniqueClasses.size);

            renderStrandStats();
            renderClassStats();
        }

        function renderStrandStats() {
            const tbody = $('#strandStatsBody');
            tbody.empty();

            const strands = ['STEM', 'ABM', 'HUMSS', 'GAS'];

            strands.forEach(strand => {
                const regularCount = sections
                    .filter(s => s.strand === strand)
                    .reduce((sum, s) => sum + s.studentCount, 0);

                const irregularCount = irregularStudents.filter(s => s.strand === strand).length;
                const total = regularCount + irregularCount;

                tbody.append(`
                    <tr>
                        <td><span class="badge badge-${getStrandColor(strand)}">${strand}</span></td>
                        <td>${regularCount}</td>
                        <td>${irregularCount}</td>
                        <td><strong>${total}</strong></td>
                    </tr>
                `);
            });
        }

        function renderClassStats() {
            const tbody = $('#classStatsBody');
            tbody.empty();

            const classEnrollments = {};

            Object.values(sectionClassEnrollments).forEach(classes => {
                classes.forEach(classId => {
                    classEnrollments[classId] = (classEnrollments[classId] || 0) + 1;
                });
            });

            const sortedClasses = Object.entries(classEnrollments)
                .sort((a, b) => b[1] - a[1]);

            sortedClasses.slice(0, 10).forEach(([classId, count]) => {
                const cls = sampleClasses.find(c => c.id === classId);
                if (cls) {
                    tbody.append(`
                        <tr>
                            <td>${cls.name}</td>
                            <td><span class="badge badge-primary">${count} sections</span></td>
                        </tr>
                    `);
                }
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

        function viewSectionDetails(sectionKey) {
            const [grade, strand, section] = sectionKey.split('-');
            const sectionData = sections.find(s =>
                s.grade == grade && s.strand === strand && s.section === section
            );

            if (!sectionData) return;

            const enrolledClasses = sectionClassEnrollments[sectionKey] || [];

            let html = `
                <div class="row">
                    <div class="col-md-12">
                        <h4><i class="fas fa-users"></i> Grade ${grade} - ${strand} ${section}</h4>
                        <hr>
                        <p><strong>Total Students:</strong> ${sectionData.studentCount}</p>
                        <p><strong>Enrolled Classes:</strong> ${enrolledClasses.length}</p>
                        <hr>
                        <h5>Class List:</h5>
                        <ul class="list-group">
                            ${enrolledClasses.map(classId => {
                const cls = sampleClasses.find(c => c.id === classId);
                return cls ? `
                                    <li class="list-group-item">
                                        <i class="fas fa-book text-primary"></i> ${cls.name}
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