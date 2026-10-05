<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class CreateSprintHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Workspace Owner') || $this->user()?->hasRole('Superadmin');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'uuid'],
            'name' => ['required', 'string', 'max:100'],
            'goal' => ['nullable', 'string'],
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
