<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

final class DeleteIssueHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var UserModel|null $user */
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $issueId = (string) $this->route('id');
        $projectId = $issueId !== ''
            ? (string) DB::table('issues')->where('id', $issueId)->value('project_id')
            : '';
        $workspaceId = $projectId !== ''
            ? (string) DB::table('projects')->where('id', $projectId)->value('workspace_id')
            : '';

        return $user->hasPermission('issues:delete')
            || $user->hasPermission('issues:manage')
            || ($workspaceId !== '' && (
                $user->hasPermission('issues:delete', 'WORKSPACE', $workspaceId)
                || $user->hasPermission('issues:manage', 'WORKSPACE', $workspaceId)
            ))
            || ($projectId !== '' && (
                $user->hasPermission('issues:delete', 'PROJECT', $projectId)
                || $user->hasPermission('issues:manage', 'PROJECT', $projectId)
            ));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
