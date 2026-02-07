<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait AuditLogin
{
    /**
     * Log a login event
     * Automatically closes any existing active sessions for this user
     */
    protected function logLogin(
        string $userType,
        string $userIdentifier,
        ?string $sessionId = null
    ): int {
        // Close any existing active sessions (fail-safe for improper logouts)
        $this->closeExistingSessions($userType, $userIdentifier);
        
        return DB::table('audit_login')->insertGetId([
            'user_type' => $userType,
            'user_identifier' => $userIdentifier,
            'ip_address' => request()->ip(),
            'session_id' => $sessionId,
            'created_at' => now()
        ]);
    }

    /**
     * Close existing active sessions for a user
     * This is the main fail-safe for users who didn't logout properly
     */
    protected function closeExistingSessions(string $userType, string $userIdentifier): void
    {
        $sessionLifetime = config('session.lifetime', 120);
        
        DB::table('audit_login')
            ->where('user_type', $userType)
            ->where('user_identifier', $userIdentifier)
            ->whereNull('logout_at')
            ->update([
                'logout_at' => DB::raw('LEAST(
                    NOW(), 
                    DATE_ADD(created_at, INTERVAL ' . $sessionLifetime . ' MINUTE)
                )')
            ]);
    }

    /**
     * Log a logout event
     */
    protected function logLogout(int $loginId): void
    {
        DB::table('audit_login')
            ->where('id', $loginId)
            ->whereNull('logout_at')
            ->update([
                'logout_at' => now()
            ]);
    }

    /**
     * Log a logout by session ID
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

    /**
     * Mark expired sessions (run via scheduled task)
     * Secondary fail-safe for sessions that weren't closed
     */
    public function markExpiredSessions(): int
    {
        $sessionLifetime = config('session.lifetime', 120);
        
        $updated = DB::table('audit_login')
            ->whereNull('logout_at')
            ->where('created_at', '<', now()->subMinutes($sessionLifetime))
            ->update([
                'logout_at' => DB::raw('DATE_ADD(created_at, INTERVAL ' . $sessionLifetime . ' MINUTE)')
            ]);
            
        return $updated;
    }

    /**
     * Get session status for display
     */
    protected function getSessionStatus($login): string
    {
        if (is_null($login->logout_at)) {
            $sessionLifetime = config('session.lifetime', 120);
            $expiryTime = Carbon::parse($login->created_at)->addMinutes($sessionLifetime);
            
            if (now()->lessThan($expiryTime)) {
                return 'active';
            }
            return 'expired';
        }
        
        return 'logged_out';
    }

    /**
     * Get formatted session duration
     */
    protected function getSessionDuration($login): ?string
    {
        if (is_null($login->logout_at)) {
            $sessionLifetime = config('session.lifetime', 120);
            $expiryTime = Carbon::parse($login->created_at)->addMinutes($sessionLifetime);
            $logoutTime = now()->lessThan($expiryTime) ? null : $expiryTime;
        } else {
            $logoutTime = Carbon::parse($login->logout_at);
        }

        if ($logoutTime) {
            $loginTime = Carbon::parse($login->created_at);
            return $loginTime->diffForHumans($logoutTime, true);
        }

        return null;
    }

    /**
     * Get active sessions for a user
     */
    protected function getActiveSessions(string $userType, string $userIdentifier)
    {
        $sessionLifetime = config('session.lifetime', 120);
        
        return DB::table('audit_login')
            ->where('user_type', $userType)
            ->where('user_identifier', $userIdentifier)
            ->whereNull('logout_at')
            ->where('created_at', '>=', now()->subMinutes($sessionLifetime))
            ->get();
    }
}