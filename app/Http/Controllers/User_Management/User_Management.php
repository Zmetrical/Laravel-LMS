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

    public function create_student(Request $request)
    {
        $strands = Strand::all();
        $levels = Level::all();
        $sections = Section::all();

        $data = [
            'scripts' => [
                'user_management/create_student.js',
            ],

            'strands' => $strands,
            'levels' => $levels,
            'sections' => $sections,

        ];

        return view('admin.user_management.create_student', $data);
    }

    public function get_Sections(Request $request)
    {
        $query = Section::query();

        if ($request->strand_id) {
            $query->where('strand_id', $request->strand_id);
        }
        if ($request->level_id) {
            $query->where('level_id', $request->level_id);
        }

        return response()->json($query->get());
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
            'section' => 'required|exists:sections,id',
            'students' => 'required|array|min:1|max:100',
            'students.*.email' => 'nullable|email|distinct',
            'students.*.firstName' => 'required|string|max:255',
            'students.*.lastName' => 'required|string|max:255',
            'students.*.middleInitial' => 'nullable|string|max:50',
            'students.*.gender' => 'required|in:Male,Female,M,F',
            'students.*.studentType' => 'required|in:regular,irregular',
        ]);

        try {
            DB::beginTransaction();

            // Get active semester
            $activeSemester = DB::table('semesters')->where('status', 'active')->first();
            if (!$activeSemester) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active semester found'
                ], 422);
            }

            $emails = array_filter(array_column($request->students, 'email'));
            
            if (!empty($emails)) {
                $existingEmails = Student::whereIn('email', $emails)->pluck('email')->toArray();

                if (!empty($existingEmails)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Duplicate emails found',
                        'errors' => ['These emails already exist: ' . implode(', ', $existingEmails)]
                    ], 422);
                }
            }

            $year = date('Y');

            $lastStudent = Student::where('student_number', 'LIKE', $year . '%')
                ->orderBy('student_number', 'DESC')
                ->first();

            $lastCount = $lastStudent
                ? (int)substr($lastStudent->student_number, 4)
                : 0;

            $studentsData = [];
            $passwordMatrixData = [];
            $enrollmentData = [];
            $now = now();

            foreach ($request->students as $index => $studentData) {
                $lastCount++;
                $studentNumber = $year . str_pad($lastCount, 5, '0', STR_PAD_LEFT);

                $password = $this->generateStudentPassword();

                $gender = $studentData['gender'];
                if ($gender === 'M') {
                    $gender = 'Male';
                } elseif ($gender === 'F') {
                    $gender = 'Female';
                }

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
                    'section_id' => $request->section,
                    'student_type' => $studentData['studentType'],
                    'enrollment_date' => now(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $passwordMatrixData[] = [
                    'student_number' => $studentNumber,
                    'plain_password' => $password,
                ];

                // Create enrollment record for active semester
                $enrollmentData[] = [
                    'student_number' => $studentNumber,
                    'semester_id' => $activeSemester->id,
                    'section_id' => $request->section,
                    'enrollment_status' => 'enrolled',
                    'enrollment_date' => now(),
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            Student::insert($studentsData);
            DB::table('student_password_matrix')->insert($passwordMatrixData);
            DB::table('student_semester_enrollment')->insert($enrollmentData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($studentsData) . " student(s) created successfully!",
                'count' => count($studentsData)
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
                DB::raw("CONCAT(sy.code, ' - ', s.name) as display_name")
            )
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('s.code', 'asc')
            ->get();

        $students = DB::table('students')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->join('levels', 'sections.level_id', '=', 'levels.id')
            ->join('strands', 'sections.strand_id', '=', 'strands.id')
            ->leftJoin('student_semester_enrollment as sse', function($join) {
                $join->on('students.student_number', '=', 'sse.student_number')
                    ->whereIn('sse.semester_id', function($query) {
                        $query->select('id')
                            ->from('semesters')
                            ->where('status', 'active');
                    });
            })
            ->select(
                'students.*',
                'sections.name as section',
                'levels.name as level',
                'strands.code as strand',
                'sse.semester_id',
                'sse.enrollment_status'
            )
            ->get();

        $data = [
            'scripts' => [
                'user_management/list_student.js',
            ],

            'strands' => $strands,
            'levels' => $levels,
            'sections' => $sections,
            'semesters' => $semesters,
            'students' => $students
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

            $teacher = Teacher::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'middle_name' => $request->middle_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'user' => $request->first_name . " " . $request->last_name,
                'password' => Hash::make($defaultPassword),
                'profile_image' => '',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('teacher_password_matrix')->insert([
                'teacher_id' => $teacher->id,
                'plain_password' => $defaultPassword,
            ]);

            \Log::info('Teacher created successfully', [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->first_name . " " . $teacher->last_name,
                'email' => $teacher->email,
                'default_password' => $defaultPassword,
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