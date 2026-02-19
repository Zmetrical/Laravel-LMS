<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Illuminate\Support\Facades\DB;

class Page_Participant extends MainController
{
    public function teacherIndex($classId)
    {
        // Fetch class info
        $class = DB::table('classes')->where('id', $classId)->first();

        // If not found, you can handle it gracefully
        if (!$class) {
            abort(404, 'Class not found');
        }

        // Return the view
        return view('modules.participant.page_participant', [
            'userType' => 'teacher',
            'class' => $class,
            'scripts' => ['class_participant/page_participants.js']
        ]);
    }

public function getParticipants($classId)
{
    try {
        // 1. Get the class and the current active semester
        $class = DB::table('classes')->where('id', $classId)->first();
        if (!$class) return response()->json(['success' => false, 'message' => 'Class not found'], 404);

        $activeSemester = DB::table('semesters')->where('status', 'active')->first();
        $semesterId = $activeSemester ? $activeSemester->id : null;

        // 2. Get students enrolled directly (Irregulars)
        // Added semester_id check and enrollment_status check
        $directStudents = DB::table('student_class_matrix as scm')
            ->join('students as s', 'scm.student_number', '=', 's.student_number')
            ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
            ->leftJoin('strands as st', 'sec.strand_id', '=', 'st.id')
            ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
            ->where('scm.class_code', $class->class_code)
            ->where('scm.semester_id', $semesterId)
            ->where('scm.enrollment_status', 'enrolled')
            ->select(
                's.id', 's.student_number', 's.first_name', 's.middle_name', 's.last_name',
                's.email', 's.gender', 's.student_type', 's.profile_image',
                'sec.name as section_name', 'sec.code as section_code',
                'st.name as strand_name', 'l.name as level_name',
                DB::raw("'direct' as enrollment_type")
            )
            ->get();

        // 3. Get students enrolled via Section (Regulars)
        // Added semester_id check
        $sectionStudents = DB::table('section_class_matrix as scm')
            ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
            ->join('students as s', 's.section_id', '=', 'sec.id')
            ->leftJoin('strands as st', 'sec.strand_id', '=', 'st.id')
            ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
            ->where('scm.class_id', $classId)
            ->where('scm.semester_id', $semesterId)
            ->select(
                's.id', 's.student_number', 's.first_name', 's.middle_name', 's.last_name',
                's.email', 's.gender', 's.student_type', 's.profile_image',
                'sec.name as section_name', 'sec.code as section_code',
                'st.name as strand_name', 'l.name as level_name',
                DB::raw("'section' as enrollment_type")
            )
            ->get();

        // 4. Merge and Remove duplicates
        $allStudents = $directStudents->merge($sectionStudents);
        $uniqueStudents = $allStudents->unique('student_number')->values();

        // 5. Format the data
        $participants = $uniqueStudents->map(function($student, $index) {
            return [
                'row_number' => $index + 1,
                'id' => $student->id,
                'student_number' => $student->student_number,
                'full_name' => trim($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name),
                'email' => $student->email,
                'gender' => $student->gender,
                'student_type' => $student->student_type,
                'section_name' => $student->section_name ?? 'No Section',
                'enrollment_type' => $student->enrollment_type,
                'profile_image' => $student->profile_image ?? 'default-avatar.png'
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $participants,
            'total' => $participants->count()
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}
}