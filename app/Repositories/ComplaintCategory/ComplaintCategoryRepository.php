<?php

namespace App\Repositories\ComplaintCategory;

use App\Models\ComplaintCategory;
use Illuminate\Database\Eloquent\Collection;

class ComplaintCategoryRepository implements ComplaintCategoryRepositoryInterface
{
    public function allActive(): Collection
    {
        return ComplaintCategory::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }
}
