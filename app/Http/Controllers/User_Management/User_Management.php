<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\MainController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Models\User_Management\Strand;
use App\Models\User_Management\Section;
use App\Models\User_Management\Level;
use App\Models\User_Management\Student;
use App\Models\User_Management\Teacher;
use App\Models\User_Management\StudentSemesterEnrollment;
use Illuminate\Support\Str;

use Exception;

class User_Management extends MainController
{
    // ---------------------------------------------------------------------------
    //  Students Page
    // ---------------------------------------------------------------------------

    // public function create_student(Request $request)
    // {
    //     $strands = Strand::all();
    //     $levels = Level::all();
    //     $sections = Section::all();

    //     $data = [
    //         'scripts' => [
    //             'user_management/create_student.js',
    //         ],

    //         'strands' => $strands,
    //         'levels' => $levels,
    //         'sections' => $sections,

    //     ];

    //     return view('admin.user_management.create_student', $data);
    // }

    public function create_student(Request $request)
{
    $strands = Strand::all();
    $levels = Level::all();
    
    // Get active semester
    $activeSemester = DB::table('semesters')->where('status', 'active')->first();
    
    // Get sections with capacity info
    $sections = DB::table('sections')
        ->join('strands', 'sections.strand_id', '=', 'strands.id')
        ->join('levels', 'sections.level_id', '=', 'levels.id')
        ->select(
            'sections.id',
            'sections.code',
            'sections.name',
            'sections.capacity',
            'sections.strand_id',
            'sections.level_id',
            'strands.code as strand_code',
            'levels.name as level_name'
        )
        ->where('sections.status', 1)
        ->orderBy('sections.name')
        ->get()
        ->map(function($section) use ($activeSemester) {
            // Count enrolled students in active semester
            $enrolled = DB::table('student_semester_enrollment')
                ->where('section_id', $section->id)
                ->where('semester_id', $activeSemester->id)
                ->where('enrollment_status', 'enrolled')
                ->count();
            
            $section->enrolled_count = $enrolled;
            $section->available_slots = $section->capacity - $enrolled;
            $section->is_full = $enrolled >= $section->capacity;
            
            return $section;
        });

    $data = [
        'scripts' => [
            'user_management/create_student.js',
        ],
        'strands' => $strands,
        'levels' => $levels,
        'sections' => $sections,
        'activeSemester' => $activeSemester,
    ];

    return view('admin.user_management.create_student', $data);
}

    // public function get_Sections(Request $request)
    // {
    //     $query = Section::query();

    //     if ($request->strand_id) {
    //         $query->where('strand_id', $request->strand_id);
    //     }
    //     if ($request->level_id) {
    //         $query->where('level_id', $request->level_id);
    //     }

    //     return response()->json($query->get());
    // }

    public function get_Sections(Request $request)
{
    // Get active semester
    $activeSemester = DB::table('semesters')->where('status', 'active')->first();
    
    if (!$activeSemester) {
        return response()->json([
            'success' => false,
            'message' => 'No active semester found'
        ], 422);
    }
    
    $query = DB::table('sections')
        ->join('strands', 'sections.strand_id', '=', 'strands.id')
        ->join('levels', 'sections.level_id', '=', 'levels.id')
        ->select(
            'sections.id',
            'sections.code',
            'sections.name',
            'sections.capacity',
            'sections.strand_id',
            'sections.level_id',
            'strands.code as strand_code',
            'levels.name as level_name'
        )
        ->where('sections.status', 1);

    if ($request->strand_id) {
        $query->where('sections.strand_id', $request->strand_id);
    }
    if ($request->level_id) {
        $query->where('sections.level_id', $request->level_id);
    }

    $sections = $query->orderBy('sections.name')->get()
        ->map(function($section) use ($activeSemester) {
            // Count enrolled students
            $enrolled = DB::table('student_semester_enrollment')
                ->where('section_id', $section->id)
                ->where('semester_id', $activeSemester->id)
                ->where('enrollment_status', 'enrolled')
                ->count();
            
            $section->enrolled_count = $enrolled;
            $section->available_slots = $section->capacity - $enrolled;
            $section->is_full = $enrolled >= $section->capacity;
            
            return $section;
        });

    return response()->json([
        'success' => true,
        'sections' => $sections
    ]);
}

    public function insert_student(Request $request)
    {
        $validated = $request->validate([
            'email' => 'nullable|email|max:255|unique:students,email',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'section' => 'required|exists:sections,id',
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                $studentNumber = $this->generateStudentNumber();
                $defaultPassword = $this->generateStudentPassword();

                $middleInitial = null;
                if (!empty($validated['middle_name'])) {
                    $middleInitial = $this->processMiddleInitial($validated['middle_name']);
                }

                // Get active semester
                $activeSemester = DB::table('semesters')->where('status', 'active')->first();
                if (!$activeSemester) {
                    throw new Exception('No active semester found');
                }

                $student = Student::create([
                    'student_number' => $studentNumber,
                    'first_name' => $validated['first_name'],
                    'middle_name' => $middleInitial,
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'] ?? '',
                    'student_password' => Hash::make($defaultPassword),
                    'section_id' => $validated['section'],
                    'student_type' => 'regular',
                    'enrollment_date' => now(),
                ]);

                // Create semester enrollment record
                StudentSemesterEnrollment::create([
                    'student_number' => $studentNumber,
                    'semester_id' => $activeSemester->id,
                    'section_id' => $validated['section'],
                    'enrollment_status' => 'enrolled',
                    'enrollment_date' => now()
                ]);

                // Store plain password
                DB::table('student_password_matrix')->insert([
                    'student_number' => $studentNumber,
                    'plain_password' => $defaultPassword
                ]);

                \Log::info('Student created successfully', [
                    'student_id' => $student->id,
                    'student_number' => $studentNumber,
                    'semester_id' => $activeSemester->id
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Student created successfully!',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create student: ' . $e->getMessage()
            ], 500);
        }
    }

public function insert_students(Request $request)
{
    $request->validate([
        'strand_id' => 'required|exists:strands,id',
        'level_id' => 'required|exists:levels,id',
        'students' => 'required|array|min:1|max:100',
        'students.*.email' => 'nullable|email|distinct',
        'students.*.firstName' => 'required|string|max:255',
        'students.*.lastName' => 'required|string|max:255',
        'students.*.middleInitial' => 'nullable|string|max:50',
        'students.*.gender' => 'required|in:Male,Female,M,F',
        'students.*.studentType' => 'required|in:regular,irregular',
        'students.*.parentEmail' => 'nullable|email',
        'students.*.parentFirstName' => 'nullable|string|max:255',
        'students.*.parentLastName' => 'nullable|string|max:255',
    ]);

    try {
        DB::beginTransaction();

        // ... [Keep existing code for semester check and section distribution] ...

        $studentsData = [];
        $passwordMatrixData = [];
        $enrollmentData = [];
        $guardianLinks = []; // NEW: Store guardian links to process
        $now = now();

        // Distribute students across available sections
        $sectionIndex = 0;
        $currentSectionSlots = $availableSections[$sectionIndex]->available_slots;
        $currentSectionId = $availableSections[$sectionIndex]->id;

        foreach ($request->students as $index => $studentData) {
            // Check if current section is full, move to next
            if ($currentSectionSlots <= 0) {
                $sectionIndex++;
                $currentSectionId = $availableSections[$sectionIndex]->id;
                $currentSectionSlots = $availableSections[$sectionIndex]->available_slots;
            }

            $lastCount++;
            $studentNumber = $year . str_pad($lastCount, 5, '0', STR_PAD_LEFT);
            $password = $this->generateStudentPassword();

            $gender = $studentData['gender'];
            if ($gender === 'M') $gender = 'Male';
            elseif ($gender === 'F') $gender = 'Female';

            $middleInitial = null;
            if (!empty($studentData['middleInitial'])) {
                $middleInitial = $this->processMiddleInitial($studentData['middleInitial']);
            }

            $studentsData[] = [
                'student_number' => $studentNumber,
                'student_password' => Hash::make($password),
                'email' => $studentData['email'] ?? '',
                'first_name' => $studentData['firstName'],
                'middle_name' => $middleInitial,
                'last_name' => $studentData['lastName'],
                'gender' => $gender,
                'section_id' => $currentSectionId,
                'student_type' => $studentData['studentType'],
                'enrollment_date' => now(),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $passwordMatrixData[] = [
                'student_number' => $studentNumber,
                'plain_password' => $password,
            ];

            $enrollmentData[] = [
                'student_number' => $studentNumber,
                'semester_id' => $activeSemester->id,
                'section_id' => $currentSectionId,
                'enrollment_status' => 'enrolled',
                'enrollment_date' => now(),
                'created_at' => $now,
                'updated_at' => $now
            ];

            // NEW: Store guardian data for processing
            if (!empty($studentData['parentEmail']) || !empty($studentData['parentFirstName']) || !empty($studentData['parentLastName'])) {
                $guardianLinks[] = [
                    'student_number' => $studentNumber,
                    'email' => $studentData['parentEmail'] ?? '',
                    'firstName' => $studentData['parentFirstName'] ?? '',
                    'lastName' => $studentData['parentLastName'] ?? ''
                ];
            }

            $currentSectionSlots--;
        }

        // Insert students
        Student::insert($studentsData);
        DB::table('student_password_matrix')->insert($passwordMatrixData);
        DB::table('student_semester_enrollment')->insert($enrollmentData);

        // NEW: Process guardians
        foreach ($guardianLinks as $guardianData) {
            $this->createOrLinkGuardian([
                'email' => $guardianData['email'],
                'firstName' => $guardianData['firstName'],
                'lastName' => $guardianData['lastName']
            ], $guardianData['student_number']);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => count($studentsData) . " student(s) created successfully and distributed across sections!",
            'count' => count($studentsData),
            'guardians_linked' => count($guardianLinks)
        ]);
    } catch (\Exception $e) {
        DB::rollBack();

        \Log::error('Failed to create students', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage()
        ], 500);
    }
}

private function createOrLinkGuardian($guardianData, $studentNumber)
{
    if (empty($guardianData['email']) && empty($guardianData['firstName']) && empty($guardianData['lastName'])) {
        return null; // No guardian info provided
    }

    $guardian = null;
    
    // Check if guardian exists by email
    if (!empty($guardianData['email'])) {
        $guardian = DB::table('guardians')->where('email', $guardianData['email'])->first();
    }

    // If guardian doesn't exist, create new one
    if (!$guardian) {
        $accessToken = Str::random(64);
        
        $guardianId = DB::table('guardians')->insertGetId([
            'email' => $guardianData['email'] ?? '',
            'first_name' => $guardianData['firstName'] ?? '',
            'last_name' => $guardianData['lastName'] ?? '',
            'access_token' => $accessToken,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    } else {
        $guardianId = $guardian->id;
    }

    // Link guardian to student (check if link doesn't already exist)
    $existingLink = DB::table('guardian_students')
        ->where('guardian_id', $guardianId)
        ->where('student_number', $studentNumber)
        ->exists();

    if (!$existingLink) {
        DB::table('guardian_students')->insert([
            'guardian_id' => $guardianId,
            'student_number' => $studentNumber,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    return $guardianId;
}

    private function processMiddleInitial($middleName)
    {
        if (empty($middleName)) {
            return null;
        }

        $middleName = trim(preg_replace('/\s+/', ' ', $middleName));
        $words = explode(' ', $middleName);
        
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
                if (strlen($initials) >= 3) {
                    break;
                }
            }
        }
        
        return substr($initials, 0, 3);
    }

    private function generateStudentNumber()
    {
        $year = date('Y');
        $lastStudent = Student::whereYear('created_at', $year)->count();

        return $year . str_pad($lastStudent + 1, 5, '0', STR_PAD_LEFT);
    }

    private function generateStudentPassword()
    {
        return Str::random(10);
    }

public function list_students(Request $request)
{
    $strands = Strand::all();
    $levels = Level::all();
    $sections = Section::all();
    
    // Get semesters for filter
    $semesters = DB::table('semesters as s')
        ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
        ->select(
            's.id',
            's.name',
            's.code',
            'sy.code as school_year_code',
            DB::raw("CONCAT(sy.code, ' - ', s.name) as display_name"),
            's.status'
        )
        ->orderBy('sy.year_start', 'desc')
        ->orderBy('s.code', 'asc')
        ->get();

    // Get active semester for default selection
    $activeSemester = $semesters->where('status', 'active')->first();

    // Get ALL enrollment records - each enrollment is a separate row
    $students = DB::table('students')
        ->join('sections', 'students.section_id', '=', 'sections.id')
        ->join('levels', 'sections.level_id', '=', 'levels.id')
        ->join('strands', 'sections.strand_id', '=', 'strands.id')
        ->leftJoin('student_semester_enrollment as sse', 'students.student_number', '=', 'sse.student_number')
        ->leftJoin('semesters as sem', 'sse.semester_id', '=', 'sem.id')
        ->leftJoin('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
        ->leftJoin('sections as enrolled_section', 'sse.section_id', '=', 'enrolled_section.id')
        ->select(
            'students.id',
            'students.student_number',
            'students.first_name',
            'students.middle_name',
            'students.last_name',
            'students.student_type',
            'sections.name as current_section',
            'levels.name as level',
            'strands.code as strand',
            'sse.semester_id',
            'enrolled_section.name as enrolled_section_name',
            'sse.enrollment_status',
            'sse.enrollment_date',
            DB::raw("CONCAT(sy.code, ' - ', sem.name) as semester_display")
        )
        ->orderBy('students.last_name')
        ->orderBy('students.first_name')
        ->orderBy('sem.id', 'desc')
        ->get();

    $data = [
        'scripts' => [
            'user_management/list_student.js',
        ],
        'strands' => $strands,
        'levels' => $levels,
        'sections' => $sections,
        'semesters' => $semesters,
        'students' => $students,
        'activeSemester' => $activeSemester
    ];

    return view('admin.user_management.list_student', $data);
}
    public function getSectionsForFilter(Request $request)
    {
        try {
            $query = DB::table('sections')
                ->join('levels', 'sections.level_id', '=', 'levels.id')
                ->join('strands', 'sections.strand_id', '=', 'strands.id')
                ->select(
                    'sections.id',
                    'sections.code',
                    'sections.name'
                )
                ->where('sections.status', 1);

            if ($request->has('strand_code') && $request->strand_code != '') {
                $query->where('strands.code', $request->strand_code);
            }

            if ($request->has('level_name') && $request->level_name != '') {
                $query->where('levels.name', $request->level_name);
            }

            $sections = $query->orderBy('sections.name', 'asc')->get();

            return response()->json($sections);
            
        } catch (Exception $e) {
            \Log::error('Failed to get sections for filter', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load sections.'
            ], 500);
        }
    }

    // ---------------------------------------------------------------------------
    //  Teachers Page
    // ---------------------------------------------------------------------------

    public function create_teacher(Request $request)
    {
        $data = [
            'scripts' => [
                'user_management/create_teacher.js',
            ],

        ];

        return view('admin.user_management.create_teacher', $data);
    }

public function insert_teacher(Request $request)
{
    $validated = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'middle_name' => 'nullable|string|max:255',
        'email' => 'required|email|unique:teachers,email|max:255',
        'phone' => 'required|string|max:20',
        'gender' => 'required|string|in:Male,Female',
    ]);

    try {
        DB::beginTransaction();

        $defaultPassword = 'Teacher@' . date('Y');
        $plainPasscode = $this->generateTeacherPasscode();

        $teacher = Teacher::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'middle_name' => $request->middle_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'gender' => $request->gender,
            'user' => $request->first_name . " " . $request->last_name,
            'password' => Hash::make($defaultPassword),
            'passcode' => Hash::make($plainPasscode),
            'profile_image' => '',
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Store both password and passcode in plain text for admin reference
        DB::table('teacher_password_matrix')->updateOrInsert(
            ['teacher_id' => $teacher->id],
            [
                'teacher_id' => $teacher->id,
                'plain_password' => $defaultPassword,
                'plain_passcode' => $plainPasscode,
            ]
        );

        \Log::info('Teacher created successfully', [
            'teacher_id' => $teacher->id,
            'teacher_name' => $teacher->first_name . " " . $teacher->last_name,
            'email' => $teacher->email,
            'default_password' => $defaultPassword,
            'passcode' => $plainPasscode,
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Teacher created successfully!',
            'data' => [
                'teacher_id' => $teacher->id,
                'name' => $teacher->first_name . " " . $teacher->last_name,
                'email' => $teacher->email,
                'default_password' => $defaultPassword,
                'passcode' => $plainPasscode,
            ]
        ], 201);
    } catch (Exception $e) {
        DB::rollBack();

        \Log::error('Failed to create teacher', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create teacher: ' . $e->getMessage()
        ], 500);
    }
}

private function generateTeacherPasscode()
{
    return strtoupper(Str::random(6));
}


    public function list_teacher(Request $request)
    {
        $teachers = DB::table('teachers')
            ->select('teachers.*')
            ->get()
            ->map(function($teacher) {
                $classes = DB::table('teacher_class_matrix')
                    ->join('classes', 'teacher_class_matrix.class_id', '=', 'classes.id')
                    ->where('teacher_class_matrix.teacher_id', $teacher->id)
                    ->select(
                        'classes.id',
                        'classes.class_code',
                        'classes.class_name'
                    )
                    ->get();
                
                $teacher->classes = $classes;
                return $teacher;
            });

        $classes = DB::table('classes')
            ->select('id','class_code', 'class_name')
            ->orderBy('class_code')
            ->get();

        $data = [
            'scripts' => [
                'user_management/list_teacher.js',
            ],
            'teachers' => $teachers,
            'classes' => $classes
        ];

        return view('admin.user_management.list_teacher', $data);
    }
}