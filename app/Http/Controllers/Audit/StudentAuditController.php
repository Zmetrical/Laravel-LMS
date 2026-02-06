<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StudentAuditController extends Controller
{
    /**
     * Render the student activity logs page for teacher's advisees.
     */
    public function index()
    {
        $teacher = Auth::guard('teacher')->user();
        
        if (!$teacher) {
            abort(403, 'Unauthorized access');
        }

        // Get active semester
        $activeSemester = DB::table('semesters as s')
            ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
            ->where('s.status', 'active')
            ->select(
                's.id as semester_id',
                's.name as semester_name',
                's.code as semester_code',
                'sy.id as school_year_id',
                'sy.code as school_year_code',
                DB::raw("CONCAT(sy.code, ' - ', s.name) as display_name")
            )
            ->first();
        
        // If no active semester, get the most recent one
        if (!$activeSemester) {
            $activeSemester = DB::table('semesters as s')
                ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                ->orderBy('sy.year_start', 'desc')
                ->orderBy('s.start_date', 'desc')
                ->select(
                    's.id as semester_id',
                    's.name as semester_name',
                    's.code as semester_code',
                    'sy.id as school_year_id',
                    'sy.code as school_year_code',
                    DB::raw("CONCAT(sy.code, ' - ', s.name) as display_name")
                )
                ->first();
        }

        // Get sections where teacher is assigned
        $sections = DB::table('teacher_class_matrix as tcm')
            ->join('section_class_matrix as scm', function($join) use ($activeSemester) {
                $join->on('tcm.class_id', '=', 'scm.class_id')
                    ->where('scm.semester_id', '=', $activeSemester->semester_id);
            })
            ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
            ->where('tcm.teacher_id', $teacher->id)
            ->where('tcm.semester_id', $activeSemester->semester_id)
            ->select('sec.id')
            ->distinct()
            ->pluck('id');

        // Get all students in these sections
        $students = DB::table('students as s')
            ->join('sections as sec', 's.section_id', '=', 'sec.id')
            ->whereIn('s.section_id', $sections)
            ->where('s.student_type', 'regular')
            ->select(
                's.student_number',
                's.first_name',
                's.middle_name',
                's.last_name',
                'sec.code as section_code',
                'sec.name as section_name'
            )
            ->orderBy('sec.code')
            ->orderBy('s.last_name')
            ->orderBy('s.first_name')
            ->get();

        return view('teacher.audit.student_my_log', [
            'students' => $students,
            'activeSemester' => $activeSemester
        ]);
    }

    /**
     * Server-side DataTable endpoint for student logs.
     * Only returns logs for students in teacher's sections.
     */
    public function getStudentLogs(Request $request)
    {
        $teacher = Auth::guard('teacher')->user();

        // Get active semester
        $activeSemester = DB::table('semesters')
            ->where('status', 'active')
            ->first();

        if (!$activeSemester) {
            $activeSemester = DB::table('semesters as s')
                ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                ->orderBy('sy.year_start', 'desc')
                ->orderBy('s.start_date', 'desc')
                ->first();
        }

        // Get sections where teacher is assigned
        $sections = DB::table('teacher_class_matrix as tcm')
            ->join('section_class_matrix as scm', function($join) use ($activeSemester) {
                $join->on('tcm.class_id', '=', 'scm.class_id')
                    ->where('scm.semester_id', '=', $activeSemester->id);
            })
            ->where('tcm.teacher_id', $teacher->id)
            ->where('tcm.semester_id', $activeSemester->id)
            ->select('scm.section_id')
            ->distinct()
            ->pluck('section_id');

        // Get student numbers for these sections
        $studentNumbers = DB::table('students')
            ->whereIn('section_id', $sections)
            ->where('student_type', 'regular')
            ->pluck('student_number');

        // Base query: only student logs for teacher's advisees
        $query = DB::table('audit_logs as al')
            ->join('students as s', 'al.user_identifier', '=', 's.student_number')
            ->join('sections as sec', 's.section_id', '=', 'sec.id')
            ->where('al.user_type', 'student')
            ->whereIn('al.user_identifier', $studentNumbers)
            ->select(
                'al.id',
                'al.user_identifier',
                'al.action',
                'al.module',
                'al.record_id',
                'al.description',
                'al.ip_address',
                'al.created_at',
                DB::raw("CONCAT(s.last_name, ', ', s.first_name, ' ', s.middle_name) as student_name"),
                's.student_number',
                'sec.code as section_code'
            );

        // Apply filters
        if ($request->filled('student_number')) {
            $query->where('al.user_identifier', $request->student_number);
        }

        if ($request->filled('action')) {
            $query->where('al.action', $request->action);
        }

        if ($request->filled('module')) {
            $query->where('al.module', $request->module);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('al.created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('al.created_at', '<=', $request->date_to);
        }

        $totalFiltered = $query->count();

        // Ordering
        $columns = [
            'al.created_at',
            'student_name',
            'al.action',
            'al.module',
            'al.record_id',
            'al.description',
            'al.ip_address',
        ];

        $orderIndex  = $request->input('order.0.column', 0);
        $orderDir    = in_array($request->input('order.0.dir'), ['asc', 'desc'])
                        ? $request->input('order.0.dir')
                        : 'desc';
        $orderColumn = $columns[$orderIndex] ?? 'al.created_at';

        $query->orderBy($orderColumn, $orderDir);

        // Paging
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);

        if ($length !== -1) {
            $query->skip($start)->take($length);
        }

        $logs = $query->get();

        // Total records
        $totalRecords = DB::table('audit_logs')
            ->where('user_type', 'student')
            ->whereIn('user_identifier', $studentNumbers)
            ->count();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $totalFiltered,
            'data'            => $logs,
        ]);
    }

    /**
     * Return a single student audit log detail.
     * Enforces that the student belongs to teacher's sections.
     */
    public function getStudentLogDetail(int $id)
    {
        $teacher = Auth::guard('teacher')->user();

        // Get active semester
        $activeSemester = DB::table('semesters')
            ->where('status', 'active')
            ->first();

        if (!$activeSemester) {
            $activeSemester = DB::table('semesters as s')
                ->join('school_years as sy', 's.school_year_id', '=', 'sy.id')
                ->orderBy('sy.year_start', 'desc')
                ->orderBy('s.start_date', 'desc')
                ->first();
        }

        // Get sections where teacher is assigned
        $sections = DB::table('teacher_class_matrix as tcm')
            ->join('section_class_matrix as scm', function($join) use ($activeSemester) {
                $join->on('tcm.class_id', '=', 'scm.class_id')
                    ->where('scm.semester_id', '=', $activeSemester->id);
            })
            ->where('tcm.teacher_id', $teacher->id)
            ->where('tcm.semester_id', $activeSemester->id)
            ->select('scm.section_id')
            ->distinct()
            ->pluck('section_id');

        // Get student numbers for validation
        $studentNumbers = DB::table('students')
            ->whereIn('section_id', $sections)
            ->where('student_type', 'regular')
            ->pluck('student_number');

        $log = DB::table('audit_logs')
            ->where('id', $id)
            ->where('user_type', 'student')
            ->whereIn('user_identifier', $studentNumbers)
            ->first();

        if (!$log) {
            return response()->json(['error' => 'Log not found'], 404);
        }

        return response()->json($log);
    }
}