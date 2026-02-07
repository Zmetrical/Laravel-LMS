<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Traits\AuditLogin;

class MarkExpiredSessions extends Command
{
    use AuditLogin;

    protected $signature = 'audit:mark-expired-sessions';
    protected $description = 'Mark audit login sessions as expired based on session lifetime';

    public function handle()
    {
        $updated = $this->markExpiredSessions();
        
        $this->info("Marked {$updated} expired sessions.");
        
        return Command::SUCCESS;
    }
}