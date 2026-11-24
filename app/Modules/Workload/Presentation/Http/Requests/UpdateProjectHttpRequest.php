<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use App\Modules\Workload\Application\Services\WorkloadAccessChecker;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateProjectHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        $projectId = (string) $this->route('id');
        if ($projectId !== '') {
            return WorkloadAccessChecker::canEditProject($user->id, $projectId);
        }

        return $user->hasPermission('projects:manage') || $user->hasPermission('projects:edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'lead_id' => ['nullable', 'exists:users,id'],
            'workflow_id' => ['nullable', 'uuid'],
            'priority_id' => ['nullable', 'uuid', 'exists:priorities,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            // Durasi (hari kalender, inklusif). Dipakai bila end_date kosong.
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ];
    }

    public function getActorId(): string
    {
        return (string) $this->user()->id;
    }
}
