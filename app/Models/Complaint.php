<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference_number',
        'title',
        'description',
        'status',
        'priority',
        'category_id',
        'department_id',
        'region_id',
        'created_by',
        'locked_by',
        'locked_at',
        'lock_expires_at',
        'sla_due_at',
        'resolved_at',
        'closed_at',
        'resolution_summary',
        'row_version',
    ];

    protected $casts = [
        'sla_due_at'   => 'datetime',
        'resolved_at'  => 'datetime',
        'closed_at'    => 'datetime',
        'locked_at'    => 'datetime',
        'lock_expires_at' => 'datetime',
        'row_version'      => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(ComplaintCategory::class, 'category_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function locker()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function versions()
    {
        return $this->hasMany(ComplaintVersion::class);
    }

    public function notes()
    {
        return $this->hasMany(ComplaintNote::class);
    }

    public function attachments()
    {
        return $this->hasMany(ComplaintAttachment::class);
    }


    public function isLocked(): bool
    {
        return $this->locked_by !== null
            && $this->lock_expires_at !== null
            && now()->lt($this->lock_expires_at);
    }
}
