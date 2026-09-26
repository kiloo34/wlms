<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateWorkspaceHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('workspaces:manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'settings' => ['nullable', 'array'],
        ];
    }

    public function getActorId(): string
    {
        return (string) auth()->id();
    }
}
