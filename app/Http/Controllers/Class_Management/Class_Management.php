<?php

namespace App\Http\Controllers\Class_Management;

use App\Http\Controllers\MainController;
use App\Models\User_Management\Section;
use App\Models\User_Management\Strand;
use Illuminate\Http\Request;
use Exception;
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
            ->select(
                'sections.code as code',
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


    // ---------------------------------------------------------------------------
    //  Get Strands Data (AJAX)
    // ---------------------------------------------------------------------------
    public function getStrandsData()
    {
        try {
            $strands = DB::table('strands')
                ->leftJoin('sections', 'strands.id', '=', 'sections.strand_id')
                ->select(
                    'strands.id',
                    'strands.code',
                    'strands.name',
                    'strands.status',
                    'strands.created_at',
                    'strands.updated_at',
                    DB::raw('COUNT(sections.id) as sections_count')
                )
                ->groupBy('strands.id', 'strands.code', 'strands.name', 'strands.status', 'strands.created_at', 'strands.updated_at')
                ->orderBy('strands.code', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $strands
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get strands data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load strands data.'
            ], 500);
        }
    }

    // ---------------------------------------------------------------------------
    //  Create Strand
    // ---------------------------------------------------------------------------
    public function createStrand(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:strands,code',
            'name' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $strandId = DB::table('strands')->insertGetId([
                'code' => strtoupper($request->code),
                'name' => $request->name,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('Strand created successfully', [
                'strand_id' => $strandId,
                'code' => $request->code,
                'name' => $request->name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Strand created successfully!',
                'strand_id' => $strandId
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create strand', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create strand: ' . $e->getMessage()
            ], 500);
        }
    }

    // ---------------------------------------------------------------------------
    //  Update Strand
    // ---------------------------------------------------------------------------
    public function updateStrand(Request $request, $id)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:strands,code,' . $id,
            'name' => 'required|string|max:255',
        ]);

        try {
            // Check if strand exists
            $strand = DB::table('strands')->where('id', $id)->first();

            if (!$strand) {
                return response()->json([
                    'success' => false,
                    'message' => 'Strand not found.'
                ], 404);
            }

            DB::beginTransaction();

            DB::table('strands')
                ->where('id', $id)
                ->update([
                    'code' => strtoupper($request->code),
                    'name' => $request->name,
                    'updated_at' => now(),
                ]);

            \Log::info('Strand updated successfully', [
                'strand_id' => $id,
                'code' => $request->code,
                'name' => $request->name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Strand updated successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to update strand', [
                'strand_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update strand: ' . $e->getMessage()
            ], 500);
        }
    }
/**
 * Get sections for a specific strand
 */
public function getStrandSections($id)
{
    try {
        $sections = DB::table('sections')
            ->join('levels', 'sections.level_id', '=', 'levels.id')
            ->where('sections.strand_id', $id)
            ->select(
                'sections.id',
                'sections.code',
                'sections.name',
                'sections.status',
                'levels.name as level_name'
            )
            ->orderBy('levels.id', 'asc')
            ->orderBy('sections.code', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $sections
        ]);
    } catch (Exception $e) {
        \Log::error('Failed to get strand sections', [
            'strand_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to load sections data.'
        ], 500);
    }
}
    public function list_schoolyear()
    {
        $data = [
            'scripts' => ['class_management/list_schoolyear.js'],
        ];

        return view('admin.class_management.list_schoolyear', $data);
    }
}
