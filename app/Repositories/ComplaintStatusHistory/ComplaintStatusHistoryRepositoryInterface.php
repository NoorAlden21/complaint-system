<?php

namespace App\Repositories\ComplaintStatusHistory;

use App\Models\Complaint;
use App\Models\ComplaintStatusHistory;

interface ComplaintStatusHistoryRepositoryInterface
{
    public function record(
        Complaint $complaint,
        ?string $fromStatus,
        string $toStatus,
        int $changedBy,
        ?string $note = null
    ): ComplaintStatusHistory;
}
