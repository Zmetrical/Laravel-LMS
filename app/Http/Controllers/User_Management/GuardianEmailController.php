<?php

namespace App\Http\Controllers\User_Management;

use App\Http\Controllers\MainController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Traits\AuditLogger;
use Exception;

class GuardianEmailController extends MainController
{
    use AuditLogger;

    /**
     * Send verification email to guardian
     * Called automatically after student creation with new guardian
     */
    public function sendVerificationEmail($guardianId, $isAutomatic = false)
    {
        try {
            $guardian = DB::table('guardians')
                ->where('id', $guardianId)
                ->first();

            if (!$guardian) {
                \Log::warning('Guardian not found for verification email', ['guardian_id' => $guardianId]);
                return [
                    'success' => false,
                    'message' => 'Guardian not found'
                ];
            }

            // Check if already verified
            if ($guardian->email_verified_at) {
                return [
                    'success' => false,
                    'message' => 'Email already verified on ' . date('M d, Y', strtotime($guardian->email_verified_at)),
                    'already_verified' => true
                ];
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
                ->select(
                    's.first_name',
                    's.middle_name',
                    's.last_name',
                    's.student_number'
                )
                ->get();

            // Send verification email
            Mail::send('guardian.verification_email', [
                'guardian_name' => $guardian->first_name . ' ' . $guardian->last_name,
                'verification_url' => $verificationUrl,
                'students' => $students,
                'is_automatic' => $isAutomatic
            ], function ($message) use ($guardian) {
                $message->to($guardian->email)
                    ->subject('Trinity University - Verify Your Email Address');
            });

            // Audit log
            $this->logAudit(
                'email_sent',
                'guardians',
                (string)$guardian->id,
                ($isAutomatic ? 'Auto-sent' : 'Manually sent') . " verification email to guardian: {$guardian->email}",
                null,
                [
                    'guardian_id' => $guardian->id,
                    'email' => $guardian->email,
                    'student_count' => $students->count(),
                    'is_automatic' => $isAutomatic
                ]
            );

            \Log::info('Verification email sent', [
                'guardian_id' => $guardian->id,
                'email' => $guardian->email,
                'is_automatic' => $isAutomatic
            ]);

            return [
                'success' => true,
                'message' => 'Verification email sent to ' . $guardian->email,
                'verification_url' => $verificationUrl,
                'guardian_email' => $guardian->email
            ];

        } catch (Exception $e) {
            \Log::error('Failed to send verification email', [
                'guardian_id' => $guardianId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ];
        }
    }

public function sendAccessEmail($guardianId)
{
    try {
        $guardian = DB::table('guardians')
            ->where('id', $guardianId)
            ->first();

        if (!$guardian) {
            return [
                'success' => false,
                'message' => 'Guardian not found'
            ];
        }

        // Check if email is verified
        if (!$guardian->email_verified_at) {
            return [
                'success' => false,
                'message' => 'Guardian email is not verified. Please send verification email first.',
                'needs_verification' => true
            ];
        }

        $accessUrl = route('guardian.access', ['token' => $guardian->access_token]);
        $studentPortalUrl = route('student.login');

        // Get students with passwords
        $students = DB::table('guardian_students as gs')
            ->join('students as s', 'gs.student_number', '=', 's.student_number')
            ->leftJoin('student_password_matrix as spm', 's.student_number', '=', 'spm.student_number')
            ->where('gs.guardian_id', $guardian->id)
            ->select(
                's.first_name',
                's.middle_name',
                's.last_name',
                's.student_number',
                'spm.plain_password'
            )
            ->get();

        if ($students->count() === 0) {
            return [
                'success' => false,
                'message' => 'No students linked to this guardian'
            ];
        }

        Mail::send('guardian.access_email', [
            'guardian_name' => $guardian->first_name . ' ' . $guardian->last_name,
            'access_url' => $accessUrl,
            'student_portal_url' => $studentPortalUrl,
            'students' => $students
        ], function ($message) use ($guardian) {
            $message->to($guardian->email)
                ->subject('Trinity University - Guardian Portal Access');
        });

        // Audit log
        $this->logAudit(
            'email_sent',
            'guardians',
            (string)$guardian->id,
            "Sent access email to guardian: {$guardian->email}",
            null,
            [
                'guardian_id' => $guardian->id,
                'email' => $guardian->email,
                'student_count' => $students->count()
            ]
        );

        \Log::info('Access email sent', [
            'guardian_id' => $guardian->id,
            'email' => $guardian->email
        ]);

        return [
            'success' => true,
            'message' => 'Guardian access email sent successfully to ' . $guardian->email,
            'access_url' => $accessUrl,
            'guardian_email' => $guardian->email
        ];

    } catch (Exception $e) {
        \Log::error('Failed to send access email', [
            'guardian_id' => $guardianId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return [
            'success' => false,
            'message' => 'Failed to send email: ' . $e->getMessage()
        ];
    }
}

    public function verifyEmail($token)
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

        // Audit log
        $this->logAudit(
            'email_verified',
            'guardians',
            (string)$guardian->id,
            'Guardian email verified: ' . $guardian->email,
            null,
            [
                'guardian_id' => $guardian->id,
                'email' => $guardian->email,
                'verified_at' => now()->toDateTimeString()
            ]
        );

        \Log::info('Guardian email verified', [
            'guardian_id' => $guardian->id,
            'email' => $guardian->email
        ]);

        // **ADD THIS: Automatically send access email after verification**
        $accessEmailResult = $this->sendAccessEmail($guardian->id);
        
        $accessEmailSent = $accessEmailResult['success'] ?? false;
        
        if (!$accessEmailSent) {
            \Log::warning('Failed to auto-send access email after verification', [
                'guardian_id' => $guardian->id,
                'error' => $accessEmailResult['message'] ?? 'Unknown error'
            ]);
        }

        return view('guardian.verification_result', [
            'success' => true,
            'message' => 'Email verified successfully!',
            'guardian_name' => $guardian->first_name . ' ' . $guardian->last_name,
            'access_url' => route('guardian.access', ['token' => $guardian->access_token]),
            'access_email_sent' => $accessEmailSent
        ]);
    }

/**
 * Resend verification email to guardian
 */
public function resendGuardianVerification($studentId, $guardianId)
{
    try {
        // Verify guardian is linked to this student
        $guardianStudent = DB::table('guardian_students as gs')
            ->join('guardians as g', 'gs.guardian_id', '=', 'g.id')
            ->join('students as s', 'gs.student_number', '=', 's.student_number')
            ->where('s.id', $studentId)
            ->where('gs.guardian_id', $guardianId)
            ->where('g.is_active', 1)
            ->first();

        if (!$guardianStudent) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found or not linked to this student'
            ], 404);
        }

        // Use GuardianEmailController to send verification
        $emailController = new \App\Http\Controllers\User_Management\GuardianEmailController();
        $result = $emailController->sendVerificationEmail($guardianId, false);

        return response()->json($result, $result['success'] ? 200 : 400);

    } catch (Exception $e) {
        \Log::error('Failed to resend verification email', [
            'student_id' => $studentId,
            'guardian_id' => $guardianId,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to resend verification email'
        ], 500);
    }
}

/**
 * Resend access email to guardian
 */
public function resendGuardianAccess($studentId, $guardianId)
{
    try {
        // Verify guardian is linked to this student
        $guardianStudent = DB::table('guardian_students as gs')
            ->join('guardians as g', 'gs.guardian_id', '=', 'g.id')
            ->join('students as s', 'gs.student_number', '=', 's.student_number')
            ->where('s.id', $studentId)
            ->where('gs.guardian_id', $guardianId)
            ->where('g.is_active', 1)
            ->first();

        if (!$guardianStudent) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found or not linked to this student'
            ], 404);
        }

        // Use GuardianEmailController to send access email
        $emailController = new \App\Http\Controllers\User_Management\GuardianEmailController();
        $result = $emailController->sendAccessEmail($guardianId);

        return response()->json($result, $result['success'] ? 200 : 400);

    } catch (Exception $e) {
        \Log::error('Failed to resend access email', [
            'student_id' => $studentId,
            'guardian_id' => $guardianId,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to resend access email'
        ], 500);
    }
}

/**
 * Change guardian email address
 */
public function changeGuardianEmail(Request $request, $studentId, $guardianId)
{
    try {
        $validated = $request->validate([
            'new_email' => 'required|email|max:255'
        ]);

        // Verify guardian is linked to this student
        $guardianStudent = DB::table('guardian_students as gs')
            ->join('guardians as g', 'gs.guardian_id', '=', 'g.id')
            ->join('students as s', 'gs.student_number', '=', 's.student_number')
            ->where('s.id', $studentId)
            ->where('gs.guardian_id', $guardianId)
            ->where('g.is_active', 1)
            ->select('g.*', 's.student_number')
            ->first();

        if (!$guardianStudent) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found or not linked to this student'
            ], 404);
        }

        // Check if email is already in use by another guardian
        $emailExists = DB::table('guardians')
            ->where('email', $validated['new_email'])
            ->where('id', '!=', $guardianId)
            ->exists();

        if ($emailExists) {
            return response()->json([
                'success' => false,
                'message' => 'This email is already in use by another guardian'
            ], 422);
        }

        DB::beginTransaction();

        // Store old email for audit
        $oldEmail = $guardianStudent->email;

        // Update guardian email and reset verification
        DB::table('guardians')
            ->where('id', $guardianId)
            ->update([
                'email' => $validated['new_email'],
                'email_verified_at' => null,
                'verification_token' => null,
                'updated_at' => now()
            ]);

        // Audit log
        $this->logAudit(
            'updated',
            'guardians',
            (string)$guardianId,
            "Changed guardian email for student {$guardianStudent->student_number}",
            ['email' => $oldEmail],
            ['email' => $validated['new_email']]
        );

        DB::commit();

        // Automatically send verification email to new address
        $emailController = new \App\Http\Controllers\User_Management\GuardianEmailController();
        $verificationResult = $emailController->sendVerificationEmail($guardianId, false);

        return response()->json([
            'success' => true,
            'message' => 'Email updated successfully. Verification email sent to new address.',
            'verification_sent' => $verificationResult['success'] ?? false
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);

    } catch (Exception $e) {
        DB::rollBack();
        \Log::error('Failed to change guardian email', [
            'student_id' => $studentId,
            'guardian_id' => $guardianId,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to update email: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Get guardian verification status
 */
public function getGuardianStatus($studentId, $guardianId)
{
    try {
        $guardian = DB::table('guardian_students as gs')
            ->join('guardians as g', 'gs.guardian_id', '=', 'g.id')
            ->join('students as s', 'gs.student_number', '=', 's.student_number')
            ->where('s.id', $studentId)
            ->where('gs.guardian_id', $guardianId)
            ->select(
                'g.id',
                'g.email',
                'g.email_verified_at',
                'g.verification_sent_at',
                'g.is_active'
            )
            ->first();

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'is_verified' => !is_null($guardian->email_verified_at),
            'email' => $guardian->email,
            'verified_at' => $guardian->email_verified_at,
            'verification_sent_at' => $guardian->verification_sent_at
        ]);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to get guardian status'
        ], 500);
    }
}

    /**
     * Manual send verification (for admin interface)
     */
    public function manualSendVerification(Request $request)
    {
        $request->validate([
            'guardian_id' => 'required|exists:guardians,id'
        ]);

        $result = $this->sendVerificationEmail($request->guardian_id, false);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Manual send access email (for admin interface)
     */
    public function manualSendAccessEmail(Request $request)
    {
        $request->validate([
            'guardian_id' => 'required|exists:guardians,id'
        ]);

        $result = $this->sendAccessEmail($request->guardian_id);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get all guardians with their verification status
     */
    public function getGuardians()
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
            ->groupBy(
                'g.id',
                'g.email',
                'g.first_name',
                'g.last_name',
                'g.access_token',
                'g.is_active',
                'g.email_verified_at',
                'g.verification_sent_at',
                'g.created_at'
            )
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

    /**
     * Get students linked to a guardian
     */
    public function getGuardianStudents($id)
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
                $student->full_name = trim($student->first_name . ' ' . 
                    ($student->middle_name ? $student->middle_name . ' ' : '') . 
                    $student->last_name);
                return $student;
            });

        return response()->json($students);
    }

    /**
     * Batch send verification emails to unverified guardians
     */
    public function batchSendVerifications(Request $request)
    {
        $request->validate([
            'guardian_ids' => 'required|array',
            'guardian_ids.*' => 'exists:guardians,id'
        ]);

        $results = [
            'success' => [],
            'failed' => [],
            'already_verified' => []
        ];

        foreach ($request->guardian_ids as $guardianId) {
            $result = $this->sendVerificationEmail($guardianId, false);
            
            if ($result['success']) {
                $results['success'][] = $guardianId;
            } elseif (isset($result['already_verified'])) {
                $results['already_verified'][] = $guardianId;
            } else {
                $results['failed'][] = [
                    'guardian_id' => $guardianId,
                    'message' => $result['message']
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
            'summary' => [
                'sent' => count($results['success']),
                'already_verified' => count($results['already_verified']),
                'failed' => count($results['failed']),
                'total' => count($request->guardian_ids)
            ]
        ]);
    }
}