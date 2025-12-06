<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Section_Management extends MainController
{
    // Show section assignment page
    public function assignSections()
    {
        $activeSemester = DB::table('semesters')
            ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
            ->where('semesters.status', 'active')
            ->select('semesters.*', 'school_years.code as school_year_code')
            ->first();

        if (!$activeSemester) {
            return redirect()->back()->with('error', 'No active semester found');
        }

        // Get all semesters for dropdown
        $semesters = DB::table('semesters')
            ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
            ->select('semesters.id', 'semesters.name', 'school_years.code as school_year_code')
            ->orderBy('school_years.year_start', 'desc')
            ->orderBy('semesters.code', 'desc')
            ->get();

        $strands = DB::table('strands')->where('status', 1)->get();
        $levels = DB::table('levels')->get();

        return view('admin.user_management.assign_section', compact(
            'activeSemester',
            'semesters',
            'strands',
            'levels'
        ));
    }

    // Get students by filters
    public function getStudentsByFilter(Request $request)
    {
        $query = DB::table('students')
            ->leftJoin('sections', 'students.section_id', '=', 'sections.id')
            ->leftJoin('strands', 'sections.strand_id', '=', 'strands.id')
            ->leftJoin('levels', 'sections.level_id', '=', 'levels.id')
            ->select(
                'students.id',
                'students.student_number',
                'students.first_name',
                'students.middle_name',
                'students.last_name',
                'students.student_type',
                'students.current_semester_id',
                'sections.id as section_id',
                'sections.name as section_name',
                'sections.code as section_code',
                'strands.code as strand_code',
                'strands.name as strand_name',
                'levels.name as level_name'
            );

        // Filter by semester
        if ($request->has('semester_id') && $request->semester_id) {
            $query->where('students.current_semester_id', $request->semester_id);
        }

        // Filter by strand
        if ($request->has('strand_id') && $request->strand_id) {
            $query->where('sections.strand_id', $request->strand_id);
        }

        // Filter by level
        if ($request->has('level_id') && $request->level_id) {
            $query->where('sections.level_id', $request->level_id);
        }

        // Filter by section
        if ($request->has('section_id') && $request->section_id) {
            $query->where('students.section_id', $request->section_id);
        }

        // Filter by student type
        if ($request->has('student_type') && $request->student_type) {
            $query->where('students.student_type', $request->student_type);
        }

        $students = $query->orderBy('students.last_name')->get();

        return response()->json($students);
    }

    // Get available sections for assignment
    public function getAvailableSections(Request $request)
    {
        $query = DB::table('sections')
            ->join('strands', 'sections.strand_id', '=', 'strands.id')
            ->join('levels', 'sections.level_id', '=', 'levels.id')
            ->where('sections.status', 1)
            ->select(
                'sections.id',
                'sections.code',
                'sections.name',
                'sections.strand_id',
                'sections.level_id',
                'strands.code as strand_code',
                'strands.name as strand_name',
                'levels.name as level_name'
            );

        if ($request->has('strand_id') && $request->strand_id) {
            $query->where('sections.strand_id', $request->strand_id);
        }

        if ($request->has('level_id') && $request->level_id) {
            $query->where('sections.level_id', $request->level_id);
        }

        $sections = $query->get();

        return response()->json($sections);
    }

    // Assign students to new section
    public function assignStudentsToSection(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'required|exists:students,id',
            'new_section_id' => 'required|exists:sections,id',
            'new_semester_id' => 'required|exists:semesters,id'
        ]);

        try {
            DB::beginTransaction();

            $updated = DB::table('students')
                ->whereIn('id', $request->student_ids)
                ->update([
                    'section_id' => $request->new_section_id,
                    'current_semester_id' => $request->new_semester_id,
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully assigned {$updated} student(s) to new section",
                'updated' => $updated
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign students: ' . $e->getMessage()
            ], 500);
        }
    }

    // Bulk promote students (e.g., Grade 11 -> Grade 12)
    public function bulkPromoteStudents(Request $request)
    {
        $request->validate([
            'source_semester_id' => 'required|exists:semesters,id',
            'source_strand_id' => 'required|exists:strands,id',
            'source_level_id' => 'required|exists:levels,id',
            'target_level_id' => 'required|exists:levels,id',
            'target_semester_id' => 'required|exists:semesters,id',
            'section_mapping' => 'required|array'
        ]);

        try {
            DB::beginTransaction();

            $updated = 0;

            foreach ($request->section_mapping as $sourceSection => $targetSection) {
                if (!$targetSection) continue;

                $students = DB::table('students')
                    ->where('section_id', $sourceSection)
                    ->where('current_semester_id', $request->source_semester_id)
                    ->where('student_type', 'regular')
                    ->pluck('id');

                if ($students->count() > 0) {
                    DB::table('students')
                        ->whereIn('id', $students)
                        ->update([
                            'section_id' => $targetSection,
                            'current_semester_id' => $request->target_semester_id,
                            'updated_at' => now()
                        ]);

                    $updated += $students->count();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully promoted {$updated} student(s)",
                'updated' => $updated
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to promote students: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get promotion summary
    public function getPromotionSummary(Request $request)
    {
        $request->validate([
            'source_semester_id' => 'required|exists:semesters,id',
            'source_strand_id' => 'required|exists:strands,id',
            'source_level_id' => 'required|exists:levels,id'
        ]);

        $sections = DB::table('sections')
            ->where('sections.strand_id', $request->source_strand_id)
            ->where('sections.level_id', $request->source_level_id)
            ->where('sections.status', 1)
            ->get();

        $summary = [];

        foreach ($sections as $section) {
            $studentCount = DB::table('students')
                ->where('section_id', $section->id)
                ->where('current_semester_id', $request->source_semester_id)
                ->where('student_type', 'regular')
                ->count();

            $summary[] = [
                'section_id' => $section->id,
                'section_name' => $section->name,
                'section_code' => $section->code,
                'student_count' => $studentCount
            ];
        }

        return response()->json($summary);
    }
}