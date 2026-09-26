<?php
declare(strict_types=1);

namespace App\Modules\Workload\Domain\Entities;

use App\Modules\Workload\Domain\ValueObjects\WorkflowId;

final class Workflow
{
    public function __construct(
        private readonly WorkflowId $id,
        private string $name,
        private ?string $description,
        private bool $isDefault
    ) {}

    public static function create(
        WorkflowId $id,
        string $name,
        ?string $description,
        bool $isDefault
    ): self {
        return new self($id, $name, $description, $isDefault);
    }

    public function update(
        string $name,
        ?string $description,
        bool $isDefault
    ): void {
        $this->name = $name;
        $this->description = $description;
        $this->isDefault = $isDefault;
    }

    public function getId(): WorkflowId { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): ?string { return $this->description; }
    public function isDefault(): bool { return $this->isDefault; }
}

