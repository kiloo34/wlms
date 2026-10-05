<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Domain\Repositories;

use App\Modules\Collaboration\Domain\Entities\Comment;
use App\Modules\Collaboration\Domain\ValueObjects\CommentId;

interface CommentRepositoryInterface
{
    public function save(Comment $comment): void;

    public function findById(CommentId $id): ?Comment;

    /**
     * @return Comment[]
     */
    public function findByIssueId(string $issueId): array;
}
