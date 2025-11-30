<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintNote extends Model
{
    protected $fillable = [
        'complaint_id',
        'complaint_version_id',
        'created_by',
        'type',
        'is_internal',
        'message',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function version()
    {
        return $this->belongsTo(ComplaintVersion::class, 'complaint_version_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
