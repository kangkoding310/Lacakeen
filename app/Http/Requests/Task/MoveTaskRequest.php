<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class MoveTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('task'));
    }

    public function rules(): array
    {
        return [
            'status_id' => ['required', 'uuid', 'exists:task_statuses,id'],
            'order' => ['required', 'integer', 'min:0'],
            'ordered_ids' => ['sometimes', 'array'],
            'ordered_ids.*' => ['uuid', 'exists:tasks,id'],
        ];
    }
}
