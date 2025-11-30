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
        'sla_due_at',
        'resolved_at',
        'closed_at',
        'resolution_summary',
    ];

    protected $casts = [
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

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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
}
