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
                's.section_id',
                'sec.name as section_name',
                'l.name as level_name',
                'st.name as strand_name'
            )
            ->first();

        if (!$student) {
            return redirect()->route('guardian.home')->with('error', 'Student not found.');
        }

        $student->full_name = trim($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name);

        // Get all semesters that have enrollment or grades for this student
        $semesters = DB::table('semesters as sem')
            ->join('school_years as sy', 'sem.school_year_id', '=', 'sy.id')
            ->whereExists(function($query) use ($student_number) {
                $query->select(DB::raw(1))
                    ->from('student_semester_enrollment as sse')
                    ->whereColumn('sse.semester_id', 'sem.id')
                    ->where('sse.student_number', $student_number);
            })
            ->select('sem.id', 'sem.name', 'sem.status', 'sy.code as school_year_code')
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

        // Get enrolled classes based on student type
        if ($student->student_type === 'regular' && $student->section_id) {
            // Regular student - get classes from section_class_matrix
            $enrolledClasses = DB::table('section_class_matrix as scm')
                ->join('classes as c', 'scm.class_id', '=', 'c.id')
                ->where('scm.section_id', $student->section_id)
                ->where('scm.semester_id', $semesterId)
                ->select('c.class_code', 'c.class_name')
                ->get();
        } else {
            // Irregular student - get classes from student_class_matrix
            $enrolledClasses = DB::table('student_class_matrix as stcm')
                ->join('classes as c', 'stcm.class_code', '=', 'c.class_code')
                ->where('stcm.student_number', $student_number)
                ->where('stcm.semester_id', $semesterId)
                ->where('stcm.enrollment_status', 'enrolled')
                ->select('c.class_code', 'c.class_name')
                ->get();
        }

        $grades = [];

        foreach ($enrolledClasses as $class) {
            // Get final grade if exists
            $finalGrade = DB::table('grades_final')
                ->where('student_number', $student_number)
                ->where('class_code', $class->class_code)
                ->where('semester_id', $semesterId)
                ->first();

            $grades[] = [
                'class_code' => $class->class_code,
                'class_name' => $class->class_name,
                'q1_transmuted_grade' => $finalGrade->q1_grade ?? null,
                'q2_transmuted_grade' => $finalGrade->q2_grade ?? null,
                'final_grade' => $finalGrade->final_grade ?? null,
                'remarks' => $finalGrade->remarks ?? null,
            ];
        }

        return response()->json(['grades' => $grades]);
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