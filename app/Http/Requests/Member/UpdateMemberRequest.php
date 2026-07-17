<?php

namespace App\Http\Requests\Member;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage users');
    }

    public function rules(): array
    {
        return [
            'role' => ['sometimes', Rule::exists('roles', 'name')],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
        ];
    }
}
