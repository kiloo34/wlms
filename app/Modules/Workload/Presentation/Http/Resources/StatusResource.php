<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Resources;

use App\Modules\Workload\Domain\Entities\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Status $resource
 */
class StatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
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
