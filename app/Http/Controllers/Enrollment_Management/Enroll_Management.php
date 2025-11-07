<?php

namespace App\Http\Controllers\Enrollment_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;

use App\Models\Enroll_Management\Section;


class Enroll_Management extends MainController
{

    public function enroll_class()
    {
        $data = [
            'scripts' => ['enroll_management/enroll_class.js'],
        ];
        return view('admin.enroll_management.enroll_class', $data);
    }

    public function enroll_section()
    {

        $data = [
            'scripts' => ['enroll_management/enroll_section.js'],
        ];

        return view('admin.enroll_management.enroll_section', $data);
    }

        /**
     * Get sections data with filtering (AJAX)
     */
    public function getSectionsData(Request $request)
    {
        try {
            $query = Section::with(['strand', 'level', 'students'])
                ->active();

            // Apply filters
            if ($request->filled('grade')) {
                $query->byLevel($request->grade);
            }

            if ($request->filled('strand')) {
                $query->byStrand($request->strand);
            }

            if ($request->filled('search')) {
                $query->search($request->search);
            }

            $sections = $query->get();

            // Format data for response
            $formattedSections = $sections->map(function ($section) {
                return [
                    'id' => $section->id,
                    'code' => $section->code,
                    'name' => $section->name,
                    'grade' => $section->level->name,
                    'level_id' => $section->level_id,
                    'strand' => $section->strand->name,
                    'strand_code' => $section->strand->code,
                    'strand_id' => $section->strand_id,
                    'student_count' => $section->students->count(),
                    'class_count' => $section->classes->count(),
                    'full_name' => $section->full_name,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedSections
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching sections: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get section details including enrolled classes (AJAX)
     */
    public function getDetails($id)
    {
        try {
            $section = Section::with(['strand', 'level', 'students', 'classes'])
                ->findOrFail($id);

            // Format classes data
            $classes = $section->classes->map(function ($class) {
                return [
                    'id' => $class->id,
                    'code' => $class->class_code,
                    'name' => $class->class_name,
                ];
            });

            $data = [
                'id' => $section->id,
                'code' => $section->code,
                'name' => $section->name,
                'grade' => $section->level->name,
                'strand' => $section->strand->name,
                'strand_code' => $section->strand->code,
                'student_count' => $section->students->count(),
                'full_name' => $section->full_name,
                'classes' => $classes,
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching section details: ' . $e->getMessage()
            ], 500);
        }
    }

    public function enroll_student()
    {

        $data = [
            'scripts' => ['enroll_management/enroll_student.js'],
        ];

        return view('admin.enroll_management.enroll_student', $data);
    }
}
