<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuditLogController extends Controller
{
    /**
     * Display admin audit logs page
     */
    public function adminIndex()
    {
        return view('admin.audit.admin_logs');
    }

    /**
     * Display teacher audit logs page
     */
    public function teacherIndex()
    {
        return view('admin.audit.teacher_logs');
    }

    /**
     * Display student/guardian audit logs page
     */
    public function studentIndex()
    {
        return view('admin.audit.student_logs');
    }

    /**
     * Display login audit logs page
     */
    public function loginIndex()
    {
        return view('admin.audit.login_logs');
    }

    /**
     * Get admin audit logs data (AJAX)
     */
    public function getAdminLogs(Request $request)
    {
        $query = DB::table('audit_logs')
            ->where('user_type', 'admin')
            ->select('*');

        // Apply filters
        if ($request->filled('search_value')) {
            $search = $request->search_value;
            $query->where(function($q) use ($search) {
                $q->where('user_identifier', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Get total records before pagination
        $totalRecords = $query->count();

        // Apply ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');
        
        $columns = ['created_at', 'user_identifier', 'action', 'module', 'record_id', 'description', 'ip_address'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';
        
        $query->orderBy($orderColumn, $orderDirection);

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);
        
        if ($length != -1) {
            $query->skip($start)->take($length);
        }

        $logs = $query->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => DB::table('audit_logs')->where('user_type', 'admin')->count(),
            'recordsFiltered' => $totalRecords,
            'data' => $logs
        ]);
    }

    /**
     * Get teacher audit logs data (AJAX)
     */
    public function getTeacherLogs(Request $request)
    {
        $query = DB::table('audit_logs')
            ->where('user_type', 'teacher')
            ->select('*');

        // Apply filters
        if ($request->filled('search_value')) {
            $search = $request->search_value;
            $query->where(function($q) use ($search) {
                $q->where('user_identifier', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Get total records before pagination
        $totalRecords = $query->count();

        // Apply ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');
        
        $columns = ['created_at', 'user_identifier', 'action', 'module', 'record_id', 'description', 'ip_address'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';
        
        $query->orderBy($orderColumn, $orderDirection);

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);
        
        if ($length != -1) {
            $query->skip($start)->take($length);
        }

        $logs = $query->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => DB::table('audit_logs')->where('user_type', 'teacher')->count(),
            'recordsFiltered' => $totalRecords,
            'data' => $logs
        ]);
    }

    /**
     * Get student/guardian audit logs data (AJAX)
     */
    public function getStudentLogs(Request $request)
    {
        $query = DB::table('audit_logs')
            ->whereIn('user_type', ['student', 'guardian'])
            ->select('*');

        // Apply filters
        if ($request->filled('user_type')) {
            $query->where('user_type', $request->user_type);
        }

        if ($request->filled('search_value')) {
            $search = $request->search_value;
            $query->where(function($q) use ($search) {
                $q->where('user_identifier', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Get total records before pagination
        $totalRecords = $query->count();

        // Apply ordering
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');
        
        $columns = ['created_at', 'user_type', 'user_identifier', 'action', 'module', 'description', 'ip_address'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';
        
        $query->orderBy($orderColumn, $orderDirection);

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);
        
        if ($length != -1) {
            $query->skip($start)->take($length);
        }

        $logs = $query->get();

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => DB::table('audit_logs')->whereIn('user_type', ['student', 'guardian'])->count(),
            'recordsFiltered' => $totalRecords,
            'data' => $logs
        ]);
    }

/**
 * Get login audit logs data (AJAX)
 */
public function getLoginLogs(Request $request)
{
    $sessionLifetime = config('session.lifetime', 120);
    
    $query = DB::table('audit_login')
        ->select([
            '*',
            DB::raw("CASE 
                WHEN logout_at IS NULL THEN
                    CASE 
                        WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < {$sessionLifetime} 
                        THEN 'active'
                        ELSE 'expired'
                    END
                ELSE 'logged_out'
            END as status"),
            DB::raw("CASE 
                WHEN logout_at IS NOT NULL 
                THEN TIMESTAMPDIFF(SECOND, created_at, logout_at)
                WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) >= {$sessionLifetime}
                THEN {$sessionLifetime} * 60
                ELSE TIMESTAMPDIFF(SECOND, created_at, NOW())
            END as duration_seconds")
        ]);

    // Apply filters
    if ($request->filled('user_type')) {
        $query->where('user_type', $request->user_type);
    }

    if ($request->filled('search_value')) {
        $search = $request->search_value;
        $query->where(function($q) use ($search) {
            $q->where('user_identifier', 'like', "%{$search}%")
              ->orWhere('ip_address', 'like', "%{$search}%");
        });
    }

    if ($request->filled('status')) {
        if ($request->status === 'active') {
            $query->whereNull('logout_at')
                  ->whereRaw("TIMESTAMPDIFF(MINUTE, created_at, NOW()) < {$sessionLifetime}");
        } else if ($request->status === 'logged_out') {
            $query->whereNotNull('logout_at');
        } else if ($request->status === 'expired') {
            $query->whereNull('logout_at')
                  ->whereRaw("TIMESTAMPDIFF(MINUTE, created_at, NOW()) >= {$sessionLifetime}");
        }
    }

    if ($request->filled('date_from')) {
        $query->whereDate('created_at', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
        $query->whereDate('created_at', '<=', $request->date_to);
    }

    $totalRecords = $query->count();

    // Apply ordering
    $orderColumnIndex = $request->input('order.0.column', 0);
    $orderDirection = $request->input('order.0.dir', 'desc');
    
    $columns = ['created_at', 'user_type', 'user_identifier', 'ip_address', 'duration_seconds', 'logout_at', 'status'];
    $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';
    
    $query->orderBy($orderColumn, $orderDirection);

    // Apply pagination
    $start = $request->input('start', 0);
    $length = $request->input('length', 25);
    
    if ($length != -1) {
        $query->skip($start)->take($length);
    }

    $logs = $query->get();

    return response()->json([
        'draw' => intval($request->input('draw')),
        'recordsTotal' => DB::table('audit_login')->count(),
        'recordsFiltered' => $totalRecords,
        'data' => $logs
    ]);
}

    /**
     * Get single audit log details
     */
    public function getLogDetails($id)
    {
        $log = DB::table('audit_logs')->where('id', $id)->first();
        
        if (!$log) {
            return response()->json(['error' => 'Log not found'], 404);
        }

        return response()->json($log);
    }

    /**
     * Get single login log details
     */
    public function getLoginDetails($id)
    {
        $log = DB::table('audit_login')->where('id', $id)->first();
        
        if (!$log) {
            return response()->json(['error' => 'Log not found'], 404);
        }

        return response()->json($log);
    }
}