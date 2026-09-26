<?php
declare(strict_types=1);
namespace App\Modules\Workload\Presentation\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Workload\Domain\Entities\Status;

/**
 * @property Status $resource
 */
class StatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId()->value,
            'name' => $this->resource->getName(),
            'slug' => $this->resource->getSlug(),
            'category' => $this->resource->getCategory(),
            'color' => $this->resource->getColor(),
        ];
    }
}
