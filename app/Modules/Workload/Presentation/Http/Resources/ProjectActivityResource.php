<?php

namespace App\Modules\Workload\Presentation\Http\Resources;

use App\Modules\Workload\Application\DTOs\ProjectActivityOutput;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProjectActivityOutput
 */
class ProjectActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'issue_id' => $this->issueId,
            'issue_title' => $this->issueTitle,
            'actor' => [
                'id' => $this->actorId,
                'name' => $this->actorName,
            ],
            'field_changed' => $this->fieldChanged,
            'old_value' => $this->oldValue,
            'new_value' => $this->newValue,
            'created_at' => $this->createdAt,
        ];
    }
}
