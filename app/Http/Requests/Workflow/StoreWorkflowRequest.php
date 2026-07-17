<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkflowRequest extends FormRequest
{
    /**
     * Authorization is enforced by CreateWorkflowAction once the target
     * project is resolved from the validated input.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'uuid', 'exists:projects,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'trigger_type' => ['required', 'string', 'max:80'],
            'trigger_value' => ['nullable', 'string', 'max:255'],
            'action_type' => ['required', 'string', 'max:80'],
            'action_target' => ['nullable', 'string', 'max:255'],
        ];
    }
}
