<?php
declare(strict_types=1);
namespace App\Modules\Collaboration\Domain\Events;

use DateTimeImmutable;

final class CommentEdited
{
    public function __construct(
        public readonly string $commentId,
        public readonly string $issueId,
        public readonly string $authorId,
        public readonly DateTimeImmutable $occurredAt
    ) {}
}

