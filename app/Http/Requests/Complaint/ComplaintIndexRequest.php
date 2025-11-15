<?php

namespace App\Http\Requests\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class ComplaintIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return $this->user() !== null;
        return true;
    }

    public function rules(): array
    {
        return [
            'status'        => ['nullable', 'in:pending,open,in_progress,resolved,closed,rejected'],
            'priority'      => ['nullable', 'in:low,medium,high,urgent'],
            'category_id'   => ['nullable', 'exists:complaint_categories,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'per_page'      => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
