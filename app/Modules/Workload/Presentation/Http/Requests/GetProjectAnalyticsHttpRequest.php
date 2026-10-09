<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\ProjectModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
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

        $project = ProjectModel::where('id', $projectId)->whereNull('deleted_at')->first();
        if (! $project) {
            abort(404, 'Project not found.');
        }

        // 1. Superadmin check
        if ($user->email === 'superadmin@wlms.com') {
            return true;
        }

        if ($user->hasRole('Superadmin')) {
            return true;
        }

        $isSuperadmin = DB::table('user_roles')
            ->join('roles', 'user_roles.role_id', '=', 'roles.id')
            ->where('user_roles.user_id', $user->id)
            ->where('roles.name', 'Superadmin')
            ->exists();
        if ($isSuperadmin) {
            return true;
        }

        // 2. Workspace owner group / internal member
        $workspace = WorkspaceModel::where('id', $project->workspace_id)->whereNull('deleted_at')->first();
        if ($workspace && $user->org_unit_id && $user->org_unit_id === $workspace->owner_group_id) {
            return true;
        }

        // 3. Project lead
        if ($project->lead_id !== null && (int) $project->lead_id === (int) $user->id) {
            return true;
        }

        // 4. Assigned to at least one issue in the project
        $isAssigned = DB::table('issues')
            ->where('project_id', $project->id)
            ->whereNull('deleted_at')
            ->where('assignee_id', $user->id)
            ->exists();
        if ($isAssigned) {
            return true;
        }

        // 5. Context role in the project
        $hasContextRole = DB::table('user_roles')
            ->where('context_id', $project->id)
            ->where('context_type', 'PROJECT')
            ->where('user_id', $user->id)
            ->exists();
        if ($hasContextRole) {
            return true;
        }

        // 6. Workspace membership
        return DB::table('workspace_members')
            ->where('workspace_id', $project->workspace_id)
            ->where('user_id', $user->id)
            ->exists();
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
