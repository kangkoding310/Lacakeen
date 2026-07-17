<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    /**
     * Authorization is enforced by CreateProjectAction once the target
     * workspace is resolved from the validated input.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('prefix')) {
            $this->merge(['prefix' => strtoupper((string) $this->prefix)]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'prefix' => ['required', 'alpha_num:ascii', 'max:12', Rule::unique('projects')->where('workspace_id', $this->workspace_id)],
            'description' => ['nullable', 'string'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'workspace_id' => ['required', 'uuid', 'exists:workspaces,id'],
        ];
    }
}
