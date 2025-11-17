<?php

namespace App\Repositories\ComplaintAttachment;

use App\Models\Complaint;
use App\Models\ComplaintAttachment;

class ComplaintAttachmentRepository implements ComplaintAttachmentRepositoryInterface
{
    public function createForComplaint(Complaint $complaint, array $attributes): ComplaintAttachment
    {
        $attributes['complaint_id'] = $complaint->id;

        return ComplaintAttachment::create($attributes);
    }
}
