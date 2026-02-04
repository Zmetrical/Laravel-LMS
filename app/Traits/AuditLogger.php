<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait AuditLogger
{
    /**
     * Log an audit entry
     *
     * @param string $action - created, updated, deleted, viewed, exported, etc
     * @param string $module - students, grades, classes, quizzes, etc
     * @param string|null $recordId - ID of affected record
     * @param string|null $description - Human readable description
     * @param array|null $oldValues - Previous values for updates
     * @param array|null $newValues - New values for creates/updates
     * @param string|null $userType - Optional: explicit user type (admin, teacher, student, guardian, system)
     * @param string|null $userIdentifier - Optional: explicit user identifier
     * @return void
     */
    protected function logAudit(
        string $action,
        string $module,
        ?string $recordId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $userType = null,
        ?string $userIdentifier = null
    ) {
        try {
            // Use explicit parameters if provided, otherwise auto-detect
            if ($userType && $userIdentifier) {
                $finalUserType = $userType;
                $finalUserIdentifier = $userIdentifier;
            } else {
                // Auto-detect from Laravel Auth guards
                $finalUserType = 'system';
                $finalUserIdentifier = 'system';

                // Check Laravel Auth guards in order
                if (Auth::guard('teacher')->check()) {
                    $finalUserType = 'teacher';
                    $teacher = Auth::guard('teacher')->user();
                    $finalUserIdentifier = $teacher->email ?? 'teacher_' . ($teacher->id ?? 'unknown');
                } 
                elseif (Auth::guard('admin')->check()) {
                    $finalUserType = 'admin';
                    $admin = Auth::guard('admin')->user();
                    $finalUserIdentifier = $admin->email ?? $admin->admin_name ?? 'admin_' . ($admin->id ?? 'unknown');
                } 
                elseif (Auth::guard('student')->check()) {
                    $finalUserType = 'student';
                    $student = Auth::guard('student')->user();
                    $finalUserIdentifier = $student->student_number ?? 'student_' . ($student->id ?? 'unknown');
                }
                elseif (Auth::check()) {
                    $finalUserType = 'admin';
                    $user = Auth::user();
                    $finalUserIdentifier = $user->email ?? $user->user_name ?? 'user_' . ($user->id ?? 'unknown');
                }
            }

            // Validate user type
            $validUserTypes = ['admin', 'teacher', 'student', 'guardian', 'system'];
            if (!in_array($finalUserType, $validUserTypes)) {
                Log::warning('Invalid user type provided to audit log', [
                    'provided_type' => $finalUserType,
                    'action' => $action,
                    'module' => $module
                ]);
                $finalUserType = 'system';
            }

            // Prepare data for insertion
            $auditData = [
                'user_type' => $finalUserType,
                'user_identifier' => $finalUserIdentifier,
                'action' => $action,
                'module' => $module,
                'record_id' => $recordId,
                'description' => $description,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ];

            // Insert the audit log
            DB::table('audit_logs')->insert($auditData);

        } catch (\Exception $e) {
            // Log error but don't break the main operation
            Log::error('Audit logging failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'action' => $action,
                'module' => $module,
                'record_id' => $recordId,
            ]);
            // DO NOT THROW - audit should never break main functionality
        }
    }

    /**
     * Format values for audit logging 
     *
     * @param array $values
     * @param array $sensitiveFields
     * @return array
     */
    protected function formatAuditValues(array $values, array $sensitiveFields = ['password', 'token', 'secret', 'student_password', 'passcode']): array
    {
        $formatted = $values;
        foreach ($sensitiveFields as $field) {
            if (isset($formatted[$field])) {
                $formatted[$field] = '[REDACTED]';
            }
        }
        return $formatted;
    }

    /**
     * Log guardian action 
     * 
     * @param string $guardianEmail
     * @param string $action
     * @param string $module
     * @param string|null $recordId
     * @param string|null $description
     * @param array|null $newValues
     * @return void
     */
    protected function logGuardianAction(
        string $guardianEmail,
        string $action,
        string $module,
        ?string $recordId = null,
        ?string $description = null,
        ?array $newValues = null
    ) {
        // Use the main logAudit method with explicit guardian type
        $this->logAudit(
            $action,
            $module,
            $recordId,
            $description,
            null,
            $newValues,
            'guardian',
            $guardianEmail ?: 'guardian_unknown'
        );
    }
}