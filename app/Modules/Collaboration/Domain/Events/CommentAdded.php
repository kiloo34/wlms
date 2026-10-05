<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Domain\Events;

use DateTimeImmutable;

final class CommentAdded
{
    public function __construct(
        public readonly string $commentId,
        public readonly string $issueId,
        public readonly int|string $authorId,
        public readonly ?string $parentId,
        public readonly string $body,
        public readonly DateTimeImmutable $occurredAt
    ) {}
}
