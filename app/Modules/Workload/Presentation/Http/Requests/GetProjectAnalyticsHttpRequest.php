<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use App\Modules\Workload\Application\Services\WorkloadAccessChecker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

final class GetProjectAnalyticsHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $projectId = (string) ($this->route('id') ?? $this->route('projectId') ?? $this->route('project_id') ?? '');
        if ($projectId === '') {
            abort(404, 'Project not found.');
        }

        $project = DB::table('projects')
            ->where('id', $projectId)
            ->whereNull('deleted_at')
            ->first(['id', 'workspace_id', 'lead_id']);

        if (! $project) {
            abort(404, 'Project not found.');
        }

        if (WorkloadAccessChecker::canManageWorkspace($user->id, (string) $project->workspace_id) || (string) $project->lead_id === (string) $user->id) {
            return true;
        }

        if (! $user->hasPermission('analytics:view') && ! $user->hasPermission('projects:manage')) {
            abort(403, 'Unauthorized.');
        }

        if (! WorkloadAccessChecker::canViewProject($user->id, $projectId, (string) $project->workspace_id, $project->lead_id)) {
            abort(403, 'Unauthorized.');
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date_range' => ['nullable', 'string', 'in:7d,30d,quarter,custom,last_7_days,last_30_days,this_quarter'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'sprint_id' => ['nullable', 'string'],
        ];
    }
}
