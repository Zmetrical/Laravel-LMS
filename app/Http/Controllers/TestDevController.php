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

    public function send_verification_email(Request $request)
    {
        $request->validate([
            'guardian_id' => 'required|exists:guardians,id'
        ]);

        try {
            $guardian = DB::table('guardians')
                ->where('id', $request->guardian_id)
                ->first();

            if (!$guardian) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guardian not found'
                ], 404);
            }

            // Check if already verified
            if ($guardian->email_verified_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already verified on ' . date('M d, Y', strtotime($guardian->email_verified_at))
                ], 400);
            }

            // Generate verification token
            $verificationToken = Str::random(64);
            
            // Update guardian with verification token
            DB::table('guardians')
                ->where('id', $guardian->id)
                ->update([
                    'verification_token' => $verificationToken,
                    'verification_sent_at' => now(),
                    'updated_at' => now()
                ]);

            $verificationUrl = route('guardian.verify', ['token' => $verificationToken]);

            // Get linked students
            $students = DB::table('guardian_students as gs')
                ->join('students as s', 'gs.student_number', '=', 's.student_number')
                ->where('gs.guardian_id', $guardian->id)
                ->select('s.first_name', 's.last_name', 's.student_number')
                ->get();

            // Send verification email
            Mail::send('guardian.verification_email', [
                'guardian_name' => $guardian->first_name . ' ' . $guardian->last_name,
                'verification_url' => $verificationUrl,
                'students' => $students
            ], function ($message) use ($guardian) {
                $message->to($guardian->email)
                    ->subject('Trinity University - Verify Your Email Address');
            });

            return response()->json([
                'success' => true,
                'message' => 'Verification email sent to ' . $guardian->email,
                'verification_url' => $verificationUrl
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function verify_email($token)
    {
        $guardian = DB::table('guardians')
            ->where('verification_token', $token)
            ->whereNull('email_verified_at')
            ->first();

        if (!$guardian) {
            return view('guardian.verification_result', [
                'success' => false,
                'message' => 'Invalid or expired verification link.'
            ]);
        }

        // Update guardian as verified
        DB::table('guardians')
            ->where('id', $guardian->id)
            ->update([
                'email_verified_at' => now(),
                'verification_token' => null,
                'updated_at' => now()
            ]);

        // Log verification
        DB::table('audit_logs')->insert([
            'user_type' => 'guardian',
            'user_identifier' => $guardian->email,
            'action' => 'email_verified',
            'module' => 'guardians',
            'record_id' => $guardian->id,
            'description' => 'Guardian email verified: ' . $guardian->email,
            'ip_address' => request()->ip(),
            'created_at' => now()
        ]);

        // Automatically send access email after successful verification
        try {
            $accessUrl = route('guardian.access', ['token' => $guardian->access_token]);

            $students = DB::table('guardian_students as gs')
                ->join('students as s', 'gs.student_number', '=', 's.student_number')
                ->where('gs.guardian_id', $guardian->id)
                ->select('s.first_name', 's.last_name', 's.student_number')
                ->get();

            // Send access email automatically
            Mail::send('guardian.access_email', [
                'guardian_name' => $guardian->first_name . ' ' . $guardian->last_name,
                'access_url' => $accessUrl,
                'students' => $students
            ], function ($message) use ($guardian) {
                $message->to($guardian->email)
                    ->subject('Trinity University - Guardian Portal Access');
            });

            // Log access email sent
            DB::table('audit_logs')->insert([
                'user_type' => 'guardian',
                'user_identifier' => $guardian->email,
                'action' => 'access_email_sent',
                'module' => 'guardians',
                'record_id' => $guardian->id,
                'description' => 'Guardian portal access email sent after verification: ' . $guardian->email,
                'ip_address' => request()->ip(),
                'created_at' => now()
            ]);

        } catch (\Exception $e) {
            // Log error but don't fail the verification
            DB::table('audit_logs')->insert([
                'user_type' => 'system',
                'user_identifier' => 'auto_email',
                'action' => 'error',
                'module' => 'guardians',
                'record_id' => $guardian->id,
                'description' => 'Failed to auto-send access email after verification: ' . $e->getMessage(),
                'ip_address' => request()->ip(),
                'created_at' => now()
            ]);
        }

        return view('guardian.verification_result', [
            'success' => true,
            'message' => 'Email verified successfully!',
            'guardian_name' => $guardian->first_name . ' ' . $guardian->last_name,
            'access_url' => route('guardian.access', ['token' => $guardian->access_token])
        ]);
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
                'g.email_verified_at',
                'g.verification_sent_at',
                'g.created_at',
                DB::raw('COUNT(gs.student_number) as student_count')
            )
            ->groupBy('g.id', 'g.email', 'g.first_name', 'g.last_name', 'g.access_token', 
                      'g.is_active', 'g.email_verified_at', 'g.verification_sent_at', 'g.created_at')
            ->orderBy('g.created_at', 'desc')
            ->get()
            ->map(function($guardian) {
                $guardian->access_url = route('guardian.access', ['token' => $guardian->access_token]);
                $guardian->is_verified = !is_null($guardian->email_verified_at);
                $guardian->verification_status = $guardian->is_verified ? 'verified' : 'unverified';
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

    public function resend_verification(Request $request)
    {
        $request->validate([
            'guardian_id' => 'required|exists:guardians,id'
        ]);

        return $this->send_verification_email($request);
    }
}