<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use Exception;
use Illuminate\Support\Facades\DB;

class Year_Management extends MainController
{
    public function list_schoolyear()
    {
        $data = [
            'scripts' => ['class_management/list_schoolyear.js'],
        ];

        return view('admin.class_management.list_schoolyear', $data);
    }
    /**
     * Get school years data (AJAX)
     */
    public function getSchoolYearsData()
    {
        try {
            $schoolYears = DB::table('school_years')
                ->select(
                    'id',
                    'year_start',
                    'year_end',
                    'code',
                    'status',
                    'created_at',
                    'updated_at'
                )
                ->orderBy('year_start', 'desc')
                ->get();

            // Get semester count for each school year
            foreach ($schoolYears as $sy) {
                $sy->semesters_count = DB::table('semesters')
                    ->where('school_year_id', $sy->id)
                    ->count();
            }

            return response()->json([
                'success' => true,
                'data' => $schoolYears
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to get school years data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load school years data.'
            ], 500);
        }
    }

    /**
     * Create school year
     */
    public function createSchoolYear(Request $request)
    {
        $validated = $request->validate([
            'year_start' => 'required|integer|min:2000|max:2100',
            'year_end' => 'required|integer|min:2000|max:2100|gt:year_start',
        ]);

        try {
            // Validate year difference is exactly 1
            if ($request->year_end - $request->year_start != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year must span exactly one year (e.g., 2024-2025).'
                ], 422);
            }

            // Generate code
            $code = $request->year_start . '-' . $request->year_end;

            // Check if already exists
            $exists = DB::table('school_years')->where('code', $code)->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year already exists.'
                ], 422);
            }

            DB::beginTransaction();

            $schoolYearId = DB::table('school_years')->insertGetId([
                'year_start' => $request->year_start,
                'year_end' => $request->year_end,
                'code' => $code,
                'status' => 'upcoming',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('School year created successfully', [
                'school_year_id' => $schoolYearId,
                'code' => $code,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'School year created successfully!',
                'school_year_id' => $schoolYearId
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create school year', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create school year: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update school year
     */
    public function updateSchoolYear(Request $request, $id)
    {
        $validated = $request->validate([
            'year_start' => 'required|integer|min:2000|max:2100',
            'year_end' => 'required|integer|min:2000|max:2100|gt:year_start',
            'status' => 'required|in:active,completed,upcoming',
        ]);

        try {
            $schoolYear = DB::table('school_years')->where('id', $id)->first();

            if (!$schoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year not found.'
                ], 404);
            }

            // Validate year difference
            if ($request->year_end - $request->year_start != 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year must span exactly one year.'
                ], 422);
            }

            $code = $request->year_start . '-' . $request->year_end;

            // Check if code exists (excluding current)
            $exists = DB::table('school_years')
                ->where('code', $code)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year already exists.'
                ], 422);
            }

            // If setting to active, deactivate others
            if ($request->status === 'active') {
                DB::table('school_years')
                    ->where('id', '!=', $id)
                    ->where('status', 'active')
                    ->update(['status' => 'upcoming']);
            }

            DB::beginTransaction();

            DB::table('school_years')
                ->where('id', $id)
                ->update([
                    'year_start' => $request->year_start,
                    'year_end' => $request->year_end,
                    'code' => $code,
                    'status' => $request->status,
                    'updated_at' => now(),
                ]);

            \Log::info('School year updated successfully', [
                'school_year_id' => $id,
                'code' => $code,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'School year updated successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to update school year', [
                'school_year_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update school year: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set active school year
     */
    public function setActiveSchoolYear($id)
    {
        try {
            $schoolYear = DB::table('school_years')->where('id', $id)->first();

            if (!$schoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year not found.'
                ], 404);
            }

            DB::beginTransaction();

            // Deactivate all
            DB::table('school_years')->update(['status' => 'upcoming']);

            // Activate selected
            DB::table('school_years')
                ->where('id', $id)
                ->update([
                    'status' => 'active',
                    'updated_at' => now()
                ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'School year activated successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to activate school year', [
                'school_year_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to activate school year.'
            ], 500);
        }
    }

    /**
     * List semester management page
     */
    public function list_semester()
    {
        $data = [
            'scripts' => ['class_management/list_semester.js'],
        ];

        return view('admin.class_management.list_semester', $data);
    }

    /**
     * Get semesters data (AJAX)
     */
    public function getSemestersData(Request $request)
    {
        try {
            $query = DB::table('semesters')
                ->join('school_years', 'semesters.school_year_id', '=', 'school_years.id')
                ->select(
                    'semesters.id',
                    'semesters.school_year_id',
                    'semesters.name',
                    'semesters.code',
                    'semesters.start_date',
                    'semesters.end_date',
                    'semesters.status',
                    'semesters.created_at',
                    'semesters.updated_at',
                    'school_years.code as school_year_code',
                    'school_years.year_start',
                    'school_years.year_end'
                );

            // Filter by school year if provided
            if ($request->has('school_year_id') && $request->school_year_id != '') {
                $query->where('semesters.school_year_id', $request->school_year_id);
            }

            $semesters = $query->orderBy('school_years.year_start', 'desc')
                ->orderBy('semesters.start_date', 'desc')
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

    /**
     * Create semester
     */
    public function createSemester(Request $request)
    {
        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            // Check if code already exists for this school year
            $exists = DB::table('semesters')
                ->where('school_year_id', $request->school_year_id)
                ->where('code', $request->code)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester code already exists for this school year.'
                ], 422);
            }

            DB::beginTransaction();

            $semesterId = DB::table('semesters')->insertGetId([
                'school_year_id' => $request->school_year_id,
                'name' => $request->name,
                'code' => strtoupper($request->code),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => 'upcoming',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Log::info('Semester created successfully', [
                'semester_id' => $semesterId,
                'code' => $request->code,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semester created successfully!',
                'semester_id' => $semesterId
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to create semester', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create semester: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update semester
     */
    public function updateSemester(Request $request, $id)
    {
        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|in:active,completed,upcoming',
        ]);

        try {
            $semester = DB::table('semesters')->where('id', $id)->first();

            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester not found.'
                ], 404);
            }

            // Check if code exists (excluding current)
            $exists = DB::table('semesters')
                ->where('school_year_id', $request->school_year_id)
                ->where('code', $request->code)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester code already exists for this school year.'
                ], 422);
            }

            // If setting to active, deactivate others
            if ($request->status === 'active') {
                DB::table('semesters')
                    ->where('id', '!=', $id)
                    ->where('status', 'active')
                    ->update(['status' => 'upcoming']);
            }

            DB::beginTransaction();

            DB::table('semesters')
                ->where('id', $id)
                ->update([
                    'school_year_id' => $request->school_year_id,
                    'name' => $request->name,
                    'code' => strtoupper($request->code),
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'status' => $request->status,
                    'updated_at' => now(),
                ]);

            \Log::info('Semester updated successfully', [
                'semester_id' => $id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semester updated successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to update semester', [
                'semester_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update semester: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Set active semester
     */
    public function setActiveSemester($id)
    {
        try {
            $semester = DB::table('semesters')->where('id', $id)->first();

            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester not found.'
                ], 404);
            }

            DB::beginTransaction();

            // Deactivate all
            DB::table('semesters')->update(['status' => 'upcoming']);

            // Activate selected
            DB::table('semesters')
                ->where('id', $id)
                ->update([
                    'status' => 'active',
                    'updated_at' => now()
                ]);

            // Also set the parent school year as active
            DB::table('school_years')->update(['status' => 'upcoming']);
            DB::table('school_years')
                ->where('id', $semester->school_year_id)
                ->update(['status' => 'active']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semester activated successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to activate semester', [
                'semester_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to activate semester.'
            ], 500);
        }
    }
}
