<?php

namespace App\Repositories\ComplaintAttachment;

use App\Models\Complaint;
use App\Models\ComplaintAttachment;

interface ComplaintAttachmentRepositoryInterface
{
    public function createForComplaint(Complaint $complaint, array $attributes): ComplaintAttachment;
}
