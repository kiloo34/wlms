<?php
declare(strict_types=1);
namespace App\Modules\Workload\Application\DTOs;

final class WorkflowDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
        public bool $isDefault
    ) {}
}
