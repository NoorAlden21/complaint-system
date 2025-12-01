<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    protected $fillable = [
        'disk',
        'path',
        'size',
        'backup_name',
        'status',
        'finished_at',
        'error_message',
    ];

    protected $casts = [
        'finished_at' => 'datetime',
    ];
}
