<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ($this->user()?->can('manage-rbac') ?? false)
            || ($this->user()?->hasPermission('projects:manage') ?? false)
            || ($this->user()?->hasPermission('workspaces:manage') ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'slug' => 'required|string|max:50|unique:statuses,slug',
            'category' => 'required|string|max:20|in:TODO,IN_PROGRESS,DONE',
            'color' => 'nullable|string|max:20',
        ];
    }
}
