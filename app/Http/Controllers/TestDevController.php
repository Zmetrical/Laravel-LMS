<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TestDevController extends Controller
{
    public function index()
    {
        $data = [
            'scripts' => ['testdev/index.js']
        ];
        
        return view('testdev.index', $data);
    }

    public function send_guardian_email(Request $request)
    {
        $request->validate([
            'guardian_id' => 'required|exists:guardians,id'
        ]);

        try {
            // Get guardian details
            $guardian = DB::table('guardians')
                ->where('id', $request->guardian_id)
                ->first();

            if (!$guardian) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guardian not found'
                ], 404);
            }

            // Generate access link
            $accessUrl = route('guardian.access', ['token' => $guardian->access_token]);

            // Get linked students
            $students = DB::table('guardian_students as gs')
                ->join('students as s', 'gs.student_number', '=', 's.student_number')
                ->where('gs.guardian_id', $guardian->id)
                ->select('s.first_name', 's.last_name', 's.student_number')
                ->get();

            if ($students->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No students linked to this guardian'
                ], 400);
            }

            // Send email
            Mail::send('guardian.guardian_access', [
                'guardian_name' => $guardian->first_name . ' ' . $guardian->last_name,
                'access_url' => $accessUrl,
                'students' => $students
            ], function ($message) use ($guardian) {
                $message->to($guardian->email)
                    ->subject('Trinity University - Guardian Portal Access');
            });

            return response()->json([
                'success' => true,
                'message' => 'Guardian access email sent successfully to ' . $guardian->email,
                'access_url' => $accessUrl,
                'guardian_email' => $guardian->email
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function get_students()
    {
        // Not needed anymore since we're selecting from existing guardians
        return response()->json([]);
    }

    public function get_guardians()
    {
        $guardians = DB::table('guardians as g')
            ->leftJoin('guardian_students as gs', 'g.id', '=', 'gs.guardian_id')
            ->select(
                'g.id',
                'g.email',
                'g.first_name',
                'g.last_name',
                'g.access_token',
                'g.is_active',
                'g.created_at',
                DB::raw('COUNT(gs.student_number) as student_count')
            )
            ->groupBy('g.id', 'g.email', 'g.first_name', 'g.last_name', 'g.access_token', 'g.is_active', 'g.created_at')
            ->orderBy('g.created_at', 'desc')
            ->get()
            ->map(function($guardian) {
                $guardian->access_url = route('guardian.access', ['token' => $guardian->access_token]);
                return $guardian;
            });

        return response()->json($guardians);
    }

    public function get_guardian_students($id)
    {
        $students = DB::table('guardian_students as gs')
            ->join('students as s', 'gs.student_number', '=', 's.student_number')
            ->where('gs.guardian_id', $id)
            ->select(
                's.student_number',
                's.first_name',
                's.middle_name',
                's.last_name'
            )
            ->get()
            ->map(function($student) {
                $student->full_name = trim($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name);
                return $student;
            });

        return response()->json($students);
    }


    public function toggle_guardian_status($id)
    {
        try {
            $guardian = DB::table('guardians')->where('id', $id)->first();
            
            $newStatus = $guardian->is_active ? 0 : 1;
            
            DB::table('guardians')
                ->where('id', $id)
                ->update(['is_active' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => 'Guardian status updated',
                'new_status' => $newStatus
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}