<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'label_ar',
        'label_en',
        'description_ar',
        'description_en',
        'is_active',
    ];

    public function getLabelAttribute(): string
    {
        $locale = app()->getLocale();

        if ($locale === 'ar') {
            return $this->label_ar ?: $this->label_en;
        }
    }

    public function getDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();

        if ($locale === 'ar') {
            return $this->description_ar ?? $this->description_en;
        }
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'category_id');
    }
}
