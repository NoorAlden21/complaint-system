<?php

namespace App\Repositories\ComplaintVersion;

use App\Models\Complaint;
use App\Models\ComplaintVersion;

interface ComplaintVersionRepositoryInterface
{
    public function record(
        Complaint $complaint,
        int $version_number,
        int $changedBy,
        ?string $note = null
    ): ComplaintVersion;
}
