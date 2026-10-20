<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use App\Modules\Workload\Application\Services\WorkloadAccessChecker;
use Illuminate\Foundation\Http\FormRequest;

final class ArchiveWorkspaceHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $workspaceId = (string) ($this->route('id') ?? $this->route('workspaceId') ?? '');
        if ($workspaceId && WorkloadAccessChecker::canManageWorkspace($user->id, $workspaceId)) {
            return true;
        }

        return $user->hasPermission('workspaces:manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    public function getActorId(): string
    {
        return (string) auth()->id();
    }
}
