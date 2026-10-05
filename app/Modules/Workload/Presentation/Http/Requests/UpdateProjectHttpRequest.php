<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateProjectHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('projects:manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'lead_id' => ['nullable', 'uuid'],
            'workflow_id' => ['nullable', 'uuid'],
            'priority_id' => ['nullable', 'uuid', 'exists:priorities,id'],
        ];
    }

    public function getActorId(): string
    {
        return (string) $this->user()->id;
    }
}
