<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class CreateProjectHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('projects:manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'workspace_id' => ['required', 'uuid'],
            'key' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'lead_id' => ['nullable', 'uuid'],
            'priority_id' => ['nullable', 'uuid', 'exists:priorities,id'],
        ];
    }

    public function getIdempotencyKey(): string
    {
        return (string) ($this->header('Idempotency-Key') ?? Str::uuid());
    }

    public function getActorId(): string
    {
        return (string) $this->user()->id;
    }
}
