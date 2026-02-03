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
     * @return void
     */
    protected function logAudit(
        string $action,
        string $module,
        ?string $recordId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ) {
        try {
            // Determine user type and identifier
            $userType = 'system';
            $userIdentifier = 'system';

            // Check Laravel Auth guards FIRST (this is what you're actually using)
            if (Auth::guard('admin')->check()) {
                $userType = 'admin';
                $admin = Auth::guard('admin')->user();
                $userIdentifier = $admin->email ?? $admin->admin_name ?? 'admin_' . ($admin->id ?? 'unknown');
            } 
            // Check teacher guard
            elseif (Auth::guard('teacher')->check()) {
                $userType = 'teacher';
                $teacher = Auth::guard('teacher')->user();
                $userIdentifier = $teacher->email ?? 'teacher_' . ($teacher->id ?? 'unknown');
            } 
            // Check student guard
            elseif (Auth::guard('student')->check()) {
                $userType = 'student';
                $student = Auth::guard('student')->user();
                $userIdentifier = $student->student_number ?? 'student_' . ($student->id ?? 'unknown');
            }
            // Check default guard (fallback)
            elseif (Auth::check()) {
                $userType = 'admin';
                $user = Auth::user();
                $userIdentifier = $user->email ?? $user->user_name ?? 'user_' . ($user->id ?? 'unknown');
            }

            // Prepare data for insertion
            $auditData = [
                'user_type' => $userType,
                'user_identifier' => $userIdentifier,
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
     * Format values for audit logging (remove sensitive data)
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
     * Log guardian action (for token-based access, not authentication)
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
        try {
            DB::table('audit_logs')->insert([
                'user_type' => 'guardian',
                'user_identifier' => $guardianEmail ?: 'guardian_unknown',
                'action' => $action,
                'module' => $module,
                'record_id' => $recordId,
                'description' => $description,
                'old_values' => null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Guardian action logging failed', [
                'error' => $e->getMessage(),
                'guardian' => $guardianEmail,
                'action' => $action
            ]);
        }
    }
}