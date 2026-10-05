<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Application\UseCases;

use App\Modules\Collaboration\Application\DTOs\AddCommentInput;
use App\Modules\Collaboration\Application\DTOs\CommentOutput;
use App\Modules\Collaboration\Domain\Entities\Comment;
use App\Modules\Collaboration\Domain\Repositories\CommentRepositoryInterface;
use App\Modules\Collaboration\Domain\ValueObjects\CommentBody;
use App\Modules\Collaboration\Domain\ValueObjects\CommentId;

final class AddCommentToIssueUseCase
{
    public function __construct(
        private readonly CommentRepositoryInterface $repository
    ) {}

    public function execute(AddCommentInput $input): CommentOutput
    {
        // Pengecekan authorization (ReBAC) idealnya dilakukan di tingkat presentasi
        // atau via interface abstrak jika perlu cross-module checking.

        $comment = Comment::create(
            new CommentId($input->commentId),
            $input->issueId,
            max(0, (int) $input->authorId),
            $input->parentId,
            new CommentBody($input->body)
        );

        // Transaction and event dispatching will be handled by infrastructure (decorator or repository)
        $this->repository->save($comment);

        return CommentOutput::fromDomain($comment);
    }
}
