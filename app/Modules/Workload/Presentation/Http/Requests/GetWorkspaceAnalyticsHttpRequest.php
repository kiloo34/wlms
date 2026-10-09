<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

final class GetWorkspaceAnalyticsHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $workspaceId = (string) ($this->route('id') ?? $this->route('workspaceId') ?? $this->route('workspace_id') ?? '');
        if ($workspaceId === '') {
            abort(404, 'Workspace not found.');
        }

        $workspace = WorkspaceModel::where('id', $workspaceId)->whereNull('deleted_at')->first();
        if (! $workspace) {
            abort(404, 'Workspace not found.');
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

        // 2. Owner group / internal member
        if ($user->org_unit_id && $user->org_unit_id === $workspace->owner_group_id) {
            return true;
        }

        // 3. Workspace Policy check
        if ($user->can('view', $workspace)) {
            return true;
        }

        // 4. Workspace membership check
        return DB::table('workspace_members')
            ->where('workspace_id', $workspace->id)
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
        ];
    }
}
