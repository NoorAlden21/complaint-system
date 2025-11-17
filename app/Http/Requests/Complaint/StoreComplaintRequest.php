<?php

namespace App\Http\Requests\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        // return $this->user()?->hasRole('citizen') ?? false;
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string'],
            'category_id'  => ['required', 'exists:complaint_categories,id'],
            'department_id' => ['nullable', 'exists:departments,id'],

            'attachments'   => ['nullable', 'array', 'max:20'],
            'attachments.*' => [
                'file',
                'mimes:jpg,jpeg,png,pdf,doc,docx',
                'max:10240',
            ],
        ];
    }
}
