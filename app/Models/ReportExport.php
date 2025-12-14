<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportExport extends Model
{
    protected $fillable = [
        'type',
        'format',
        'filters',
        'requested_by',
        'status',
        'queued_at',
        'started_at',
        'finished_at',
        'file_disk',
        'file_path',
        'file_size',
        'error_message',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'filters'     => 'array',
        'queued_at'   => 'datetime',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
