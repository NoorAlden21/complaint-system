<?php

namespace App\Http\Requests\Complaint;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComplaintRequest extends FormRequest
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
            'row_version'   => ['required', 'integer'],

            'status' => [
                'nullable',
                'string',
                Rule::in([
                    'pending',
                    'needs_more_info',
                    'open',
                    'in_progress',
                    'resolved',
                    'closed',
                    'rejected',
                ]),
            ],

            'priority' => [
                'nullable',
                'string',
                Rule::in([
                    'low',
                    'medium',
                    'high',
                    'urgent',
                ]),
            ],

            'department_id' => [
                'nullable',
                'integer',
                'exists:departments,id',
            ],

            'note' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'info_request_message' => [
                'nullable',
                'string',
                'max:2000',
                'required_if:status,needs_more_info',
            ],
        ];
    }
}
