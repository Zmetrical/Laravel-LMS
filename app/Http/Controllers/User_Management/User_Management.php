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
    //  Create Student Page
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
            $lastCount = Student::whereYear('enrollment_date', $year)->count();

            // Prepare bulk insert data
            $studentsData = [];
            $now = now();

            foreach ($request->students as $index => $studentData) {
                $lastCount++;
                $studentNumber = $year . str_pad($lastCount, 5, '0', STR_PAD_LEFT);

                $password = ucfirst(strtolower($studentData['firstName'])) .
                    ucfirst(strtolower($studentData['lastName'])) . '@' . $year;

                $studentsData[] = [
                    'student_number' => $studentNumber,
                    'student_password' => Hash::make(value: $password),
                    'email' => $studentData['email'],
                    'first_name' => $studentData['firstName'],
                    'middle_name' => $studentData['middleInitial'] ?? null,
                    'last_name' => $studentData['lastName'],
                    'gender' => $studentData['gender'],
                    'section_id' => $request->section,
                ];
            }
            Student::insert($studentsData);

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


    // ---------------------------------------------------------------------------
    //  List Student Page
    // ---------------------------------------------------------------------------

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
    //  Create Teacher Page
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
            'email' => 'required|email|unique:users,email|max:255',
            'phone' => 'required|string|max:20',
        ]);

        try {
            // Start database transaction
            DB::beginTransaction();

            // Generate default password (you can customize this)
            $defaultPassword = 'Teacher@' . date('Y');

            // Insert teacher into users table
            $teacher = Teacher::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'middle_name' => $request->middle_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'gender' => $request->gender,

                'user' => $request->first_name . " " . $request->last_name,
                'password' => Hash::make($defaultPassword),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('Student created successfully', [
                'student_id' => $teacher->id,
                'student_number' => $teacher->user,
                'default_password' => $teacher->password,
            ]);


            return response()->json([
                'success' => true,
                'message' => 'Teacher created successfully!',
                // 'redirect_url' => route('profile.teacher', ['id' => $teacherId])
            ], 201);

        } catch (Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create teacher: ' . $e->getMessage()
            ], 500);
        }
    }

    // ---------------------------------------------------------------------------
    //  List Student Page
    // ---------------------------------------------------------------------------

    public function list_teacher(Request $request)
    {

        $data = [
            'scripts' => [
                'user_management/list_teacher.js',
            ]
        ];

        return view('admin.user_management.list_teacher', $data);
    }
}
