<?php

namespace App\Http\Controllers\User_Management;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Models\User_Management\Strand;
use App\Models\User_Management\Section;
use App\Models\User_Management\Level;
use App\Models\User_Management\Student;
use App\Models\User_Management\Teacher;
use App\Models\StudentSemester;
use Illuminate\Support\Str;
use App\Traits\AuditLogger;

use Exception;

class User_Management extends Controller
{
    use AuditLogger;

    protected $guardianEmailController;

    public function __construct()
    {
        $this->guardianEmailController = new GuardianEmailController();
    }

    // ---------------------------------------------------------------------------
    //  Students Page
    // ---------------------------------------------------------------------------

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
        $adminGuard = Auth::guard('admin');

        $validated = $request->validate([
            'email' => 'nullable|email|max:255|unique:students,email',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'section' => 'required|exists:sections,id',
        ]);

        try {
            DB::beginTransaction();

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

            // Get section details for audit
            $section = DB::table('sections')
                ->join('strands', 'sections.strand_id', '=', 'strands.id')
                ->join('levels', 'sections.level_id', '=', 'levels.id')
                ->where('sections.id', $validated['section'])
                ->select('sections.name as section_name', 'strands.code as strand', 'levels.name as level')
                ->first();

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
            StudentSemester::create([
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

            // Audit log - format values without sensitive data
            $auditValues = [
                'student_number' => $studentNumber,
                'first_name' => $validated['first_name'],
                'middle_name' => $middleInitial,
                'last_name' => $validated['last_name'],
                'email' => $validated['email'] ?? '',
                'section_id' => $validated['section'],
                'section_name' => $section->section_name,
                'strand' => $section->strand,
                'level' => $section->level,
                'student_type' => 'regular',
                'semester_id' => $activeSemester->id,
                'enrollment_status' => 'enrolled',
            ];

            $this->logAudit(
                'created',
                'students',
                $studentNumber,
                "Created student: {$validated['first_name']} {$validated['last_name']} ({$studentNumber}) - {$section->section_name}",
                null,
                $auditValues
            );

            \Log::info('Student created successfully', [
                'student_id' => $student->id,
                'student_number' => $studentNumber,
                'semester_id' => $activeSemester->id
            ]);

            DB::commit();

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
            'strand_id'                    => 'required|exists:strands,id',
            'level_id'                     => 'required|exists:levels,id',
            'preferred_section_id'         => 'required|exists:sections,id',
            'students'                     => 'required|array|min:1|max:100',
            'students.*.email'             => 'required|email|distinct',
            'students.*.firstName'         => 'required|string|max:255',
            'students.*.lastName'          => 'required|string|max:255',
            'students.*.middleInitial'     => 'required|string|max:50',
            'students.*.gender'            => 'required|in:Male,Female,M,F',
            'students.*.studentType'       => 'required|in:regular,irregular',
            'students.*.parentEmail'       => 'required|email',
            'students.*.parentFirstName'   => 'required|string|max:255',
            'students.*.parentLastName'    => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Get active semester
            $activeSemester = DB::table('semesters')->where('status', 'active')->first();
            if (!$activeSemester) {
                throw new Exception('No active semester found');
            }

            $strand = DB::table('strands')->where('id', $request->strand_id)->first();
            $level  = DB::table('levels')->where('id', $request->level_id)->first();

            // Verify preferred section belongs to the chosen strand & level
            $preferredSection = DB::table('sections')
                ->where('id', $request->preferred_section_id)
                ->where('strand_id', $request->strand_id)
                ->where('level_id', $request->level_id)
                ->where('status', 1)
                ->first();

            if (!$preferredSection) {
                throw new Exception('Selected section does not match the chosen strand and level');
            }

            // Build the ordered section list: preferred section FIRST, then the rest alphabetically
            $otherSections = DB::table('sections')
                ->where('strand_id', $request->strand_id)
                ->where('level_id', $request->level_id)
                ->where('status', 1)
                ->where('id', '!=', $request->preferred_section_id)
                ->orderBy('name')
                ->get();

            $allSections = collect([$preferredSection])->merge($otherSections);

            // Attach live available slots to each section
            $allSections = $allSections->map(function($section) use ($activeSemester) {
                $enrolled = DB::table('student_semester_enrollment')
                    ->where('section_id', $section->id)
                    ->where('semester_id', $activeSemester->id)
                    ->where('enrollment_status', 'enrolled')
                    ->count();

                $section->enrolled_count  = $enrolled;
                $section->available_slots = $section->capacity - $enrolled;
                return $section;
            })->filter(fn($s) => $s->available_slots > 0)->values();

            if ($allSections->isEmpty()) {
                throw new Exception('No sections available with capacity for the selected strand and level');
            }

            $totalAvailableSlots = $allSections->sum('available_slots');
            $studentsToAdd       = count($request->students);

            if ($studentsToAdd > $totalAvailableSlots) {
                return response()->json([
                    'success'  => false,
                    'message'  => "Insufficient capacity across all sections for the selected strand and level.",
                    'required' => $studentsToAdd,
                    'available'=> $totalAvailableSlots,
                    'shortage' => $studentsToAdd - $totalAvailableSlots,
                ], 422);
            }

            // Generate student numbers
            $year        = date('Y');
            $lastStudent = Student::whereYear('created_at', $year)
                ->orderBy('student_number', 'desc')
                ->first();

            $lastCount = $lastStudent ? intval(substr($lastStudent->student_number, 4)) : 0;

            $studentsData       = [];
            $passwordMatrixData = [];
            $enrollmentData     = [];
            $guardianLinks      = [];
            $auditEntries       = [];
            $now                = now();

            // Distribute: fill preferred section first, overflow into others in order
            $sectionIndex        = 0;
            $currentSection      = $allSections[$sectionIndex];
            $currentSectionSlots = $currentSection->available_slots;

            foreach ($request->students as $studentData) {
                // Advance to next section if current is full
                if ($currentSectionSlots <= 0) {
                    $sectionIndex++;
                    if ($sectionIndex >= $allSections->count()) {
                        throw new Exception('Unexpected error: ran out of sections during distribution');
                    }
                    $currentSection      = $allSections[$sectionIndex];
                    $currentSectionSlots = $currentSection->available_slots;
                }

                $lastCount++;
                $studentNumber = $year . str_pad($lastCount, 5, '0', STR_PAD_LEFT);
                $password      = $this->generateStudentPassword();

                $gender = $studentData['gender'];
                if ($gender === 'M') $gender = 'Male';
                elseif ($gender === 'F') $gender = 'Female';

                $middleInitial = $this->processMiddleInitial($studentData['middleInitial']);

                $studentsData[] = [
                    'student_number'   => $studentNumber,
                    'student_password' => Hash::make($password),
                    'email'            => $studentData['email'],
                    'first_name'       => $studentData['firstName'],
                    'middle_name'      => $middleInitial,
                    'last_name'        => $studentData['lastName'],
                    'gender'           => $gender,
                    'section_id'       => $currentSection->id,
                    'student_type'     => $studentData['studentType'],
                    'enrollment_date'  => now(),
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ];

                $passwordMatrixData[] = [
                    'student_number' => $studentNumber,
                    'plain_password' => $password,
                ];

                $enrollmentData[] = [
                    'student_number'    => $studentNumber,
                    'semester_id'       => $activeSemester->id,
                    'section_id'        => $currentSection->id,
                    'enrollment_status' => 'enrolled',
                    'enrollment_date'   => now(),
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];

                $guardianLinks[] = [
                    'student_number' => $studentNumber,
                    'email'          => $studentData['parentEmail'],
                    'firstName'      => $studentData['parentFirstName'],
                    'lastName'       => $studentData['parentLastName'],
                ];

                $auditEntries[] = [
                    'student_number'    => $studentNumber,
                    'first_name'        => $studentData['firstName'],
                    'middle_name'       => $middleInitial,
                    'last_name'         => $studentData['lastName'],
                    'email'             => $studentData['email'],
                    'gender'            => $gender,
                    'section_id'        => $currentSection->id,
                    'section_name'      => $currentSection->name,
                    'strand'            => $strand->code,
                    'level'             => $level->name,
                    'student_type'      => $studentData['studentType'],
                    'parent_email'      => $studentData['parentEmail'],
                    'parent_first_name' => $studentData['parentFirstName'],
                    'parent_last_name'  => $studentData['parentLastName'],
                ];

                $currentSectionSlots--;
            }

            // Bulk inserts
            Student::insert($studentsData);
            DB::table('student_password_matrix')->insert($passwordMatrixData);
            DB::table('student_semester_enrollment')->insert($enrollmentData);

            // Process guardians + send verification emails
            $guardianCount       = 0;
            $newGuardiansCreated = [];

            foreach ($guardianLinks as $guardianData) {
                $result = $this->createOrLinkGuardian([
                    'email'     => $guardianData['email'],
                    'firstName' => $guardianData['firstName'],
                    'lastName'  => $guardianData['lastName'],
                ], $guardianData['student_number']);

                if ($result['success']) {
                    $guardianCount++;
                    if ($result['is_new']) {
                        $newGuardiansCreated[] = $result['guardian_id'];
                    }
                }
            }

            $emailResults = ['sent' => 0, 'failed' => 0];

            foreach ($newGuardiansCreated as $guardianId) {
                $emailResult = $this->guardianEmailController->sendVerificationEmail($guardianId, true);
                if ($emailResult['success']) {
                    $emailResults['sent']++;
                } else {
                    $emailResults['failed']++;
                    \Log::warning('Failed to send automatic verification email', [
                        'guardian_id' => $guardianId,
                        'message'     => $emailResult['message'],
                    ]);
                }
            }

            // Audit log
            $this->logAudit(
                'created',
                'students',
                null,
                "Batch created {$studentsToAdd} students for {$strand->code} - {$level->name}, starting in {$preferredSection->name}",
                null,
                [
                    'strand_id'                  => $request->strand_id,
                    'strand_code'                => $strand->code,
                    'level_id'                   => $request->level_id,
                    'level_name'                 => $level->name,
                    'preferred_section_id'       => $preferredSection->id,
                    'preferred_section_name'     => $preferredSection->name,
                    'total_students'             => $studentsToAdd,
                    'semester_id'                => $activeSemester->id,
                    'guardians_linked'           => $guardianCount,
                    'new_guardians_created'      => count($newGuardiansCreated),
                    'verification_emails_sent'   => $emailResults['sent'],
                    'verification_emails_failed' => $emailResults['failed'],
                    'students'                   => $auditEntries,
                ]
            );

            DB::commit();

            $message = count($studentsData) . " student(s) enrolled successfully, starting in {$preferredSection->name}!";

            if ($emailResults['sent'] > 0) {
                $message .= " {$emailResults['sent']} verification email(s) sent automatically.";
            }
            if ($emailResults['failed'] > 0) {
                $message .= " Note: {$emailResults['failed']} email(s) failed to send.";
            }

            return response()->json([
                'success'          => true,
                'message'          => $message,
                'count'            => count($studentsData),
                'guardians_linked' => $guardianCount,
                'new_guardians'    => count($newGuardiansCreated),
                'emails_sent'      => $emailResults['sent'],
                'emails_failed'    => $emailResults['failed'],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create students', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function createOrLinkGuardian($guardianData, $studentNumber)
    {
        $guardian = null;
        $isNew = false;
        
        // Check if guardian exists by email
        $guardian = DB::table('guardians')->where('email', $guardianData['email'])->first();

        // If guardian doesn't exist, create new one
        if (!$guardian) {
            $accessToken = Str::random(64);
            
            $guardianId = DB::table('guardians')->insertGetId([
                'email' => $guardianData['email'],
                'first_name' => $guardianData['firstName'],
                'last_name' => $guardianData['lastName'],
                'access_token' => $accessToken,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $isNew = true;

            // Audit log for guardian creation
            $this->logAudit(
                'created',
                'guardians',
                (string)$guardianId,
                "Created guardian: {$guardianData['firstName']} {$guardianData['lastName']} ({$guardianData['email']}) linked to student {$studentNumber}",
                null,
                [
                    'email' => $guardianData['email'],
                    'first_name' => $guardianData['firstName'],
                    'last_name' => $guardianData['lastName'],
                    'student_number' => $studentNumber,
                ]
            );
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

            // Audit log for linking
            if ($guardian) {
                $this->logAudit(
                    'linked',
                    'guardian_students',
                    $studentNumber,
                    "Linked existing guardian {$guardian->first_name} {$guardian->last_name} to student {$studentNumber}",
                    null,
                    [
                        'guardian_id' => $guardianId,
                        'guardian_email' => $guardian->email,
                        'student_number' => $studentNumber,
                    ]
                );
            }
        }

        return [
            'success' => true,
            'guardian_id' => $guardianId,
            'is_new' => $isNew
        ];
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


/**
     * AJAX endpoint for paginated, filtered student list (admin)
     * Add this method to the User_Management controller.
     * Also remove the heavy student query from list_students() — replace with just filter data.
     */

    // ── Updated list_students (page load only, no student query) ─────────────
    public function list_students(Request $request)
    {
        $strands = Strand::all();
        $levels  = Level::all();

        // Semesters for filter dropdown
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

        $activeSemester = $semesters->where('status', 'active')->first();

        $data = [
            'scripts' => ['user_management/list_student.js'],
            'strands'        => $strands,
            'levels'         => $levels,
            'semesters'      => $semesters,
            'activeSemester' => $activeSemester,
        ];

        return view('admin.user_management.list_student', $data);
    }

    // ── New AJAX endpoint ─────────────────────────────────────────────────────
    public function getStudentsAjax(Request $request)
    {
        try {
            $search      = trim($request->get('search', ''));
            $semesterId  = $request->get('semester_id');
            $studentType = $request->get('student_type');
            $strandCode  = $request->get('strand_code');
            $levelName   = $request->get('level_name');
            $sectionName = $request->get('section_name');
            $perPage     = (int) $request->get('per_page', 25);

            $query = DB::table('students')
                ->join('sections', 'students.section_id', '=', 'sections.id')
                ->join('levels', 'sections.level_id', '=', 'levels.id')
                ->join('strands', 'sections.strand_id', '=', 'strands.id')
                ->leftJoin('student_semester_enrollment as sse', function ($join) use ($semesterId) {
                    $join->on('students.student_number', '=', 'sse.student_number');
                    if ($semesterId) {
                        $join->where('sse.semester_id', '=', $semesterId);
                    }
                })
                ->leftJoin('semesters as sem', 'sse.semester_id', '=', 'sem.id')
                ->leftJoin('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
                ->leftJoin('sections as enrolled_section', 'sse.section_id', '=', 'enrolled_section.id')
                ->leftJoin('guardian_students as gs', 'students.student_number', '=', 'gs.student_number')
                ->leftJoin('guardians as g', 'gs.guardian_id', '=', 'g.id')
                ->select(
                    'students.id',
                    'students.student_number',
                    'students.first_name',
                    'students.middle_name',
                    'students.last_name',
                    'students.email',
                    'students.student_type',
                    'sections.name as current_section',
                    'levels.name as level',
                    'strands.code as strand',
                    'sse.semester_id',
                    'sse.enrollment_status',
                    'sse.enrollment_date',
                    'enrolled_section.name as enrolled_section_name',
                    DB::raw("CONCAT(sy.code, ' - ', sem.name) as semester_display"),
                    'g.email as guardian_email',
                    'g.email_verified_at',
                    DB::raw('CASE
                        WHEN g.email_verified_at IS NOT NULL THEN "verified"
                        WHEN g.email IS NOT NULL THEN "pending"
                        ELSE "none"
                    END as verification_status')
                );

            // Search: student number or name
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('students.student_number', 'like', "%{$search}%")
                      ->orWhere('students.first_name',   'like', "%{$search}%")
                      ->orWhere('students.last_name',    'like', "%{$search}%");
                });
            }

            // Semester filter — only show students with enrollment in this semester
            if ($semesterId) {
                $query->where('sse.semester_id', $semesterId);
            }

            // Student type filter
            if ($studentType) {
                $query->where('students.student_type', $studentType);
            }

            // Strand filter (on current/base section strand)
            if ($strandCode) {
                $query->where('strands.code', $strandCode);
            }

            // Level filter (on current/base section level)
            if ($levelName) {
                $query->where('levels.name', $levelName);
            }

            // Section filter (enrolled section name)
            if ($sectionName) {
                $query->where('enrolled_section.name', $sectionName);
            }

            $query->orderBy('students.last_name')
                  ->orderBy('students.first_name');

            $students = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data'    => $students->items(),
                'meta'    => [
                    'current_page' => $students->currentPage(),
                    'last_page'    => $students->lastPage(),
                    'per_page'     => $students->perPage(),
                    'total'        => $students->total(),
                    'from'         => $students->firstItem() ?? 0,
                    'to'           => $students->lastItem()   ?? 0,
                ],
            ]);
        } catch (Exception $e) {
            \Log::error('getStudentsAjax failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to load students.'], 500);
        }
    }

// public function list_students(Request $request)
//     {
//         $strands = Strand::all();
//         $levels = Level::all();
//         $sections = Section::all();
        
//         // Get semesters for filter
//         $semesters = DB::table('semesters as s')
//             ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
//             ->select(
//                 's.id',
//                 's.name',
//                 's.code',
//                 'sy.code as school_year_code',
//                 DB::raw("CONCAT(sy.code, ' - ', s.name) as display_name"),
//                 's.status'
//             )
//             ->orderBy('sy.year_start', 'desc')
//             ->orderBy('s.code', 'asc')
//             ->get();

//         // Get active semester for default selection
//         $activeSemester = $semesters->where('status', 'active')->first();

//         // Get ALL enrollment records with guardian verification status
//         $students = DB::table('students')
//             ->join('sections', 'students.section_id', '=', 'sections.id')
//             ->join('levels', 'sections.level_id', '=', 'levels.id')
//             ->join('strands', 'sections.strand_id', '=', 'strands.id')
//             ->leftJoin('student_semester_enrollment as sse', 'students.student_number', '=', 'sse.student_number')
//             ->leftJoin('semesters as sem', 'sse.semester_id', '=', 'sem.id')
//             ->leftJoin('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
//             ->leftJoin('sections as enrolled_section', 'sse.section_id', '=', 'enrolled_section.id')
//             // Join with guardian to get verification status
//             ->leftJoin('guardian_students as gs', 'students.student_number', '=', 'gs.student_number')
//             ->leftJoin('guardians as g', 'gs.guardian_id', '=', 'g.id')
//             ->select(
//                 'students.id',
//                 'students.student_number',
//                 'students.first_name',
//                 'students.middle_name',
//                 'students.last_name',
//                 'students.email',
//                 'students.student_type',
//                 'sections.name as current_section',
//                 'levels.name as level',
//                 'strands.code as strand',
//                 'sse.semester_id',
//                 'enrolled_section.name as enrolled_section_name',
//                 'sse.enrollment_status',
//                 'sse.enrollment_date',
//                 DB::raw("CONCAT(sy.code, ' - ', sem.name) as semester_display"),
//                 // Add guardian email and verification status
//                 'g.email as guardian_email',
//                 'g.email_verified_at',
//                 DB::raw('CASE 
//                     WHEN g.email_verified_at IS NOT NULL THEN "verified"
//                     WHEN g.email IS NOT NULL THEN "pending"
//                     ELSE "none"
//                 END as verification_status')
//             )
//             ->orderBy('students.last_name')
//             ->orderBy('students.first_name')
//             ->orderBy('sem.id', 'desc')
//             ->get();

//         $data = [
//             'scripts' => [
//                 'user_management/list_student.js',
//             ],
//             'strands' => $strands,
//             'levels' => $levels,
//             'sections' => $sections,
//             'semesters' => $semesters,
//             'students' => $students,
//             'activeSemester' => $activeSemester
//         ];

//         return view('admin.user_management.list_student', $data);
//     }

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

            $defaultPassword = $this->generateTeacherPassword();
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

            // Audit log - format values without sensitive data
            $auditValues = [
                'teacher_id' => $teacher->id,
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'status' => 1,
            ];

            $this->logAudit(
                'created',
                'teachers',
                (string)$teacher->id,
                "Created teacher: {$request->first_name} {$request->last_name} ({$request->email})",
                null,
                $auditValues
            );

            \Log::info('Teacher created successfully', [
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->first_name . " " . $teacher->last_name,
                'email' => $teacher->email,
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

    private function generateTeacherPassword()
    {
        return 'Tchr' . strtoupper(Str::random(4)) . rand(100, 999);
    }


    private function generateTeacherPasscode()
    {
        return strtoupper(Str::random(6));
    }

public function list_teacher(Request $request)
{
    $activeSchoolYear = DB::table('school_years')
        ->where('status', 'active')
        ->first();

    $teachers = DB::table('teachers')
        ->select('teachers.*')
        ->get()
        ->map(function ($teacher) use ($activeSchoolYear) {
            $classes = DB::table('teacher_class_matrix')
                ->join('classes', 'teacher_class_matrix.class_id', '=', 'classes.id')
                ->where('teacher_class_matrix.teacher_id', $teacher->id)
                ->where('teacher_class_matrix.school_year_id', $activeSchoolYear->id ?? 0)
                ->select('classes.id', 'classes.class_code', 'classes.class_name')
                ->get();

            $teacher->classes = $classes;

            return $teacher;
        });

    // teachers.status is the global on/off (1 = active, 0 = inactive)
    $activeCount   = $teachers->where('status', 1)->count();
    $inactiveCount = $teachers->where('status', 0)->count();

    $classes = DB::table('classes')
        ->select('id', 'class_code', 'class_name')
        ->orderBy('class_code')
        ->get();

    $data = [
        'scripts'          => ['user_management/list_teacher.js'],
        'teachers'         => $teachers,
        'classes'          => $classes,
        'activeSchoolYear' => $activeSchoolYear,
        'activeCount'      => $activeCount,
        'inactiveCount'    => $inactiveCount,
    ];

    return view('admin.user_management.list_teacher', $data);
}


public function teacherHistory($id)
{
    $teacher = DB::table('teachers')->where('id', $id)->first();

    if (!$teacher) {
        abort(404, 'Teacher not found');
    }

    // Get all school years that are relevant to this teacher:
    // - has a trail record in teacher_school_year_status, OR
    // - has classes assigned in teacher_class_matrix
    // Use UNION so a tsys record with no classes is still included.
    $schoolYearIds = DB::table('teacher_school_year_status')
        ->where('teacher_id', $id)
        ->pluck('school_year_id')
        ->merge(
            DB::table('teacher_class_matrix')
                ->where('teacher_id', $id)
                ->pluck('school_year_id')
        )
        ->unique()
        ->values();

    $schoolYears = DB::table('school_years as sy')
        ->leftJoin('teacher_school_year_status as tsys', function ($join) use ($id) {
            $join->on('sy.id', '=', 'tsys.school_year_id')
                 ->where('tsys.teacher_id', '=', $id);
        })
        ->whereIn('sy.id', $schoolYearIds)
        ->select(
            'sy.id as school_year_id',
            'sy.code as school_year_code',
            'sy.status as school_year_status',
            'tsys.id as trail_id',
            'tsys.status as trail_status',
            'tsys.reactivated_by',
            'tsys.reactivated_at',
            'tsys.deactivated_by',
            'tsys.deactivated_at'
        )
        ->orderBy('sy.year_start', 'desc')
        ->get();

    $schoolYearData = [];

    foreach ($schoolYears as $sy) {
        // ── Teaching assignments ────────────────────────────────────────────
        $classes = DB::table('teacher_class_matrix as tcm')
            ->join('classes as c', 'tcm.class_id', '=', 'c.id')
            ->leftJoin('semesters as sem', 'tcm.semester_id', '=', 'sem.id')
            ->where('tcm.teacher_id', $id)
            ->where('tcm.school_year_id', $sy->school_year_id)
            ->select(
                'c.id as class_id',
                'c.class_code',
                'c.class_name',
                'c.class_category',
                'sem.id as semester_id',
                'sem.name as semester_name',
                'sem.code as semester_code'
            )
            ->distinct()
            ->get();

        // Attach sections to each class
        $classesWithSections = $classes->map(function ($class) {
            $sections = DB::table('section_class_matrix as scm')
                ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
                ->join('levels as lvl', 'sec.level_id', '=', 'lvl.id')
                ->join('strands as str', 'sec.strand_id', '=', 'str.id')
                ->where('scm.class_id', $class->class_id)
                ->where('scm.semester_id', $class->semester_id)
                ->select(
                    'sec.code as section_code', 'sec.name as section_name',
                    'str.code as strand_code', 'lvl.name as level_name'
                )
                ->get();

            $class->sections      = $sections;
            $class->section_count = $sections->count();

            return $class;
        });

        // ── Adviser assignments ─────────────────────────────────────────────
        $adviserAssignments = DB::table('section_adviser_matrix as sam')
            ->join('sections as sec', 'sam.section_id', '=', 'sec.id')
            ->join('semesters as sem', 'sam.semester_id', '=', 'sem.id')
            ->join('levels as lvl', 'sec.level_id', '=', 'lvl.id')
            ->join('strands as str', 'sec.strand_id', '=', 'str.id')
            ->where('sam.teacher_id', $id)
            ->where('sem.school_year_id', $sy->school_year_id)
            ->select(
                'sec.id as section_id', 'sec.code as section_code',
                'sec.name as section_name', 'str.code as strand_code',
                'lvl.name as level_name', 'sem.id as semester_id',
                'sem.name as semester_name', 'sem.code as semester_code',
                'sam.assigned_date'
            )
            ->orderBy('sem.code')
            ->orderBy('sec.code')
            ->get();

        // ── Resolve admin names for trail ───────────────────────────────────
        $reactivatedByName = null;
        $deactivatedByName = null;

        if ($sy->reactivated_by) {
            $admin = DB::table('admins')->where('id', $sy->reactivated_by)->first();
            $reactivatedByName = $admin ? $admin->admin_name : 'Admin #' . $sy->reactivated_by;
        }
        if ($sy->deactivated_by) {
            $admin = DB::table('admins')->where('id', $sy->deactivated_by)->first();
            $deactivatedByName = $admin ? $admin->admin_name : 'Admin #' . $sy->deactivated_by;
        }

        $schoolYearData[] = [
            'school_year_id'     => $sy->school_year_id,
            'school_year_code'   => $sy->school_year_code,
            'school_year_status' => $sy->school_year_status,

            // Timeline trail
            'has_trail'           => !is_null($sy->trail_id),
            'trail_status'        => $sy->trail_status,
            'reactivated_at'      => $sy->reactivated_at,
            'reactivated_by_name' => $reactivatedByName,
            'deactivated_at'      => $sy->deactivated_at,
            'deactivated_by_name' => $deactivatedByName,

            'classes'                => $classesWithSections->groupBy('class_code'),
            'adviser_assignments'    => $adviserAssignments->groupBy('semester_id'),
            'total_classes'          => $classesWithSections->count(),
            'total_sections'         => $classesWithSections->sum('section_count'),
            'total_adviser_sections' => $adviserAssignments->count(),
        ];
    }

    $data = [
        'scripts'          => ['user_management/teacher_history.js'],
        'teacher'          => $teacher,
        'schoolYearData'   => $schoolYearData,
        'activeSchoolYear' => DB::table('school_years')->where('status', 'active')->first(),
    ];

    return view('admin.user_management.teacher_history', $data);
}

// ============================================================
public function toggleTeacherStatus(Request $request)
{
    $validated = $request->validate([
        'teacher_id'     => 'required|exists:teachers,id',
        'school_year_id' => 'required|exists:school_years,id',
        'status'         => 'required|in:active,inactive',
    ]);

    try {
        DB::beginTransaction();

        $teacher    = DB::table('teachers')->where('id', $validated['teacher_id'])->first();
        $schoolYear = DB::table('school_years')->where('id', $validated['school_year_id'])->first();

        // Use the admin guard — session('admin_id') is always null for guard-based auth
        $adminId = Auth::guard('admin')->id();

        $newStatus    = $validated['status'];   // 'active' | 'inactive' (request value)
        $newIntStatus = $newStatus === 'active' ? 1 : 0;

        // ── 1. Update the teacher's global status ──────────────────────────
        DB::table('teachers')
            ->where('id', $validated['teacher_id'])
            ->update([
                'status'     => $newIntStatus,
                'updated_at' => now(),
            ]);

        // ── 2. Upsert the timeline trail ───────────────────────────────────

        // status enum matches request value directly: 'active' | 'inactive'

        $existing = DB::table('teacher_school_year_status')
            ->where('teacher_id', $validated['teacher_id'])
            ->where('school_year_id', $validated['school_year_id'])
            ->first();

        if ($newStatus === 'inactive') {
            $trailData = [
                'status'         => $newStatus,
                'deactivated_by' => $adminId,
                'deactivated_at' => now(),
                'updated_at'     => now(),
            ];
        } else {
            $trailData = [
                'status'         => $newStatus,
                'reactivated_by' => $adminId,
                'reactivated_at' => now(),
                'updated_at'     => now(),
            ];
        }

        if ($existing) {
            DB::table('teacher_school_year_status')
                ->where('id', $existing->id)
                ->update($trailData);
        } else {
            DB::table('teacher_school_year_status')->insert(array_merge($trailData, [
                'teacher_id'     => $validated['teacher_id'],
                'school_year_id' => $validated['school_year_id'],
                'created_at'     => now(),
            ]));
        }

        // ── 3. Audit log ───────────────────────────────────────────────────
        $this->logAudit(
            'updated',
            'teachers',
            (string) $validated['teacher_id'],
            "Changed teacher {$teacher->first_name} {$teacher->last_name} status to {$newStatus} (school year: {$schoolYear->code})",
            ['status' => $newStatus === 'active' ? 0 : 1],
            ['status' => $newIntStatus, 'school_year' => $schoolYear->code]
        );

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Teacher status updated to {$newStatus} successfully!",
            'status'  => $newStatus,
        ]);

    } catch (Exception $e) {
        DB::rollBack();
        \Log::error('Failed to update teacher status', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to update teacher status: ' . $e->getMessage(),
        ], 500);
    }
}


}