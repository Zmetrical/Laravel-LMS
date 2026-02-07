<?php

namespace App\Http\Controllers\Class_Management;

use Illuminate\Http\Request;
use App\Http\Controllers\MainController;
use App\Traits\AuditLogger;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class Archive_Management extends MainController
{
    use AuditLogger;

    public function archivePage()
    {
        $data = [
            'scripts' => ['class_management/archive_management.js'],
        ];

        return view('admin.class_management.archive_management', $data);
    }

    public function verifyAdminAccess(Request $request)
    {
        $validated = $request->validate([
            'admin_password' => 'required|string',
        ]);

        try {
            // Use Auth guard instead of session('admin_id')
            $adminId = Auth::guard('admin')->id();
            
            if (!$adminId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin session not found.'
                ], 401);
            }

            $admin = DB::table('admins')
                ->where('id', $adminId)
                ->first();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin account not found.'
                ], 404);
            }

            // Verify password using Hash::check
            if (!Hash::check($request->admin_password, $admin->admin_password)) {
                $this->logAudit(
                    'failed_verification',
                    'archive_access',
                    null,
                    'Failed archive access verification attempt',
                    null,
                    ['admin_id' => $adminId]
                );

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password.'
                ], 401);
            }

            // Store verification in session
            session(['archive_verified' => true, 'archive_verified_at' => now()]);

            $this->logAudit(
                'verified',
                'archive_access',
                null,
                'Successfully verified archive access',
                null,
                ['admin_id' => $adminId]
            );

            return response()->json([
                'success' => true,
                'message' => 'Access granted.'
            ]);
        } catch (Exception $e) {
            \Log::error('Failed to verify admin access', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Verification failed.'
            ], 500);
        }
    }

    public function archiveSchoolYear($id)
    {
        try {
            $schoolYear = DB::table('school_years')->where('id', $id)->first();

            if (!$schoolYear) {
                return response()->json([
                    'success' => false,
                    'message' => 'School year not found.'
                ], 404);
            }

            if ($schoolYear->status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot archive active school year.'
                ], 422);
            }

            DB::beginTransaction();

            DB::table('school_years')
                ->where('id', $id)
                ->update([
                    'status' => 'completed',
                    'updated_at' => now()
                ]);

            $this->logAudit(
                'archived',
                'school_years',
                (string)$id,
                "Archived school year '{$schoolYear->code}'",
                ['status' => $schoolYear->status],
                ['status' => 'completed']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'School year archived successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to archive school year', [
                'school_year_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to archive school year.'
            ], 500);
        }
    }

    public function archiveSemester($id)
    {
        try {
            $semester = DB::table('semesters')->where('id', $id)->first();

            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester not found.'
                ], 404);
            }

            if ($semester->status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot archive active semester.'
                ], 422);
            }

            $schoolYear = DB::table('school_years')->where('id', $semester->school_year_id)->first();

            DB::beginTransaction();

            DB::table('semesters')
                ->where('id', $id)
                ->update([
                    'status' => 'completed',
                    'updated_at' => now()
                ]);

            $this->logAudit(
                'archived',
                'semesters',
                (string)$id,
                "Archived {$semester->name} for school year {$schoolYear->code}",
                ['status' => $semester->status],
                ['status' => 'completed']
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semester archived successfully!'
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            \Log::error('Failed to archive semester', [
                'semester_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to archive semester.'
            ], 500);
        }
    }
}