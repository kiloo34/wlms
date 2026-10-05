<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Entities;

use App\Modules\Workload\Domain\ValueObjects\StatusId;

final class Status
{
    public function __construct(
        private readonly StatusId $id,
        private string $name,
        private string $slug,
        private string $category,
        private ?string $color
    ) {}

    public static function create(
        StatusId $id,
        string $name,
        string $slug,
        string $category,
        ?string $color
    ): self {
        return new self($id, $name, $slug, $category, $color);
    }

    public function update(
        string $name,
        string $slug,
        string $category,
        ?string $color
    ): void {
        $this->name = $name;
        $this->slug = $slug;
        $this->category = $category;
        $this->color = $color;
    }

    public function getId(): StatusId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }
}
