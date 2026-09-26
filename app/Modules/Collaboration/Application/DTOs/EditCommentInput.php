<?php
declare(strict_types=1);
namespace App\Modules\Collaboration\Application\DTOs;

final class EditCommentInput
{
    public function __construct(
        public readonly string $commentId,
        public readonly string $authorId,
        public readonly string $body
    ) {}
}

