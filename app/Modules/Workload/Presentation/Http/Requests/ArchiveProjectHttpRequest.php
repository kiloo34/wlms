<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use App\Modules\Workload\Application\Services\WorkloadAccessChecker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

final class ArchiveProjectHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $projectId = (string) $this->route('id');
        if ($projectId) {
            $project = DB::table('projects')->where('id', $projectId)->whereNull('deleted_at')->first(['workspace_id', 'lead_id']);
            if ($project) {
                if ((string) $project->lead_id === (string) $user->id || WorkloadAccessChecker::canManageWorkspace($user->id, (string) $project->workspace_id)) {
                    return true;
                }
            }
        }

        return $user->hasPermission('projects:manage');
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
        return (string) $this->user()->id;
    }
}
