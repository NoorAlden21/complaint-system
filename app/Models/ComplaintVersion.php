<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComplaintVersion extends Model
{
    protected $fillable = [
        'complaint_id',
        'version_number',
        'status',
        'title',
        'description',
        'category_id',
        'department_id',
        'region_id',
        'priority',
        'changed_by',
        'note',
    ];

    public function complaint()
    {
        return $this->belongsTo(Complaint::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function notes()
    {
        return $this->hasMany(ComplaintNote::class, 'complaint_version_id');
    }
}
