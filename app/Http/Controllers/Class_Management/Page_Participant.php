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
        return view('modules.class.page_participant', [
            'userType' => 'teacher',
            'class' => $class,
        ]);
    }

    public function getParticipants($classId)
    {
        try {
            // Get students enrolled directly via student_class_matrix
            $directStudents = DB::table('student_class_matrix as scm')
                ->join('classes as c', 'scm.class_code', '=', 'c.class_code')
                ->join('students as s', 'scm.student_number', '=', 's.student_number')
                ->leftJoin('sections as sec', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as st', 'sec.strand_id', '=', 'st.id')
                ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
                ->where('c.id', $classId)
                ->select(
                    's.id',
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.email',
                    's.gender',
                    's.student_type',
                    's.profile_image',
                    'sec.name as section_name',
                    'sec.code as section_code',
                    'st.name as strand_name',
                    'l.name as level_name',
                    DB::raw("'direct' as enrollment_type")
                )
                ->get();

            // Get students enrolled via section_class_matrix
            $sectionStudents = DB::table('section_class_matrix as scm')
                ->join('classes as c', 'scm.class_id', '=', 'c.id')
                ->join('sections as sec', 'scm.section_id', '=', 'sec.id')
                ->join('students as s', 's.section_id', '=', 'sec.id')
                ->leftJoin('strands as st', 'sec.strand_id', '=', 'st.id')
                ->leftJoin('levels as l', 'sec.level_id', '=', 'l.id')
                ->where('c.id', $classId)
                ->select(
                    's.id',
                    's.student_number',
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.email',
                    's.gender',
                    's.student_type',
                    's.profile_image',
                    'sec.name as section_name',
                    'sec.code as section_code',
                    'st.name as strand_name',
                    'l.name as level_name',
                    DB::raw("'section' as enrollment_type")
                )
                ->get();

            // Merge and remove duplicates (prioritize direct enrollment)
            $allStudents = $directStudents->merge($sectionStudents);
            $uniqueStudents = $allStudents->unique('student_number')->values();

            // Format the data
            $participants = $uniqueStudents->map(function($student, $index) {
                return [
                    'row_number' => $index + 1,
                    'id' => $student->id,
                    'student_number' => $student->student_number,
                    'full_name' => trim($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name),
                    'first_name' => $student->first_name,
                    'middle_name' => $student->middle_name,
                    'last_name' => $student->last_name,
                    'email' => $student->email,
                    'gender' => $student->gender,
                    'student_type' => $student->student_type,
                    'section_code' => $student->section_code ?? 'N/A',
                    'section_name' => $student->section_name ?? 'No Section',
                    'strand_name' => $student->strand_name ?? '',
                    'level_name' => $student->level_name ?? '',
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
                'message' => 'Error fetching participants: ' . $e->getMessage()
            ], 500);
        }
    }
}