<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Entities;

use App\Modules\Workload\Domain\Events\WorkspaceCreated;
use App\Modules\Workload\Domain\ValueObjects\WorkspaceId;
use App\Shared\Domain\Traits\HasDomainEvents;
use DateTimeImmutable;

final class Workspace
{
    use HasDomainEvents;

    /**
     * @param  array<string, mixed>|null  $settings
     */
    private function __construct(
        private readonly WorkspaceId $id,
        private readonly string $ownerGroupId,
        private string $name,
        private string $status,
        private ?array $settings,
        private readonly DateTimeImmutable $createdAt
    ) {}

    /**
     * Factory method untuk membuat Workspace baru.
     * Tidak ada IDOR/Otorisasi di sini, itu urusan UseCase.
     */
    public static function create(
        WorkspaceId $id,
        string $ownerGroupId,
        string $name,
        string $actorId
    ): self {
        $workspace = new self(
            $id,
            $ownerGroupId,
            $name,
            'ACTIVE',
            null,
            new DateTimeImmutable
        );

        // Rekam event bisnis
        $workspace->recordEvent(new WorkspaceCreated(
            $id->value,
            $ownerGroupId,
            $actorId,
            $name,
            new DateTimeImmutable
        ));

        return $workspace;
    }

    public function archive(string $actorId): void
    {
        $this->status = 'ARCHIVED';
        // $this->recordEvent(new WorkspaceArchived(...)); // Untuk implementasi selanjutnya
    }

    /**
     * @param  array<string, mixed>|null  $settings
     */
    public function updateDetails(string $newName, ?array $settings, string $actorId): void
    {
        $this->name = $newName;
        $this->settings = $settings;
    }

    public function rename(string $newName, string $actorId): void
    {
        $this->name = $newName;
        // $this->recordEvent(new WorkspaceRenamed(...)); // Untuk implementasi selanjutnya
    }

    // Getters murni (No Setters)
    public function getId(): WorkspaceId
    {
        return $this->id;
    }

    public function getOwnerGroupId(): string
    {
        return $this->ownerGroupId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSettings(): ?array
    {
        return $this->settings;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
