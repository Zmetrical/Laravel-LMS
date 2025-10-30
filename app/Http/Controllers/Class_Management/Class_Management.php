<?php

namespace App\Http\Controllers\Class_Management;
use App\Http\Controllers\MainController;
use App\Models\User_Management\Section;
use App\Models\User_Management\Strand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Class_Management\Classes;


class Class_Management extends MainController
{


    // === Insert Class
    public function insert_class(Request $request)
    {
        // Validate the form data
        $validated = $request->validate([
            'class_code' => 'required|string|max:100|unique:classes,class_code',
            'class_name' => 'required|string|max:250',
            'ww_perc' => 'required|integer|min:0|max:100',
            'pt_perc' => 'required|integer|min:0|max:100',
            'qa_perce' => 'required|integer|min:0|max:100',
        ]);

        try {
            // Validate that percentages total 100
            $totalPercentage = $request->ww_perc + $request->pt_perc + $request->qa_perce;
            if ($totalPercentage != 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade weight distribution must total exactly 100%. Current total: ' . $totalPercentage . '%'
                ], 422);
            }

            // Start database transaction
            DB::beginTransaction();

            // Insert class into classes table
            $class = DB::table('classes')->insertGetId([
                'class_code' => $request->class_code,
                'class_name' => $request->class_name,
                'ww_perc' => $request->ww_perc,
                'pt_perc' => $request->pt_perc,
                'qa_perce' => $request->qa_perce,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('Class created successfully', [
                'class_id' => $class,
                'class_code' => $request->class_code,
                'class_name' => $request->class_name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Class created successfully!',
                'class_id' => $class
            ], 201);

        } catch (Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            \Log::error('Failed to create class', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create class: ' . $e->getMessage()
            ], 500);
        }
    }


    // ---------------------------------------------------------------------------
    //  List Class
    // ---------------------------------------------------------------------------

    public function list_class()
    {

        $classes = Classes::all();

        $data = [
            'scripts' => ['class_management/list_class.js'],
            'classes' => $classes
        ];

        return view('admin.class_management.list_class', $data);
    }

    // ---------------------------------------------------------------------------
    //  Create Strand
    // ---------------------------------------------------------------------------

    public function list_strand()
    {
        $strands = Strand::all();

        $data = [
            'scripts' => ['class_management/list_strand.js'],
            'strands' => $strands,
        ];

        return view('admin.class_management.list_strand', $data);
    }

    public function list_section()
    {
        $sections = DB::table('sections')
            ->join('levels', 'sections.level_id', '=', 'levels.id')
            ->join('strands', 'sections.strand_id', '=', second: 'strands.id')
            ->select('sections.code as code',
                'sections.name as name',
                'levels.name as level',
                'strands.code as strand'
            )
            ->get();

        $data = [
            'scripts' => ['class_management/list_section.js'],
            'sections' => $sections
        ];

        return view('admin.class_management.list_section', $data);
    }

    public function list_schoolyear()
    {


        $data = [
            'scripts' => ['class_management/list_schoolyear.js'],
        ];

        return view('admin.class_management.list_schoolyear', $data);
    }
}
