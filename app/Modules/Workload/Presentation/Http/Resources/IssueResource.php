<?php
declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'number' => $this->number,
            'title' => $this->title,
            'description' => $this->description,
            'issue_type_id' => $this->issue_type_id,
            'priority_id' => $this->priority_id,
            'status_id' => $this->status_id,
            'sprint_id' => $this->sprint_id,
            'reporter_id' => $this->reporter_id,
            'assignee_id' => $this->assignee_id,
            'story_points' => $this->story_points,
            'original_estimate_seconds' => $this->original_estimate_seconds,
            'remaining_estimate_seconds' => $this->remaining_estimate_seconds,
            'created_at' => $this->created_at,
            
            // Assuming eager loading for relationships
            'assignee' => $this->whenLoaded('assignee', function () {
                return [
                    'id' => $this->assignee->id,
                    'name' => $this->assignee->name,
                ];
            }),
            'reporter' => $this->whenLoaded('reporter', function () {
                return [
                    'id' => $this->reporter->id,
                    'name' => $this->reporter->name,
                ];
            }),
            'status' => $this->whenLoaded('status', function () {
                return [
                    'id' => $this->status->id,
                    'name' => $this->status->name,
                    'slug' => $this->status->slug,
                    'category' => $this->status->category,
                    'color' => $this->status->color,
                ];
            }),
        ];
    }
}
