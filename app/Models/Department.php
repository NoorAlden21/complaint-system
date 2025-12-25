<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'is_active',
    ];

    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'ar') {
            return $this->name_ar ?: $this->name_en;
        }

        return $this->name_en ?: $this->name_ar;
    }

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();

        if ($locale === 'ar') {
            return $this->description_ar ?? $this->description_en;
        }

        return $this->description_en ?? $this->description_ar;
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function employees()
    {
        return $this->hasMany(User::class);
    }
}
