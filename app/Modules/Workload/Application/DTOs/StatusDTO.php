<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

final class StatusDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $category,
        public ?string $color
    ) {}
}
