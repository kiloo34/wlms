<?php
declare(strict_types=1);
namespace App\Modules\Collaboration\Infrastructure\Persistence\Repositories;

use App\Modules\Collaboration\Domain\Entities\Comment;
use App\Modules\Collaboration\Domain\Repositories\CommentRepositoryInterface;
use App\Modules\Collaboration\Domain\ValueObjects\CommentId;
use App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Mappers\CommentMapper;
use App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Models\CommentModel;

final class EloquentCommentRepository implements CommentRepositoryInterface
{
    public function save(Comment $comment): void
    {
        $model = CommentModel::find($comment->id->value);
        $model = CommentMapper::toEloquent($comment, $model);
        $model->save();

        // Normally here we would dispatch domain events, but we will leave it for the subscriber/decorator layer
        // if needed, or dispatch them directly using Laravel's event dispatcher.
        $events = $comment->getDomainEvents();
        foreach ($events as $event) {
            event($event);
        }
        $comment->clearDomainEvents();
    }

    public function findById(CommentId $id): ?Comment
    {
        $model = CommentModel::find($id->value);
        if (!$model) {
            return null;
        }

        return CommentMapper::toDomain($model);
    }

    public function findByIssueId(string $issueId): array
    {
        $models = CommentModel::where('issue_id', $issueId)->orderBy('created_at', 'asc')->get();
        return $models->map(fn(CommentModel $model) => CommentMapper::toDomain($model))->all();
    }
}

