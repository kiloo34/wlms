<?php
declare(strict_types=1);
namespace App\Modules\Workload\Presentation\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Workload\Domain\Entities\Workflow;

/**
 * @property Workflow $resource
 */
class WorkflowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId()->value,
            'name' => $this->resource->getName(),
            'description' => $this->resource->getDescription(),
            'is_default' => $this->resource->isDefault(),
        ];
    }
}
