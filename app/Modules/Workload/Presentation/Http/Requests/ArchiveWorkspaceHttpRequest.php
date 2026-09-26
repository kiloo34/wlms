<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ArchiveWorkspaceHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('workspaces:manage');
    }

    public function rules(): array
    {
        return [];
    }

    public function getActorId(): string
    {
        return (string) auth()->id();
    }
}
