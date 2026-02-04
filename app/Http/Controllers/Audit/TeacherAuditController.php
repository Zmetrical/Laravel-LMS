<?php

namespace App\Http\Controllers\Audit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TeacherAuditController extends Controller
{
    /**
     * Render the teacher's own activity-log page.
     */
    public function index()
    {
        return view('teacher.audit.teacher_my_log');
    }

    /**
     * Server-side DataTable endpoint — returns only the
     * authenticated teacher's own audit rows.
     */
    public function getMyLogs(Request $request)
    {
        $teacher = Auth::guard('teacher')->user();

        // Base query: locked to this teacher's identifier
        $query = DB::table('audit_logs')
            ->where('user_type', 'teacher')
            ->where('user_identifier', $teacher->email);

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

        $totalFiltered = $query->count();

        $columns = [
            'created_at',      
            'action',          
            'module',          
            'record_id',       
            'description',     
            'ip_address',      
        ];

        $orderIndex    = $request->input('order.0.column', 0);
        $orderDir      = in_array($request->input('order.0.dir'), ['asc', 'desc'])
                            ? $request->input('order.0.dir')
                            : 'desc';
        $orderColumn   = $columns[$orderIndex] ?? 'created_at';

        $query->orderBy($orderColumn, $orderDir);

        // ── Paging ───────────────────────────────────────────────────────────
        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);

        if ($length !== -1) {
            $query->skip($start)->take($length);
        }

        $logs = $query->get();

        // Grand total (no filters) for this teacher
        $totalRecords = DB::table('audit_logs')
            ->where('user_type', 'teacher')
            ->where('user_identifier', $teacher->email)
            ->count();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $totalRecords,
            'recordsFiltered' => $totalFiltered,
            'data'            => $logs,
        ]);
    }

    /**
     * Return a single audit-log row.
     * Still enforces that it belongs to the current teacher.
     */
    public function getMyLogDetail(int $id)
    {
        $log = DB::table('audit_logs')
            ->where('id', $id)
            ->where('user_type', 'teacher')
            ->where('user_identifier', $teacher->email)
            ->first();

        if (!$log) {
            return response()->json(['error' => 'Log not found'], 404);
        }

        return response()->json($log);
    }
}