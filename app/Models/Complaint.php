<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'category_id',
        'department_id',
        'created_by',
        // 'assigned_to',
        // 'is_anonymous',
        'sla_due_at',
        'resolved_at',
        'closed_at',
        'resolution_summary',
    ];

    protected $casts = [
        //'is_anonymous' => 'bool',
        'sla_due_at'   => 'datetime',
        'resolved_at'  => 'datetime',
        'closed_at'    => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(ComplaintCategory::class, 'category_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // public function assignee()
    // {
    //     return $this->belongsTo(User::class, 'assigned_to');
    // }

    public function comments()
    {
        return $this->hasMany(ComplaintComment::class);
    }

    public function attachments()
    {
        return $this->hasMany(ComplaintAttachment::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(ComplaintStatusHistory::class);
    }
}
