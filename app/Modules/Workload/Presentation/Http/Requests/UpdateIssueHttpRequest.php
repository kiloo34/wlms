<?php
declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateIssueHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel $user */
        $user = $this->user();
        return $user && $user->hasPermission('issues:manage');
    }

    public function rules(): array
    {
        return [
            'title'         => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'issue_type_id' => ['required', 'uuid'],
            'status_id'     => ['required', 'uuid'],
            'priority_id'   => ['required', 'uuid'],
            'assignee_id'   => ['nullable'], // Users table uses integer IDs
            'sprint_id'     => ['nullable', 'uuid'],
            'original_estimate_seconds' => ['nullable', 'integer', 'min:0'],
            'remaining_estimate_seconds' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
