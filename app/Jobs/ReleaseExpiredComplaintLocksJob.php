<?php

namespace App\Jobs;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReleaseExpiredComplaintLocksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $now = now();

        $affected = Complaint::query()
            ->whereNotNull('locked_by')
            ->whereNotNull('lock_expires_at')
            ->where('lock_expires_at', '<', $now)
            ->update([
                'locked_by'       => null,
                'locked_at'       => null,
                'lock_expires_at' => null,
            ]);

        if ($affected > 0) {
            Log::info("Released {$affected} expired complaint locks at {$now}.");
        }
    }
}
