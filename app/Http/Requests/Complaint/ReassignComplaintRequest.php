<?php

namespace App\Http\Requests\Complaint;

use Illuminate\Foundation\Http\FormRequest;

class ReassignComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (!$user) {
            return false;
        }
        return $user->hasRole('super_admin') || $user->hasRole('employee');
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'note'          => ['nullable', 'string', 'max:2000'],
        ];
    }
}
