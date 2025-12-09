<?php

namespace App\Repositories\ComplaintVersion;

use App\Models\Complaint;
use App\Models\ComplaintVersion;

class ComplaintVersionRepository implements ComplaintVersionRepositoryInterface
{
    public function findById(int $id): ?ComplaintVersion
    {
        return ComplaintVersion::query()
            ->with(['category', 'department', 'region', 'attachments', 'versions'])
            ->find($id);
    }

    public function record(
        Complaint $complaint,
        int $version_number,
        int $changedBy,
        ?string $note = null
    ): ComplaintVersion {
        return ComplaintVersion::create([
            'complaint_id' => $complaint->id,
            'version_number'  => $version_number,
            'title' => $complaint->title,
            'description' => $complaint->description,
            'status' => $complaint->status,
            'priority' => $complaint->priority,
            'category_id' => $complaint->category_id,
            'department_id' => $complaint->department_id,
            'region_id' => $complaint->region_id,
            'changed_by'   => $changedBy,
            'note'         => $note,
        ]);
    }
}
