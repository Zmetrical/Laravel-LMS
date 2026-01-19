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
                'st.name as strand_name',
                'gs.relationship'
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

        // Get student info
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
                's.student_type',
                'sec.name as section_name',
                'l.name as level_name',
                'st.name as strand_name'
            )
            ->first();

        if (!$student) {
            return redirect()->route('guardian.home')->with('error', 'Student not found.');
        }

        $student->full_name = trim($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name);

        // Get all semesters with grades for this student
        $semesters = DB::table('grades_final as gf')
            ->join('semesters as sem', 'gf.semester_id', '=', 'sem.id')
            ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
            ->where('gf.student_number', $student_number)
            ->select('sem.id', 'sem.name', 'sy.code as school_year_code')
            ->distinct()
            ->orderBy('sy.year_start', 'desc')
            ->orderBy('sem.id', 'desc')
            ->get()
            ->map(function($sem) {
                $sem->display_name = 'SY ' . $sem->school_year_code . ' - ' . $sem->name;
                return $sem;
            });

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

        // Get grades for the semester
        $grades = DB::table('grades_final as gf')
            ->join('classes as c', 'gf.class_code', '=', 'c.class_code')
            ->where('gf.student_number', $student_number)
            ->where('gf.semester_id', $semesterId)
            ->select(
                'c.class_name',
                'gf.q1_grade',
                'gf.q2_grade',
                'gf.final_grade',
                'gf.remarks'
            )
            ->orderBy('c.class_name')
            ->get();

        $stats = [
            'total_subjects' => $grades->count(),
            'passed' => $grades->where('remarks', 'PASSED')->count(),
            'failed' => $grades->where('remarks', 'FAILED')->count(),
            'average' => $grades->whereNotNull('final_grade')->avg('final_grade')
        ];

        return response()->json([
            'grades' => $grades,
            'stats' => $stats
        ]);
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