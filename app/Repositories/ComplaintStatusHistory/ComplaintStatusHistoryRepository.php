<?php

namespace App\Repositories\ComplaintStatusHistory;

use App\Models\Complaint;
use App\Models\ComplaintStatusHistory;

class ComplaintStatusHistoryRepository implements ComplaintStatusHistoryRepositoryInterface
{
    public function record(
        Complaint $complaint,
        ?string $fromStatus,
        string $toStatus,
        int $changedBy,
        ?string $note = null
    ): ComplaintStatusHistory {
        return ComplaintStatusHistory::create([
            'complaint_id' => $complaint->id,
            'from_status'  => $fromStatus,
            'to_status'    => $toStatus,
            'changed_by'   => $changedBy,
            'note'         => $note,
            'changed_at'   => now(),
        ]);
    }
}
