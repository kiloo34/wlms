<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Domain\Entities;

use App\Modules\Collaboration\Domain\Events\CommentAdded;
use App\Modules\Collaboration\Domain\Events\CommentEdited;
use App\Modules\Collaboration\Domain\ValueObjects\CommentBody;
use App\Modules\Collaboration\Domain\ValueObjects\CommentId;
use DateTimeImmutable;

final class Comment
{
    /** @var array<int, object> */
    private array $domainEvents = [];

    /**
     * @param  int<0, max>  $authorId
     */
    private function __construct(
        public readonly CommentId $id,
        public readonly string $issueId,
        public readonly int $authorId,
        public readonly ?string $parentId,
        public CommentBody $body,
        public bool $isEdited,
        public readonly DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt
    ) {}

    /**
     * @param  int<0, max>  $authorId
     */
    public static function create(
        CommentId $id,
        string $issueId,
        int $authorId,
        ?string $parentId,
        CommentBody $body
    ): self {
        $now = new DateTimeImmutable;
        $comment = new self($id, $issueId, $authorId, $parentId, $body, false, $now, $now);

        $comment->recordEvent(new CommentAdded(
            $id->value,
            $issueId,
            $authorId,
            $parentId,
            $body->value,
            $now
        ));

        return $comment;
    }

    /**
     * @param  int<0, max>  $authorId
     */
    public static function reconstruct(
        string $id,
        string $issueId,
        int $authorId,
        ?string $parentId,
        string $body,
        bool $isEdited,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt
    ): self {
        return new self(
            new CommentId($id),
            $issueId,
            $authorId,
            $parentId,
            new CommentBody($body),
            $isEdited,
            $createdAt,
            $updatedAt
        );
    }

    public function edit(CommentBody $newBody, DateTimeImmutable $updatedAt): void
    {
        $this->body = $newBody;
        $this->isEdited = true;
        $this->updatedAt = $updatedAt;

        $this->recordEvent(new CommentEdited(
            $this->id->value,
            $this->issueId,
            $this->authorId,
            $updatedAt
        ));
    }

    /**
     * @return array<int, object>
     */
    public function getDomainEvents(): array
    {
        return $this->domainEvents;
    }

    public function clearDomainEvents(): void
    {
        $this->domainEvents = [];
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
