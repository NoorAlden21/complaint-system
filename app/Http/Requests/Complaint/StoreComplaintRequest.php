<?php

namespace App\Http\Requests\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        // لو حاب تحصرها على citizen:
        // return $this->user()?->hasRole('citizen') ?? false;
        // return $this->user() !== null;
        return true;
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string'],
            'category_id'  => ['required', 'exists:complaint_categories,id'],
            'department_id' => ['nullable', 'exists:departments,id'],
            //'priority'     => ['required', 'in:low,medium,high,urgent'],
        ];
    }
}
