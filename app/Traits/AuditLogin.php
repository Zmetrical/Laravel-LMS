<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait AuditLogin
{
    /**
     * Log a login event
     *
     * @param string $userType (admin, teacher, student, guardian)
     * @param string $userIdentifier
     * @param string|null $sessionId
     * @return int Login ID
     */
    protected function logLogin(
        string $userType,
        string $userIdentifier,
        ?string $sessionId = null
    ): int {
        return DB::table('audit_login')->insertGetId([
            'user_type' => $userType,
            'user_identifier' => $userIdentifier,
            'ip_address' => request()->ip(),
            'session_id' => $sessionId,
            'created_at' => now()
        ]);
    }

    /**
     * Log a logout event
     *
     * @param int $loginId
     */
    protected function logLogout(int $loginId): void
    {
        DB::table('audit_login')
            ->where('id', $loginId)
            ->update([
                'logout_at' => now()
            ]);
    }

    /**
     * Log a logout by session
     *
     * @param string $sessionId
     */
    protected function logLogoutBySession(string $sessionId): void
    {
        DB::table('audit_login')
            ->where('session_id', $sessionId)
            ->whereNull('logout_at')
            ->update([
                'logout_at' => now()
            ]);
    }
}