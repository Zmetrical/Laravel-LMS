        const sampleStudents = [
            { id: '2024-001-0000001', name: 'Juan Dela Cruz', grade: 11, strand: 'STEM', section: 'A' },
            { id: '2024-002-0000002', name: 'Maria Garcia', grade: 11, strand: 'ABM', section: 'B' },
            { id: '2024-003-0000003', name: 'Jose Santos', grade: 12, strand: 'STEM', section: 'A' },
            { id: '2024-004-0000004', name: 'Rosa Martinez', grade: 12, strand: 'HUMSS', section: 'C' },
            { id: '2024-005-0000005', name: 'Pedro Reyes', grade: 11, strand: 'ABM', section: 'A' },
            { id: '2024-006-0000006', name: 'Ana Lopez', grade: 12, strand: 'STEM', section: 'B' },
            { id: '2024-007-0000007', name: 'Carlos Ramos', grade: 11, strand: 'HUMSS', section: 'C' },
            { id: '2024-008-0000008', name: 'Elena Torres', grade: 12, strand: 'ABM', section: 'A' },
            { id: '2024-009-0000009', name: 'Miguel Santos', grade: 11, strand: 'STEM', section: 'B' },
            { id: '2024-010-0000010', name: 'Sofia Rivera', grade: 12, strand: 'GAS', section: 'C' },
            { id: '2024-011-0000011', name: 'Luis Fernandez', grade: 11, strand: 'GAS', section: 'A' },
            { id: '2024-012-0000012', name: 'Diana Cruz', grade: 12, strand: 'HUMSS', section: 'B' },
        ];

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
            { id: 'c10', name: 'Entrepreneurship' },
            { id: 'c11', name: 'Financial Management' },
            { id: 'c12', name: 'World Literature' },
            { id: 'c13', name: 'Civics' },
            { id: 'c14', name: 'Geography' },
            { id: 'c15', name: 'Philosophy' },
            { id: 'c16', name: 'Research Methods' },
            { id: 'c17', name: 'Filipino' },
            { id: 'c18', name: 'Physical Education' }
        ];

        let currentSchoolYearId = 'default';
        let currentClass = null;
        let enrollments = { default: {} };
        let filters = {
            classSearch: '',
            studentSearch: '',
            grade: '',
            strand: '',
            section: '',
            nameLetter: '',
            nameType: 'first'
        };

        $(document).ready(function () {
            getSchoolYearFromUrl();
            setupEventListeners();
            loadClasses();
        });

        function getSchoolYearFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            const id = urlParams.get('id');

            if (id) {
                currentSchoolYearId = id;
                $('#pageTitle').html(`Student Enrollment - School Year ID: ${currentSchoolYearId}`);
            }

            if (!enrollments[currentSchoolYearId]) {
                enrollments[currentSchoolYearId] = {};
            }
        }

        function setupEventListeners() {
            $('#classSearch').on('keyup', function () {
                filters.classSearch = $(this).val().toLowerCase();
                loadClasses();
            });

            $('#studentSearch').on('keyup', function () {
                filters.studentSearch = $(this).val().toLowerCase();
                loadStudents();
            });

            $('#gradeFilter').change(function () {
                filters.grade = $(this).val();
                loadStudents();
            });

            $('#strandFilter').change(function () {
                filters.strand = $(this).val();
                loadStudents();
            });

            $('#sectionFilter').change(function () {
                filters.section = $(this).val();
                loadStudents();
            });

            $('#nameLetterFilter').on('keyup', function () {
                filters.nameLetter = $(this).val().toUpperCase();
                loadStudents();
            });

            $('.name-filter').click(function () {
                $('.name-filter').removeClass('active');
                $(this).addClass('active');
                filters.nameType = $(this).data('filter');
                loadStudents();
            });

            $('#selectAllAvailable').change(function () {
                $('.available-checkbox:not(:disabled)').prop('checked', $(this).prop('checked'));
                updateSelectedCount();
            });

            $('#selectAllEnrolled').change(function () {
                $('.enrolled-checkbox').prop('checked', $(this).prop('checked'));
                updateUnenrollCount();
            });

            $(document).on('change', '.available-checkbox', function () {
                updateSelectedCount();
            });

            $(document).on('change', '.enrolled-checkbox', function () {
                updateUnenrollCount();
            });

            $('#enrollBtn').click(function () {
                enrollStudents();
            });

            $('#unenrollBtn').click(function () {
                unenrollStudents();
            });
        }

        function loadClasses() {
            const classList = $('#classListGroup');
            classList.empty();

            let classesToShow = sampleClasses.filter(cls =>
                cls.name.toLowerCase().includes(filters.classSearch)
            );

            if (classesToShow.length === 0) {
                classList.html('<p class="p-3 text-muted text-center">No classes found</p>');
                return;
            }

            classesToShow.forEach(cls => {
                const enrolledCount = getEnrolledCount(cls.id);

                classList.append(`
                <a href="#" class="list-group-item list-group-item-action" data-class-id="${cls.id}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${cls.name}</strong>
                        </div>
                        <span class="badge badge-primary badge-pill">${enrolledCount}</span>
                    </div>
                </a>
            `);
            });

            $('#classListGroup .list-group-item').off('click').on('click', function (e) {
                e.preventDefault();
                const classId = $(this).data('class-id');
                const classData = sampleClasses.find(c => c.id === classId);
                if (classData) {
                    selectClass(classId, classData.name, $(this));
                }
            });
        }

        function getEnrolledCount(classId) {
            if (!enrollments[currentSchoolYearId] || !enrollments[currentSchoolYearId][classId]) {
                return 0;
            }
            return enrollments[currentSchoolYearId][classId].length;
        }

        function selectClass(classId, className, element) {
            currentClass = { id: classId, name: className };

            $('#classListGroup .list-group-item').removeClass('active');
            element.addClass('active');

            $('#noClassSelected').hide();
            $('#enrollmentSection').show();
            $('#selectedClassName').html(`<i class="fas fa-book mr-2"></i>${className}`);

            resetFilters();
            loadStudents();
        }

        function resetFilters() {
            filters.studentSearch = '';
            filters.grade = '';
            filters.strand = '';
            filters.section = '';
            filters.nameLetter = '';
            filters.nameType = 'first';

            $('#studentSearch').val('');
            $('#gradeFilter').val('');
            $('#strandFilter').val('');
            $('#sectionFilter').val('');
            $('#nameLetterFilter').val('');
            $('.name-filter').removeClass('active');
        }

        function filterStudents(students) {
            return students.filter(student => {
                if (filters.studentSearch &&
                    !student.name.toLowerCase().includes(filters.studentSearch) &&
                    !student.id.toLowerCase().includes(filters.studentSearch)) {
                    return false;
                }

                if (filters.grade && student.grade.toString() !== filters.grade) {
                    return false;
                }

                if (filters.strand && student.strand !== filters.strand) {
                    return false;
                }

                if (filters.section && student.section !== filters.section) {
                    return false;
                }

                if (filters.nameLetter) {
                    const nameParts = student.name.split(' ');
                    if (filters.nameType === 'first') {
                        if (!nameParts[0] || !nameParts[0].toUpperCase().startsWith(filters.nameLetter)) {
                            return false;
                        }
                    } else {
                        const lastName = nameParts[nameParts.length - 1];
                        if (!lastName || !lastName.toUpperCase().startsWith(filters.nameLetter)) {
                            return false;
                        }
                    }
                }

                return true;
            });
        }

        function loadStudents() {
            loadAvailableStudents();
            loadEnrolledStudents();
        }

        function loadAvailableStudents() {
            const tbody = $('#availableStudentsBody');
            tbody.empty();

            const enrolled = enrollments[currentSchoolYearId][currentClass.id] || [];
            const available = sampleStudents.filter(s => !enrolled.includes(s.id));
            const filteredStudents = filterStudents(available);

            $('#availableCount').text(filteredStudents.length);

            if (filteredStudents.length === 0) {
                tbody.html('<tr><td colspan="6" class="text-center text-muted py-4">No available students found</td></tr>');
                $('#selectAllAvailable').prop('checked', false).prop('disabled', true);
                return;
            }

            $('#selectAllAvailable').prop('disabled', false);

            filteredStudents.forEach(student => {
                tbody.append(`
                <tr>
                    <td class="text-center">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input available-checkbox" id="avail-${student.id}" data-id="${student.id}">
                            <label class="custom-control-label" for="avail-${student.id}"></label>
                        </div>
                    </td>
                    <td><strong>${student.name}</strong></td>
                    <td><small class="text-muted">${student.id}</small></td>
                    <td><span class="badge badge-secondary">Grade ${student.grade}</span></td>
                    <td><span class="badge badge-info">${student.strand}</span></td>
                    <td><span class="badge badge-primary">Section ${student.section}</span></td>
                </tr>
            `);
            });

            $('#selectAllAvailable').prop('checked', false);
            updateSelectedCount();
        }

        function loadEnrolledStudents() {
            const tbody = $('#enrolledStudentsBody');
            tbody.empty();

            const enrolled = enrollments[currentSchoolYearId][currentClass.id] || [];
            const enrolledStudents = sampleStudents.filter(s => enrolled.includes(s.id));

            $('#enrolledCount').text(enrolledStudents.length);

            if (enrolledStudents.length === 0) {
                tbody.html('<tr><td colspan="6" class="text-center text-muted py-4">No enrolled students yet</td></tr>');
                $('#selectAllEnrolled').prop('checked', false).prop('disabled', true);
                updateUnenrollCount();
                return;
            }

            $('#selectAllEnrolled').prop('disabled', false);

            enrolledStudents.forEach(student => {
                tbody.append(`
                <tr>
                    <td class="text-center">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input enrolled-checkbox" id="enr-${student.id}" data-id="${student.id}">
                            <label class="custom-control-label" for="enr-${student.id}"></label>
                        </div>
                    </td>
                    <td><strong>${student.name}</strong></td>
                    <td><small class="text-muted">${student.id}</small></td>
                    <td><span class="badge badge-secondary">Grade ${student.grade}</span></td>
                    <td><span class="badge badge-info">${student.strand}</span></td>
                    <td><span class="badge badge-primary">Section ${student.section}</span></td>
                </tr>
            `);
            });

            updateUnenrollCount();
        }

        function updateSelectedCount() {
            const count = $('.available-checkbox:checked').length;
            $('#selectedCount').text(count);
        }

        function updateUnenrollCount() {
            const count = $('.enrolled-checkbox:checked').length;
            $('#unenrollCount').text(count);
        }

        function enrollStudents() {
            const selected = $('.available-checkbox:checked').map(function () {
                return $(this).data('id');
            }).get();

            if (selected.length === 0) {
                alert('Please select at least one student to enroll');
                return;
            }

            if (!enrollments[currentSchoolYearId][currentClass.id]) {
                enrollments[currentSchoolYearId][currentClass.id] = [];
            }

            selected.forEach(studentId => {
                if (!enrollments[currentSchoolYearId][currentClass.id].includes(studentId)) {
                    enrollments[currentSchoolYearId][currentClass.id].push(studentId);
                }
            });

            alert(`${selected.length} student(s) enrolled successfully!`);
            loadStudents();
            loadClasses();
        }

        function unenrollStudents() {
            const selected = $('.enrolled-checkbox:checked').map(function () {
                return $(this).data('id');
            }).get();

            if (selected.length === 0) {
                alert('Please select at least one student to unenroll');
                return;
            }

            if (enrollments[currentSchoolYearId][currentClass.id]) {
                enrollments[currentSchoolYearId][currentClass.id] =
                    enrollments[currentSchoolYearId][currentClass.id].filter(id => !selected.includes(id));
            }

            alert(`${selected.length} student(s) unenrolled successfully!`);
            loadStudents();
            loadClasses();
        }