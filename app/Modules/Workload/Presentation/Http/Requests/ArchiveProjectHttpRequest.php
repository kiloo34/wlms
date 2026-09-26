<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ArchiveProjectHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('projects:manage') ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function getActorId(): string
    {
        return (string) $this->user()->id;
    }
}
