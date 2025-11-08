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

    public function insert_Student(Request $request)
    {
        // Validate the form data to match the view fields
        $validated = $request->validate([
            'email' => 'required|email|max:255|unique:students,email',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'section' => 'required|exists:sections,id',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                // Generate student number and password
                $studentNumber = $this->generateStudentNumber();
                $defaultPassword = $this->generateStudentPassword($validated['first_name'], $validated['last_name']);

                // Create student record
                $student = Student::create([
                    'student_number' => $studentNumber,
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_name'],
                    'last_name' => $validated['last_name'],
                    'email' => $validated['email'],
                    'student_password' => Hash::make($defaultPassword),
                    'section_id' => $validated['section'],
                    'enrollment_date' => now(),
                ]);

                \Log::info('Student created successfully', [
                    'student_id' => $student->id,
                    'student_number' => $studentNumber,
                    'default_password' => $defaultPassword // Log for admin reference
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Teacher created successfully!',
                // 'redirect_url' => route('profile.teacher', ['id' => $teacherId])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create teacher: ' . $e->getMessage()
            ], 500);
        }
    }

    public function insert_Students(Request $request)
    {
        // Validate the request
        $request->validate([
            'section' => 'required|exists:sections,id',
            'students' => 'required|array|min:1|max:100',
            'students.*.email' => 'required|email|distinct',
            'students.*.firstName' => 'required|string|max:255',
            'students.*.lastName' => 'required|string|max:255',
            'students.*.middleInitial' => 'nullable|string|max:2',
            'students.*.gender' => 'required|in:Male,Female',
        ]);

        try {
            DB::beginTransaction();

            // Check for duplicate emails in database
            $emails = array_column($request->students, 'email');
            $existingEmails = Student::whereIn('email', $emails)->pluck('email')->toArray();

            if (!empty($existingEmails)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate emails found',
                    'errors' => ['These emails already exist: ' . implode(', ', $existingEmails)]
                ], 422);
            }

            $year = date('Y');

            // Get the highest student number for this year
            $lastStudent = Student::where('student_number', 'LIKE', $year . '%')
                ->orderBy('student_number', 'DESC')
                ->first();

            // Extract the sequential number or start from 0
            $lastCount = $lastStudent
                ? (int)substr($lastStudent->student_number, 4)
                : 0;

            // Prepare bulk insert data
            $studentsData = [];
            $passwordMatrixData = [];
            $now = now();

            foreach ($request->students as $index => $studentData) {
                $lastCount++;
                $studentNumber = $year . str_pad($lastCount, 5, '0', STR_PAD_LEFT);

                $password = ucfirst(strtolower($studentData['firstName'])) .
                    ucfirst(strtolower($studentData['lastName'])) . '@' . $year;

                $studentsData[] = [
                    'student_number' => $studentNumber,
                    'student_password' => Hash::make($password),
                    'email' => $studentData['email'],
                    'first_name' => $studentData['firstName'],
                    'middle_name' => $studentData['middleInitial'] ?? null,
                    'last_name' => $studentData['lastName'],
                    'gender' => $studentData['gender'],
                    'section_id' => $request->section,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Prepare password matrix data
                $passwordMatrixData[] = [
                    'student_number' => $studentNumber,
                    'plain_password' => $password,
                ];
            }

            // Insert students
            Student::insert($studentsData);

            // Insert password matrix
            DB::table('student_password_matrix')->insert($passwordMatrixData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($studentsData) . " students created successfully!",
                'count' => count($studentsData)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generateStudentNumber()
    {
        $year = date('Y');
        $lastStudent = Student::whereYear('created_at', $year)->count();

        return $year . str_pad($lastStudent + 1, 5, '0', STR_PAD_LEFT);
    }

    private function generateStudentPassword($firstName, $lastName)
    {
        $password = ucfirst(strtolower($firstName)) . ucfirst(strtolower($lastName)) . '@' . date('Y');

        return $password;
    }

    public function list_Students(Request $request)
    {
        $strands = Strand::all();
        $levels = Level::all();
        $sections = Section::all();

        $students = DB::table('students')
            ->join('sections', 'students.section_id', '=', 'sections.id')
            ->join('levels', 'sections.level_id', '=', 'levels.id')
            ->join('strands', 'sections.strand_id', '=', 'strands.id')
            ->select(
                'students.*',
                'sections.name as section',
                'levels.name as level',
                'strands.code as strand'
            )
            ->get();

        $data = [
            'scripts' => [
                'user_management/list_student.js',
            ],

            'strands' => $strands,
            'levels' => $levels,
            'sections' => $sections,
            'students' => $students
        ];

        return view('admin.user_management.list_student', $data);
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
        // Validate the form data to match the view fields
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'email' => 'required|email|unique:teachers,email|max:255',
            'phone' => 'required|string|max:20',
            'gender' => 'required|string|in:Male,Female',
        ]);

        try {
            // Start database transaction
            DB::beginTransaction();

            // Generate default password (you can customize this)
            $defaultPassword = 'Teacher@' . date('Y');

            // Insert teacher into teachers table
            $teacher = Teacher::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'middle_name' => $request->middle_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'user' => $request->first_name . " " . $request->last_name,
                'password' => Hash::make($defaultPassword),
                'profile_image' => '', // Default empty or handle file upload
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert plain password into teacher_password_matrix
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
                    'default_password' => $defaultPassword, // Send this to show in UI
                ]
            ], 201);
        } catch (Exception $e) {
            // Rollback transaction on error
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
            ->select(
                'teachers.*',
            )
            ->get();

        $data = [
            'scripts' => [
                'user_management/list_teacher.js',
            ],

            'teachers' => $teachers
        ];

        return view('admin.user_management.list_teacher', $data);
    }
}
