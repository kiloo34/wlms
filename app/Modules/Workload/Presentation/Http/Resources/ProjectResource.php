<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * ZERO DATA BREACH: Eksplisit mendefinisikan field yang aman diekspos.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workspace_id' => $this->workspaceId ?? $this->workspace_id,
            'key' => $this->key,
            'name' => $this->name,
            'description' => $this->description ?? null,
            'status' => $this->status,
            'lead_id' => $this->lead_id ?? $this->leadId ?? null,
            'workflow_id' => $this->workflow_id ?? $this->workflowId ?? null,
            'priority_id' => $this->priority_id ?? $this->priorityId ?? null,
        ];
    }
}
