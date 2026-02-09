<?php

namespace App\Http\Controllers\Class_Management;

use App\Http\Controllers\MainController;
use App\Models\User_Management\Section;
use App\Models\User_Management\Strand;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;

use App\Models\Class_Management\Classes;
use App\Traits\AuditLogger;


class Class_Management extends MainController
{
    use AuditLogger;

    /**
     * Generate unique class code
     */
    private function generateClassCode()
    {
        $prefix = 'CLASS';
        
        $lastClass = DB::table('classes')->orderBy('id', 'desc')->first();
        $nextNumber = $lastClass ? $lastClass->id + 1 : 1;
        
        $code = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        
        while (DB::table('classes')->where('class_code', $code)->exists()) {
            $nextNumber++;
            $code = $prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        }
        
        return $code;
    }

    /**
     * Insert Class
     */
    public function insert_class(Request $request)
    {
        $validated = $request->validate([
            'class_name' => 'required|string|max:250',
            'class_category' => 'required|in:CORE SUBJECT,APPLIED SUBJECT,SPECIALIZED SUBJECT',
            'ww_perc' => 'required|integer|min:0|max:100',
            'pt_perc' => 'required|integer|min:0|max:100',
            'qa_perce' => 'required|integer|min:0|max:100',
        ]);

        try {
            $totalPercentage = $request->ww_perc + $request->pt_perc + $request->qa_perce;
            if ($totalPercentage != 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade weight distribution must total exactly 100%. Current total: ' . $totalPercentage . '%'
                ], 422);
            }

            DB::beginTransaction();

            $classCode = $this->generateClassCode();

            $class = DB::table('classes')->insertGetId([
                'class_code' => $classCode,
                'class_name' => $request->class_name,
                'class_category' => $request->class_category,
                'ww_perc' => $request->ww_perc,
                'pt_perc' => $request->pt_perc,
                'qa_perce' => $request->qa_perce,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Audit log
            $this->logAudit(
                'created',
                'classes',
                (string)$class,
                "Created class: {$request->class_name} ({$classCode})",
                null,
                [
                    'class_code' => $classCode,
                    'class_name' => $request->class_name,
                    'class_category' => $request->class_category,
                    'ww_perc' => $request->ww_perc,
                    'pt_perc' => $request->pt_perc,
                    'qa_perce' => $request->qa_perce,
                ]
            );

            \Log::info('Class created successfully', [
                'class_id' => $class,
                'class_code' => $classCode,
                'class_name' => $request->class_name,
                'class_category' => $request->class_category,
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
            'class_category' => 'required|in:CORE SUBJECT,APPLIED SUBJECT,SPECIALIZED SUBJECT',
            'ww_perc' => 'required|integer|min:0|max:100',
            'pt_perc' => 'required|integer|min:0|max:100',
            'qa_perce' => 'required|integer|min:0|max:100',
        ]);

        try {
            $class = DB::table('classes')->where('id', $id)->first();

            if (!$class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Class not found.'
                ], 404);
            }

            $totalPercentage = $request->ww_perc + $request->pt_perc + $request->qa_perce;
            if ($totalPercentage != 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Grade weight distribution must total exactly 100%. Current total: ' . $totalPercentage . '%'
                ], 422);
            }

            DB::beginTransaction();

            // Store old values for audit
            $oldValues = [
                'class_name' => $class->class_name,
                'class_category' => $class->class_category,
                'ww_perc' => $class->ww_perc,
                'pt_perc' => $class->pt_perc,
                'qa_perce' => $class->qa_perce,
            ];

            $newValues = [
                'class_name' => $request->class_name,
                'class_category' => $request->class_category,
                'ww_perc' => $request->ww_perc,
                'pt_perc' => $request->pt_perc,
                'qa_perce' => $request->qa_perce,
            ];

            DB::table('classes')
                ->where('id', $id)
                ->update([
                    'class_name' => $request->class_name,
                    'class_category' => $request->class_category,
                    'ww_perc' => $request->ww_perc,
                    'pt_perc' => $request->pt_perc,
                    'qa_perce' => $request->qa_perce,
                    'updated_at' => now(),
                ]);

            // Audit log
            $this->logAudit(
                'updated',
                'classes',
                (string)$id,
                "Updated class: {$request->class_name} ({$class->class_code})",
                $oldValues,
                $newValues
            );

            \Log::info('Class updated successfully', [
                'class_id' => $id,
                'class_code' => $class->class_code,
                'class_name' => $request->class_name,
                'class_category' => $request->class_category,
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

    /**
     * Get classes list for DataTables (AJAX)
     */
    public function getClassesList()
    {
        $classes = DB::table('classes')
            ->select('id', 'class_code', 'class_name', 'class_category', 'ww_perc', 'pt_perc', 'qa_perce')
            ->orderBy('class_name', 'asc')
            ->get();

        return response()->json([
            'data' => $classes
        ]);
    }

    public function list_class()
    {
        $classes = Classes::all();

        $data = [
            'scripts' => ['class_management/list_class.js'],
            'classes' => $classes
        ];

        return view('admin.class_management.list_class', $data);
    }

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
            ->join('strands', 'sections.strand_id', '=', 'strands.id')
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

            // Audit log
            $this->logAudit(
                'created',
                'strands',
                (string)$strandId,
                "Created strand: {$request->name} ({$request->code})",
                null,
                [
                    'code' => strtoupper($request->code),
                    'name' => $request->name,
                    'status' => 1,
                ]
            );

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

    public function updateStrand(Request $request, $id)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:100|unique:strands,code,' . $id,
            'name' => 'required|string|max:255',
        ]);

        try {
            $strand = DB::table('strands')->where('id', $id)->first();

            if (!$strand) {
                return response()->json([
                    'success' => false,
                    'message' => 'Strand not found.'
                ], 404);
            }

            DB::beginTransaction();

            // Store old values for audit
            $oldValues = [
                'code' => $strand->code,
                'name' => $strand->name,
            ];

            $newValues = [
                'code' => strtoupper($request->code),
                'name' => $request->name,
            ];

            DB::table('strands')
                ->where('id', $id)
                ->update([
                    'code' => strtoupper($request->code),
                    'name' => $request->name,
                    'updated_at' => now(),
                ]);

            // Audit log
            $this->logAudit(
                'updated',
                'strands',
                (string)$id,
                "Updated strand: {$request->name} ({$request->code})",
                $oldValues,
                $newValues
            );

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

            // Get semester filter if provided
            $semesterId = $request->has('semester') && $request->semester != '' ? $request->semester : null;

            foreach ($sections as $section) {
                if ($semesterId) {
                    $section->classes_count = DB::table('section_class_matrix')
                        ->where('section_id', $section->id)
                        ->where('semester_id', $semesterId)
                        ->count();
                } else {
                    $section->classes_count = DB::table('section_class_matrix')
                        ->where('section_id', $section->id)
                        ->count();
                }
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
     * Get semesters data (AJAX)
     */
    public function getSemestersData()
    {
        try {
            $semesters = DB::table('semesters')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->select(
                    'semesters.id',
                    'semesters.name',
                    'semesters.code',
                    'semesters.status',
                    'school_years.code as school_year_code'
                )
                ->orderBy('school_years.year_start', 'desc')
                ->orderBy('semesters.code', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $semesters
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get semesters data', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load semesters data.'
            ], 500);
        }
    }

    public function createSection(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'strand_id' => 'required|exists:strands,id',
            'level_id' => 'required|exists:levels,id',
        ]);

        try {
            $strand = DB::table('strands')->where('id', $request->strand_id)->first();
            $level = DB::table('levels')->where('id', $request->level_id)->first();

            DB::beginTransaction();

            $maxCode = DB::table('sections')
                ->where('code', 'LIKE', $strand->code . '-' . $level->name . '-%')
                ->orderBy('code', 'desc')
                ->first();

            $nextNumber = 1;
            if ($maxCode) {
                $parts = explode('-', $maxCode->code);
                $lastNumber = (int) end($parts);
                $nextNumber = $lastNumber + 1;
            }

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

            // Audit log
            $this->logAudit(
                'created',
                'sections',
                (string)$sectionId,
                "Created section: {$request->name} ({$code})",
                null,
                [
                    'code' => $code,
                    'name' => strtoupper($request->name),
                    'strand_id' => $request->strand_id,
                    'strand_name' => $strand->name,
                    'level_id' => $request->level_id,
                    'level_name' => $level->name,
                    'status' => 1,
                ]
            );

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

    public function updateSection(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'strand_id' => 'required|exists:strands,id',
            'level_id' => 'required|exists:levels,id',
        ]);

        try {
            $section = DB::table('sections')->where('id', $id)->first();

            if (!$section) {
                return response()->json([
                    'success' => false,
                    'message' => 'Section not found.'
                ], 404);
            }

            $strand = DB::table('strands')->where('id', $request->strand_id)->first();
            $level = DB::table('levels')->where('id', $request->level_id)->first();

            // Get old strand and level for audit
            $oldStrand = DB::table('strands')->where('id', $section->strand_id)->first();
            $oldLevel = DB::table('levels')->where('id', $section->level_id)->first();

            DB::beginTransaction();

            $strandChanged = $section->strand_id != $request->strand_id;
            $levelChanged = $section->level_id != $request->level_id;

            if ($strandChanged || $levelChanged) {
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
                $code = $section->code;
            }

            // Store old values for audit
            $oldValues = [
                'code' => $section->code,
                'name' => $section->name,
                'strand_id' => $section->strand_id,
                'strand_name' => $oldStrand->name,
                'level_id' => $section->level_id,
                'level_name' => $oldLevel->name,
            ];

            $newValues = [
                'code' => $code,
                'name' => strtoupper($request->name),
                'strand_id' => $request->strand_id,
                'strand_name' => $strand->name,
                'level_id' => $request->level_id,
                'level_name' => $level->name,
            ];

            DB::table('sections')
                ->where('id', $id)
                ->update([
                    'code' => $code,
                    'name' => strtoupper($request->name),
                    'strand_id' => $request->strand_id,
                    'level_id' => $request->level_id,
                    'updated_at' => now(),
                ]);

            // Audit log
            $this->logAudit(
                'updated',
                'sections',
                (string)$id,
                "Updated section: {$request->name} ({$code})",
                $oldValues,
                $newValues
            );

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
     * Get section classes (filtered by semester)
     */
    public function getSectionClasses($id, Request $request)
    {
        try {
            $query = DB::table('section_class_matrix')
                ->join('classes', 'section_class_matrix.class_id', '=', 'classes.id')
                ->where('section_class_matrix.section_id', $id);

            // Filter by semester if provided
            if ($request->has('semester_id') && $request->semester_id != '') {
                $query->where('section_class_matrix.semester_id', $request->semester_id);
            }

            $classes = $query->select(
                    'classes.id',
                    'classes.class_code',
                    'classes.class_name',
                    'classes.class_category',
                    'classes.ww_perc',
                    'classes.pt_perc',
                    'classes.qa_perce',
                    'section_class_matrix.semester_id',
                    'section_class_matrix.id as matrix_id'
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

    /**
     * Get available classes for section (not yet assigned for a semester)
     */
    public function getAvailableClasses($sectionId, Request $request)
    {
        try {
            $semesterId = $request->semester_id;

            if (!$semesterId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester ID is required.'
                ], 422);
            }

            // Get all classes that are NOT assigned to this section in this semester
            $availableClasses = DB::table('classes')
                ->whereNotIn('id', function($query) use ($sectionId, $semesterId) {
                    $query->select('class_id')
                        ->from('section_class_matrix')
                        ->where('section_id', $sectionId)
                        ->where('semester_id', $semesterId);
                })
                ->select('id', 'class_code', 'class_name', 'class_category')
                ->orderBy('class_code', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $availableClasses
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get available classes', [
                'section_id' => $sectionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load available classes.'
            ], 500);
        }
    }

    /**
     * Assign class to section for a semester
     */
    public function assignClassToSection(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'class_id' => 'required|exists:classes,id',
            'semester_id' => 'required|exists:semesters,id',
        ]);

        try {
            // Check if already assigned
            $exists = DB::table('section_class_matrix')
                ->where('section_id', $request->section_id)
                ->where('class_id', $request->class_id)
                ->where('semester_id', $request->semester_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This class is already assigned to this section for the selected semester.'
                ], 422);
            }

            // Get section, class, and semester details for audit
            $section = DB::table('sections')->where('id', $request->section_id)->first();
            $class = DB::table('classes')->where('id', $request->class_id)->first();
            $semester = DB::table('semesters')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->where('semesters.id', $request->semester_id)
                ->select('semesters.*', 'school_years.code as sy_code')
                ->first();

            DB::beginTransaction();

            $matrixId = DB::table('section_class_matrix')->insertGetId([
                'section_id' => $request->section_id,
                'class_id' => $request->class_id,
                'semester_id' => $request->semester_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Audit log
            $this->logAudit(
                'assigned',
                'section_class_matrix',
                (string)$matrixId,
                "Assigned class '{$class->class_name}' to section '{$section->name}' for {$semester->name} {$semester->sy_code}",
                null,
                [
                    'section_id' => $request->section_id,
                    'section_code' => $section->code,
                    'section_name' => $section->name,
                    'class_id' => $request->class_id,
                    'class_code' => $class->class_code,
                    'class_name' => $class->class_name,
                    'class_category' => $class->class_category,
                    'semester_id' => $request->semester_id,
                    'semester_name' => $semester->name,
                    'school_year' => $semester->sy_code,
                ]
            );

            \Log::info('Class assigned to section', [
                'section_id' => $request->section_id,
                'class_id' => $request->class_id,
                'semester_id' => $request->semester_id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Class assigned successfully!'
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to assign class', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign class: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove class from section
     */
    public function removeClassFromSection($matrixId)
    {
        try {
            $matrix = DB::table('section_class_matrix')->where('id', $matrixId)->first();

            if (!$matrix) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found.'
                ], 404);
            }

            // Get details for audit log
            $section = DB::table('sections')->where('id', $matrix->section_id)->first();
            $class = DB::table('classes')->where('id', $matrix->class_id)->first();
            $semester = DB::table('semesters')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->where('semesters.id', $matrix->semester_id)
                ->select('semesters.*', 'school_years.code as sy_code')
                ->first();

            DB::beginTransaction();

            // Store old values for audit
            $oldValues = [
                'section_id' => $matrix->section_id,
                'section_code' => $section->code,
                'section_name' => $section->name,
                'class_id' => $matrix->class_id,
                'class_code' => $class->class_code,
                'class_name' => $class->class_name,
                'class_category' => $class->class_category,
                'semester_id' => $matrix->semester_id,
                'semester_name' => $semester->name,
                'school_year' => $semester->sy_code,
            ];

            DB::table('section_class_matrix')->where('id', $matrixId)->delete();

            // Audit log
            $this->logAudit(
                'unassigned',
                'section_class_matrix',
                (string)$matrixId,
                "Removed class '{$class->class_name}' from section '{$section->name}' for {$semester->name} {$semester->sy_code}",
                $oldValues,
                null
            );

            \Log::info('Class removed from section', [
                'matrix_id' => $matrixId,
                'section_id' => $matrix->section_id,
                'class_id' => $matrix->class_id,
                'semester_id' => $matrix->semester_id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Class removed successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to remove class', [
                'matrix_id' => $matrixId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove class: ' . $e->getMessage()
            ], 500);
        }
    }
}