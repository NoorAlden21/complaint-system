<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PerformanceStatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],

            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'region_id'     => ['nullable', 'integer', 'exists:regions,id'],
            'category_id'   => ['nullable', 'integer', 'exists:complaint_categories,id'],
        ];
    }
}
