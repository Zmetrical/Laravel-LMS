        // Sample class data
        const classes = [
            {
                id: 1,
                code: 'ENG-101',
                name: 'English',
                teacher: 'Joe Davey',
                progress: 75
            },
            {
                id: 2,
                code: 'SCI-201',
                name: 'Science',
                teacher: 'Richard Taft',
                progress: 60
            },
            {
                id: 3,
                code: 'HIS-301',
                name: 'History',
                teacher: 'Lisa Ferda',
                progress: 45
            },
            {
                id: 4,
                code: 'MUS-101',
                name: 'Music',
                teacher: 'Roy Johnson',
                progress: 100
            },
            {
                id: 5,
                code: 'MAT-401',
                name: 'Math',
                teacher: 'Matt Wren',
                progress: 30
            },
            {
                id: 6,
                code: 'ART-201',
                name: 'Art',
                teacher: 'Pat Smith',
                progress: 85
            }
        ];

        // Function to create class card HTML
        function createClassCard(classData) {
            const color = 'info';

            return `
          <div class="col-md-6 col-lg-4 mb-4">
            <div class="card card-${color} card-outline h-100">

              <div class="card-body">
                <div class="card-class py-5"></div>

                <hr>

                <h3 class="card-title">
                  <a href="#" class="text-dark font-weight-bold">${classData.name}</a>
                </h3>

                <div class="mb-4 d-flex justify-content-end">
                    <span class="badge badge-${color}">${classData.code}</span>
                </div>
                
                <p class="text-muted mb-4">
                  <i class="fas fa-user-tie mr-1"></i> ${classData.teacher}
                </p>
                
                <div class="">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <small class="text-muted font-weight-bold">Course Progress</small>
                    <small class="text-muted font-weight-bold">${classData.progress}%</small>
                  </div>
                  <div class="progress progress-sm">
                    <div class="progress-bar bg-${color}" role="progressbar" 
                         style="width: ${classData.progress}%" 
                         aria-valuenow="${classData.progress}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;
        }

        // Populate cards on page load
        $(document).ready(function () {
            const container = $('#classCardsContainer');
            classes.forEach(classData => {
                container.append(createClassCard(classData));
            });

            // Add class button handler
            $('#addClassBtn').on('click', function () {
                const newClass = {
                    id: classes.length + 1,
                    code: 'NEW-' + (classes.length + 1) + '01',
                    name: 'New Class',
                    teacher: 'Teacher Name',
                    progress: 0
                };
                classes.push(newClass);
                container.append(createClassCard(newClass));
            });
        });