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

    /**
     * Generate unique class code
     */
    private function generateClassCode()
    {
        $prefix = 'CLASS';
        
        // Get the last class ID or count
        $lastClass = DB::table('classes')->orderBy('id', 'desc')->first();
        $nextNumber = $lastClass ? $lastClass->id + 1 : 1;
        
        // Format: CLS-001, CLS-002, etc.
        $code = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        
        // Ensure uniqueness (in case of deletions)
        while (DB::table('classes')->where('class_code', $code)->exists()) {
            $nextNumber++;
            $code = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }
        
        return $code;
    }

    /**
     * Insert Class (Updated - Auto Code Generation)
     */
    public function insert_class(Request $request)
    {
        $validated = $request->validate([
            'class_name' => 'required|string|max:250',
            'ww_perc' => 'required|integer|min:0|max:100',
            'pt_perc' => 'required|integer|min:0|max:100',
            'qa_perce' => 'required|integer|min:0|max:100',
        ]);

        try {
            // Validate percentages total 100
            $totalPercentage = $request->ww_perc + $request->pt_perc + $request->qa_perce;
            if ($totalPercentage != 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade weight distribution must total exactly 100%. Current total: ' . $totalPercentage . '%'
                ], 422);
            }

            DB::beginTransaction();

            // Auto-generate class code
            $classCode = $this->generateClassCode();

            $class = DB::table('classes')->insertGetId([
                'class_code' => $classCode,
                'class_name' => $request->class_name,
                'ww_perc' => $request->ww_perc,
                'pt_perc' => $request->pt_perc,
                'qa_perce' => $request->qa_perce,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('Class created successfully', [
                'class_id' => $class,
                'class_code' => $classCode,
                'class_name' => $request->class_name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Class created successfully with code: ' . $classCode,
                'class_id' => $class,
                'class_code' => $classCode
            ], 201);
        } catch (Exception $e) {
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

    
    /**
     * Update Class
     */
    public function updateClass(Request $request, $id)
    {
        $validated = $request->validate([
            'class_name' => 'required|string|max:250',
            'ww_perc' => 'required|integer|min:0|max:100',
            'pt_perc' => 'required|integer|min:0|max:100',
            'qa_perce' => 'required|integer|min:0|max:100',
        ]);

        try {
            // Check if class exists
            $class = DB::table('classes')->where('id', $id)->first();

            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found.'
                ], 404);
            }

            // Validate percentages total 100
            $totalPercentage = $request->ww_perc + $request->pt_perc + $request->qa_perce;
            if ($totalPercentage != 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade weight distribution must total exactly 100%. Current total: ' . $totalPercentage . '%'
                ], 422);
            }

            DB::beginTransaction();

            DB::table('classes')
                ->where('id', $id)
                ->update([
                    'class_name' => $request->class_name,
                    'ww_perc' => $request->ww_perc,
                    'pt_perc' => $request->pt_perc,
                    'qa_perce' => $request->qa_perce,
                    'updated_at' => now(),
                ]);

            \Log::info('Class updated successfully', [
                'class_id' => $id,
                'class_code' => $class->class_code,
                'class_name' => $request->class_name,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Class updated successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to update class', [
                'class_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update class: ' . $e->getMessage()
            ], 500);
        }
    }

    
    /**
     * Get single class data for editing
     */
    public function getClassData($id)
    {
        try {
            $class = DB::table('classes')->where('id', $id)->first();

            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $class
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get class data', [
                'class_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load class data.'
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
    //  Get Strands Data 
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


    
    /**
     * Get sections data with filters (AJAX)
     */
    public function getSectionsData(Request $request)
    {
        try {
            $query = DB::table('sections')
                ->join('levels', 'sections.level_id', '=', 'levels.id')
                ->join('strands', 'sections.strand_id', '=', 'strands.id')
                ->select(
                    'sections.id',
                    'sections.code',
                    'sections.name',
                    'sections.status',
                    'sections.created_at',
                    'sections.updated_at',
                    'levels.id as level_id',
                    'levels.name as level_name',
                    'strands.id as strand_id',
                    'strands.code as strand_code',
                    'strands.name as strand_name'
                );

            // Apply filters
            if ($request->has('strand') && $request->strand != '') {
                $query->where('strands.code', $request->strand);
            }

            if ($request->has('level') && $request->level != '') {
                $query->where('levels.id', $request->level);
            }

            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('sections.name', 'like', "%{$search}%")
                        ->orWhere('sections.code', 'like', "%{$search}%");
                });
            }

            $sections = $query->orderBy('strands.code', 'asc')
                ->orderBy('levels.id', 'asc')
                ->orderBy('sections.name', 'asc')
                ->get();

            // Get enrolled classes count for each section
            foreach ($sections as $section) {
                $section->classes_count = DB::table('section_class_matrix')
                    ->where('section_id', $section->id)
                    ->count();
            }

            return response()->json([
                'success' => true,
                'data' => $sections
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get sections data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load sections data.'
            ], 500);
        }
    }

    /**
     * Get levels data (AJAX)
     */
    public function getLevelsData()
    {
        try {
            $levels = DB::table('levels')
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $levels
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get levels data', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load levels data.'
            ], 500);
        }
    }

/**
 * Create section
 */
public function createSection(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'strand_id' => 'required|exists:strands,id',
        'level_id' => 'required|exists:levels,id',
    ]);

    try {
        // Get strand and level info
        $strand = DB::table('strands')->where('id', $request->strand_id)->first();
        $level = DB::table('levels')->where('id', $request->level_id)->first();

        DB::beginTransaction();

        // Get the next sequence number for this strand-level combination
        $maxCode = DB::table('sections')
            ->where('code', 'LIKE', $strand->code . '-' . $level->name . '-%')
            ->orderBy('code', 'desc')
            ->first();

        $nextNumber = 1;
        if ($maxCode) {
            // Extract the number from the last code (e.g., "STEM-11-3" -> 3)
            $parts = explode('-', $maxCode->code);
            $lastNumber = (int) end($parts);
            $nextNumber = $lastNumber + 1;
        }

        // Create code format: STRAND-LEVEL-NUMBER (e.g., STEM-11-1)
        $code = strtoupper($strand->code . '-' . $level->name . '-' . $nextNumber);

        $sectionId = DB::table('sections')->insertGetId([
            'code' => $code,
            'name' => strtoupper($request->name),
            'strand_id' => $request->strand_id,
            'level_id' => $request->level_id,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Log::info('Section created successfully', [
            'section_id' => $sectionId,
            'code' => $code,
            'name' => $request->name,
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully!',
            'section_id' => $sectionId,
            'code' => $code
        ], 201);
    } catch (Exception $e) {
        DB::rollBack();

        \Log::error('Failed to create section', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create section: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Update section
 */
public function updateSection(Request $request, $id)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'strand_id' => 'required|exists:strands,id',
        'level_id' => 'required|exists:levels,id',
    ]);

    try {
        // Check if section exists
        $section = DB::table('sections')->where('id', $id)->first();

        if (!$section) {
            return response()->json([
                'success' => false,
                'message' => 'Section not found.'
            ], 404);
        }

        // Get strand and level info
        $strand = DB::table('strands')->where('id', $request->strand_id)->first();
        $level = DB::table('levels')->where('id', $request->level_id)->first();

        DB::beginTransaction();

        // Check if strand or level changed
        $strandChanged = $section->strand_id != $request->strand_id;
        $levelChanged = $section->level_id != $request->level_id;

        if ($strandChanged || $levelChanged) {
            // Need to generate new code if strand or level changed
            $maxCode = DB::table('sections')
                ->where('code', 'LIKE', $strand->code . '-' . $level->name . '-%')
                ->where('id', '!=', $id)
                ->orderBy('code', 'desc')
                ->first();

            $nextNumber = 1;
            if ($maxCode) {
                $parts = explode('-', $maxCode->code);
                $lastNumber = (int) end($parts);
                $nextNumber = $lastNumber + 1;
            }

            $code = strtoupper($strand->code . '-' . $level->name . '-' . $nextNumber);
        } else {
            // Keep the existing code if strand and level didn't change
            $code = $section->code;
        }

        DB::table('sections')
            ->where('id', $id)
            ->update([
                'code' => $code,
                'name' => strtoupper($request->name),
                'strand_id' => $request->strand_id,
                'level_id' => $request->level_id,
                'updated_at' => now(),
            ]);

        \Log::info('Section updated successfully', [
            'section_id' => $id,
            'code' => $code,
            'name' => $request->name,
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully!',
            'code' => $code
        ]);
    } catch (Exception $e) {
        DB::rollBack();

        \Log::error('Failed to update section', [
            'section_id' => $id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to update section: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Get section classes (enrolled classes for a section)
     */
    public function getSectionClasses($id)
    {
        try {
            $classes = DB::table('section_class_matrix')
                ->join('classes', 'section_class_matrix.class_id', '=', 'classes.id')
                ->where('section_class_matrix.section_id', $id)
                ->select(
                    'classes.id',
                    'classes.class_code',
                    'classes.class_name',
                    'classes.ww_perc',
                    'classes.pt_perc',
                    'classes.qa_perce'
                )
                ->orderBy('classes.class_code', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $classes
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get section classes', [
                'section_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load classes data.'
            ], 500);
        }
    }


}
