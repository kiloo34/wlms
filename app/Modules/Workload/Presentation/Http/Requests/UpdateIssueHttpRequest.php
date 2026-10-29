<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

final class UpdateIssueHttpRequest extends FormRequest
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

        return $user->hasPermission('issues:edit')
            || $user->hasPermission('issues:manage')
            || ($workspaceId !== '' && (
                $user->hasPermission('issues:edit', 'WORKSPACE', $workspaceId)
                || $user->hasPermission('issues:manage', 'WORKSPACE', $workspaceId)
            ))
            || ($projectId !== '' && (
                $user->hasPermission('issues:edit', 'PROJECT', $projectId)
                || $user->hasPermission('issues:manage', 'PROJECT', $projectId)
            ));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'issue_type_id' => ['required', 'uuid'],
            'status_id' => ['required', 'uuid'],
            'priority_id' => ['required', 'uuid'],
            'assignee_id' => ['nullable'], // Users table uses integer IDs
            'sprint_id' => ['nullable', 'uuid'],
            'original_estimate_seconds' => ['nullable', 'integer', 'min:0'],
            'remaining_estimate_seconds' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'parent_id' => ['nullable', 'uuid', new ValidParentIssue((string) (IssueModel::find($this->route('id'))?->project_id), (string) $this->route('id'))],
        ];
    }
}
