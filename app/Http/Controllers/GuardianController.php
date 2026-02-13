<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GuardianController extends Controller
{
    // Access via unique token link
    public function access($token)
    {
        $guardian = DB::table('guardians')
            ->where('access_token', $token)
            ->where('is_active', 1)
            ->first();

        if (!$guardian) {
            return redirect()->route('home')->with('error', 'Invalid or expired guardian link.');
        }

        // Store guardian ID in session
        session(['guardian_id' => $guardian->id]);
        session(['guardian_name' => $guardian->first_name . ' ' . $guardian->last_name]);

        return redirect()->route('guardian.home');
    }

    public function index()
    {
        $guardianId = session('guardian_id');
        
        $guardian = DB::table('guardians')
            ->where('id', $guardianId)
            ->first();

        // Get all students linked to this guardian
        $students = DB::table('guardian_students as gs')
            ->join('students as s', 'gs.student_number', '=', 's.student_number')
            ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
            ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
            ->leftJoin('strands as st', 'sec.strand_id', '=', 'st.id')
            ->where('gs.guardian_id', $guardianId)
            ->select(
                's.student_number',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.profile_image',
                's.student_type',
                'sec.name as section_name',
                'l.name as level_name',
                'st.name as strand_name'
            )
            ->get()
            ->map(function($student) {
                $student->full_name = trim($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name);
                return $student;
            });

        // Get quick stats
        $totalStudents = $students->count();
        $totalSubjects = 0;
        $avgGrade = 0;

        if ($totalStudents > 0) {
            foreach ($students as $student) {
                $grades = DB::table('grades_final as gf')
                    ->join('classes as c', 'gf.class_code', '=', 'c.class_code')
                    ->where('gf.student_number', $student->student_number)
                    ->whereNotNull('gf.final_grade')
                    ->select('gf.final_grade')
                    ->get();
                
                $totalSubjects += $grades->count();
                $avgGrade += $grades->sum('final_grade');
            }
            
            if ($totalSubjects > 0) {
                $avgGrade = round($avgGrade / $totalSubjects, 2);
            }
        }

        // Get recent grade updates
        $recentUpdates = DB::table('grades_final as gf')
            ->join('students as s', 'gf.student_number', '=', 's.student_number')
            ->join('guardian_students as gs', 's.student_number', '=', 'gs.student_number')
            ->join('classes as c', 'gf.class_code', '=', 'c.class_code')
            ->join('semesters as sem', 'gf.semester_id', '=', 'sem.id')
            ->where('gs.guardian_id', $guardianId)
            ->select(
                'gf.updated_at',
                's.first_name',
                's.last_name',
                'c.class_name',
                'sem.name as semester_name',
                'gf.final_grade',
                'gf.remarks'
            )
            ->orderBy('gf.updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($update) {
                $update->student_name = $update->first_name . ' ' . $update->last_name;
                return $update;
            });

        $data = [
            'scripts' => ['guardian/dashboard.js'],
            'guardian' => $guardian,
            'students' => $students,
            'totalStudents' => $totalStudents,
            'totalSubjects' => $totalSubjects,
            'avgGrade' => $avgGrade,
            'recentUpdates' => $recentUpdates
        ];

        return view('guardian.dashboard', $data);
    }

    public function view_student_grades($student_number)
    {
        $guardianId = session('guardian_id');

        // Verify guardian has access to this student
        $guardianStudent = DB::table('guardian_students')
            ->where('guardian_id', $guardianId)
            ->where('student_number', $student_number)
            ->first();

        if (!$guardianStudent) {
            return redirect()->route('guardian.home')->with('error', 'Access denied to this student.');
        }

        // Get student info with all necessary fields
        $student = DB::table('students as s')
            ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
            ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
            ->leftJoin('strands as st', 'sec.strand_id', '=', 'st.id')
            ->where('s.student_number', $student_number)
            ->select(
                's.student_number',
                's.first_name',
                's.middle_name',
                's.last_name',
                's.profile_image',
                's.gender',
                's.student_type',
                's.section_id',
                'sec.code as section_code',
                'sec.name as section_name',
                'st.code as strand_code',
                'st.name as strand_name',
                'l.name as level_name'
            )
            ->first();

        if (!$student) {
            return redirect()->route('guardian.home')->with('error', 'Student not found.');
        }

        $student->full_name = trim($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name);

        // Get all semesters that have enrollment for this student
        $semesters = DB::table('semesters as sem')
            ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
            ->whereExists(function($query) use ($student_number) {
                $query->select(DB::raw(1))
                    ->from('student_semester_enrollment as sse')
                    ->whereColumn('sse.semester_id', 'sem.id')
                    ->where('sse.student_number', $student_number);
            })
            ->select(
                'sem.id',
                'sem.name',
                'sem.code',
                'sem.status',
                'sy.id as school_year_id',
                'sy.code as school_year_code',
                'sy.year_start',
                'sy.year_end',
                DB::raw("CONCAT(sy.code, ' - ', sem.name) as display_name")
            )
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('sem.code', 'asc')
            ->get();

        $data = [
            'scripts' => ['guardian/student_grades.js'],
            'student' => $student,
            'semesters' => $semesters
        ];

        return view('guardian.student_grades', $data);
    }

    public function get_student_grades_data($student_number, Request $request)
    {
        $guardianId = session('guardian_id');
        $semesterId = $request->input('semester_id');

        // Verify access
        $guardianStudent = DB::table('guardian_students')
            ->where('guardian_id', $guardianId)
            ->where('student_number', $student_number)
            ->first();

        if (!$guardianStudent) {
            return response()->json(['error' => 'Access denied'], 403);
        }

        if (!$semesterId) {
            return response()->json(['grades' => []]);
        }

        // Get student info
        $student = DB::table('students')
            ->where('student_number', $student_number)
            ->first();

        if (!$student) {
            return response()->json(['error' => 'Student not found'], 404);
        }

        // Get semester info for school year display
        $semester = DB::table('semesters as sem')
            ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
            ->where('sem.id', $semesterId)
            ->select(
                'sem.id',
                'sem.name',
                'sy.code as school_year_code'
            )
            ->first();

        // Get enrolled subjects with grades using the proper method
        $grades = $this->getEnrolledSubjects($student_number, $semesterId, $student->student_type, $student->section_id);

        // Get adviser name for regular students
        $adviser_name = null;
        if ($student->student_type === 'regular' && $student->section_id) {
            $adviser_name = $this->getAdviserName($student->section_id, $semesterId);
        }

        return response()->json([
            'grades' => $grades,
            'semester' => $semester,
            'adviser_name' => $adviser_name
        ]);
    }

    /**
     * Get all enrolled subjects for a student in a semester
     */
    private function getEnrolledSubjects($studentNumber, $semesterId, $studentType, $sectionId = null)
    {
        if ($studentType === 'regular' && $sectionId) {
            // Get subjects from section_class_matrix for regular students
            return DB::table('section_class_matrix as scm')
                ->join('classes as c', 'scm.class_id', '=', 'c.id')
                ->leftJoin('grades_final as gf', function($join) use ($studentNumber, $semesterId) {
                    $join->on('gf.class_code', '=', 'c.class_code')
                        ->where('gf.student_number', '=', $studentNumber)
                        ->where('gf.semester_id', '=', $semesterId);
                })
                ->where('scm.section_id', $sectionId)
                ->where('scm.semester_id', $semesterId)
                ->select(
                    'c.class_code',
                    'c.class_name',
                    'c.class_category',
                    'gf.q1_grade',
                    'gf.q2_grade',
                    'gf.final_grade',
                    'gf.remarks'
                )
                ->orderBy('c.class_category')
                ->orderBy('c.class_code')
                ->get();
        } else {
            // Get subjects from student_class_matrix for irregular students
            return DB::table('student_class_matrix as stcm')
                ->join('classes as c', 'stcm.class_code', '=', 'c.class_code')
                ->leftJoin('grades_final as gf', function($join) use ($studentNumber, $semesterId) {
                    $join->on('gf.class_code', '=', 'c.class_code')
                        ->where('gf.student_number', '=', $studentNumber)
                        ->where('gf.semester_id', '=', $semesterId);
                })
                ->where('stcm.student_number', $studentNumber)
                ->where('stcm.semester_id', $semesterId)
                ->where('stcm.enrollment_status', 'enrolled')
                ->select(
                    'c.class_code',
                    'c.class_name',
                    'c.class_category',
                    'gf.q1_grade',
                    'gf.q2_grade',
                    'gf.final_grade',
                    'gf.remarks'
                )
                ->orderBy('c.class_category')
                ->orderBy('c.class_code')
                ->get();
        }
    }

    /**
     * Get adviser name for a section from section_adviser_matrix
     */
    private function getAdviserName($sectionId, $semesterId)
    {
        $adviser = DB::table('section_adviser_matrix as sam')
            ->join('teachers as t', 'sam.teacher_id', '=', 't.id')
            ->where('sam.section_id', $sectionId)
            ->where('sam.semester_id', $semesterId)
            ->where('t.status', 1)
            ->select(
                't.first_name',
                't.middle_name',
                't.last_name'
            )
            ->first();

        if ($adviser) {
            $middleInitial = $adviser->middle_name ? strtoupper(substr($adviser->middle_name, 0, 1)) . '.' : '';
            return strtoupper(trim($adviser->first_name . ' ' . $middleInitial . ' ' . $adviser->last_name));
        }

        return null;
    }

    // Helper function to generate access link (use in admin panel)
    public static function generateAccessLink($guardianId)
    {
        $guardian = DB::table('guardians')->where('id', $guardianId)->first();
        
        if (!$guardian) {
            return null;
        }

        return route('guardian.access', ['token' => $guardian->access_token]);
    }

    // Helper function to create guardian and get link
    public static function createGuardian($email, $firstName, $lastName, $studentNumbers = [])
    {
        $token = Str::random(64);

        $guardianId = DB::table('guardians')->insertGetId([
            'email' => $email,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'access_token' => $token,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Link students
        foreach ($studentNumbers as $studentNumber) {
            DB::table('guardian_students')->insert([
                'guardian_id' => $guardianId,
                'student_number' => $studentNumber,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        return route('guardian.access', ['token' => $token]);
    }
}