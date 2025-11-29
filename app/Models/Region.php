<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $fillable = ['name_en', 'name_ar'];
    protected $appends = ['name'];

    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        if ($locale === "ar") {
            return $this->name_ar ?: $this->name_en;
        }
        return $this->name_en ?: $this->name_ar;
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }
}
