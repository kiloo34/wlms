<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Application\UseCases;

use App\Modules\Collaboration\Application\DTOs\CommentOutput;
use App\Modules\Collaboration\Application\DTOs\EditCommentInput;
use App\Modules\Collaboration\Domain\Repositories\CommentRepositoryInterface;
use App\Modules\Collaboration\Domain\ValueObjects\CommentBody;
use App\Modules\Collaboration\Domain\ValueObjects\CommentId;
use DateTimeImmutable;
use RuntimeException;

final class EditCommentUseCase
{
    public function __construct(
        private readonly CommentRepositoryInterface $repository
    ) {}

    public function execute(EditCommentInput $input): CommentOutput
    {
        $commentId = new CommentId($input->commentId);
        $comment = $this->repository->findById($commentId);

        if (! $comment) {
            throw new RuntimeException('Comment not found.');
        }

        // Anti-IDOR: Only author can edit
        if ((int) $comment->authorId !== (int) $input->authorId) {
            throw new RuntimeException('Unauthorized to edit this comment.');
        }

        $comment->edit(new CommentBody($input->body), new DateTimeImmutable);

        $this->repository->save($comment);

        return CommentOutput::fromDomain($comment);
    }
}
