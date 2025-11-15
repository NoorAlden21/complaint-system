<?php

namespace App\Repositories\ComplaintCategory;

use App\Models\ComplaintCategory;
use Illuminate\Database\Eloquent\Collection;

interface ComplaintCategoryRepositoryInterface
{
    public function allActive(): Collection;
}
