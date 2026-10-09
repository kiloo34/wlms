<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Mappers;

use App\Modules\Collaboration\Domain\Entities\Comment;
use App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Models\CommentModel;
use DateTimeImmutable;
use Illuminate\Support\Carbon;

final class CommentMapper
{
    public static function toDomain(CommentModel $model): Comment
    {
        return Comment::reconstruct(
            $model->id,
            $model->issue_id,
            (string) $model->author_id,
            $model->parent_id,
            $model->body,
            (bool) $model->is_edited,
            new DateTimeImmutable($model->created_at->toIso8601String()),
            new DateTimeImmutable($model->updated_at->toIso8601String())
        );
    }

    public static function toEloquent(Comment $entity, ?CommentModel $model = null): CommentModel
    {
        if ($model === null) {
            $model = new CommentModel;
            $model->id = $entity->id->value;
            $model->created_at = Carbon::instance($entity->createdAt);
        }

        $model->issue_id = $entity->issueId;
        $model->author_id = $entity->authorId;
        $model->parent_id = $entity->parentId;
        $model->body = $entity->body->value;
        $model->is_edited = $entity->isEdited;
        $model->updated_at = Carbon::instance($entity->updatedAt);

        return $model;
    }
}
