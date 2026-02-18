<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Traits\AuditLogger;
use Exception;

class Admin_Management extends Controller
{
    use AuditLogger;

    // ---------------------------------------------------------------------------
    //  Helper - Super Admin check
    // ---------------------------------------------------------------------------

    private function checkSuperAdminAccess()
    {
        $admin = Auth::guard('admin')->user();
        return $admin && $admin->admin_type == 1;
    }

    // ---------------------------------------------------------------------------
    //  Create Admin
    // ---------------------------------------------------------------------------

    public function create_admin(Request $request)
    {
        if (!$this->checkSuperAdminAccess()) {
            abort(403, 'Unauthorized access - Super Admin only');
        }

        return view('admin.user_management.create_admin', [
            'scripts' => ['user_management/create_admin.js'],
        ]);
    }

    public function insert_admin(Request $request)
    {
        if (!$this->checkSuperAdminAccess()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $request->validate([
            'admin_name' => 'required|string|max:100',
            'email'      => 'required|email|unique:admins,email|max:100',
        ]);

        try {
            DB::beginTransaction();

            $defaultPassword = $this->generateAdminPassword();

            $newAdminId = DB::table('admins')->insertGetId([
                'admin_name'     => $request->admin_name,
                'email'          => $request->email,
                'admin_password' => Hash::make($defaultPassword),
                'admin_type'     => 0,
                'status'         => 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $this->logAudit(
                'created', 'admins', (string) $newAdminId,
                "Created admin: {$request->admin_name} ({$request->email})",
                null,
                ['admin_id' => $newAdminId, 'admin_name' => $request->admin_name, 'email' => $request->email, 'admin_type' => 0]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Admin created successfully!',
                'data'    => [
                    'admin_id'         => $newAdminId,
                    'admin_name'       => $request->admin_name,
                    'email'            => $request->email,
                    'admin_type'       => 'Admin',
                    'default_password' => $defaultPassword,
                ]
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Failed to create admin', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to create admin: ' . $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------------------
    //  List Admins
    // ---------------------------------------------------------------------------

    public function list_admin(Request $request)
    {
        if (!$this->checkSuperAdminAccess()) {
            abort(403, 'Unauthorized access - Super Admin only');
        }

        $admins = DB::table('admins')
            ->select('id', 'admin_name', 'email', 'admin_type', 'status', 'created_at', 'updated_at')
            ->orderBy('admin_type', 'desc')
            ->orderBy('admin_name', 'asc')
            ->get()
            ->map(function ($admin) {
                $admin->admin_type_name = $this->getAdminTypeName($admin->admin_type);
                $admin->can_edit        = ($admin->admin_type == 0);
                return $admin;
            });

        return view('admin.user_management.list_admin', [
            'scripts' => ['user_management/list_admin.js'],
            'admins'  => $admins,
        ]);
    }

    // ---------------------------------------------------------------------------
    //  Update Admin
    // ---------------------------------------------------------------------------

    public function update_admin(Request $request, $id)
    {
        if (!$this->checkSuperAdminAccess()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $request->validate([
            'admin_name' => 'required|string|max:100',
            'email'      => 'required|email|max:100|unique:admins,email,' . $id,
        ]);

        try {
            DB::beginTransaction();

            $admin = DB::table('admins')->where('id', $id)->first();

            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Admin not found'], 404);
            }

            if ($admin->admin_type != 0) {
                return response()->json(['success' => false, 'message' => 'Can only edit regular Admin accounts'], 422);
            }

            $oldValues = ['admin_name' => $admin->admin_name, 'email' => $admin->email];

            DB::table('admins')->where('id', $id)->update([
                'admin_name' => $request->admin_name,
                'email'      => $request->email,
                'updated_at' => now(),
            ]);

            $this->logAudit(
                'updated', 'admins', (string) $id,
                "Updated admin: {$request->admin_name} ({$request->email})",
                $oldValues,
                ['admin_name' => $request->admin_name, 'email' => $request->email]
            );

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Admin updated successfully!']);

        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Failed to update admin', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update admin: ' . $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------------------
    //  Reset Admin Password
    // ---------------------------------------------------------------------------

    public function reset_password(Request $request, $id)
    {
        if (!$this->checkSuperAdminAccess()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        try {
            DB::beginTransaction();

            $admin = DB::table('admins')->where('id', $id)->first();

            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Admin not found'], 404);
            }

            if ($admin->admin_type != 0) {
                return response()->json(['success' => false, 'message' => 'Can only reset password for regular Admin accounts'], 422);
            }

            $newPassword = $this->generateAdminPassword();

            DB::table('admins')->where('id', $id)->update([
                'admin_password' => Hash::make($newPassword),
                'updated_at'     => now(),
            ]);

            $this->logAudit(
                'updated', 'admins', (string) $id,
                "Reset password for admin: {$admin->admin_name} ({$admin->email})",
                null,
                ['password_reset' => true]
            );

            DB::commit();

            return response()->json([
                'success'      => true,
                'message'      => 'Password reset successfully!',
                'new_password' => $newPassword
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Failed to reset admin password', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to reset password: ' . $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------------------
    //  Toggle Admin Status (activate / deactivate)
    // ---------------------------------------------------------------------------

    public function toggle_status(Request $request, $id)
    {
        if (!$this->checkSuperAdminAccess()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 403);
        }

        $request->validate([
            'status' => 'required|in:0,1',
        ]);

        try {
            DB::beginTransaction();

            $admin = DB::table('admins')->where('id', $id)->first();

            if (!$admin) {
                return response()->json(['success' => false, 'message' => 'Admin not found'], 404);
            }

            if ($admin->admin_type != 0) {
                return response()->json(['success' => false, 'message' => 'Can only modify regular Admin accounts'], 422);
            }

            $newStatus  = (int) $request->status;
            $action     = $newStatus === 1 ? 'activated' : 'deactivated';
            $message    = 'Admin ' . $action . ' successfully!';

            DB::table('admins')->where('id', $id)->update([
                'status'     => $newStatus,
                'updated_at' => now(),
            ]);

            $this->logAudit(
                $action, 'admins', (string) $id,
                ucfirst($action) . " admin: {$admin->admin_name} ({$admin->email})",
                ['status' => $admin->status],
                ['status' => $newStatus]
            );

            DB::commit();

            return response()->json(['success' => true, 'message' => $message]);

        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Failed to toggle admin status', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update status: ' . $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------------------
    //  Helpers
    // ---------------------------------------------------------------------------

    private function generateAdminPassword()
    {
        return 'Admin' . strtoupper(Str::random(4)) . rand(100, 999);
    }

    private function getAdminTypeName($type)
    {
        return match ((int) $type) {
            1       => 'Super Admin',
            0       => 'Admin',
            default => 'Unknown',
        };
    }
}