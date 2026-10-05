<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Application\DTOs;

use App\Modules\Collaboration\Domain\Entities\Comment;

final class CommentOutput
{
    public function __construct(
        public readonly string $id,
        public readonly string $issueId,
        public readonly int|string $authorId,
        public readonly ?string $parentId,
        public readonly string $body,
        public readonly bool $isEdited,
        public readonly string $createdAt,
        public readonly string $updatedAt
    ) {}

    public static function fromDomain(Comment $comment): self
    {
        return new self(
            $comment->id->value,
            $comment->issueId,
            $comment->authorId,
            $comment->parentId,
            $comment->body->value,
            $comment->isEdited,
            $comment->createdAt->format('Y-m-d\TH:i:sP'),
            $comment->updatedAt->format('Y-m-d\TH:i:sP')
        );
    }
}
