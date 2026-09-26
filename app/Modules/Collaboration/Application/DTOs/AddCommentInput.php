<?php
declare(strict_types=1);
namespace App\Modules\Collaboration\Application\DTOs;

final class AddCommentInput
{
    public function __construct(
        public readonly string $commentId,
        public readonly string $issueId,
        public readonly string $authorId,
        public readonly string $body,
        public readonly ?string $parentId = null
    ) {}
}

