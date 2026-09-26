<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Modules\Collaboration\Domain\Entities\Comment;
use App\Modules\Collaboration\Domain\ValueObjects\CommentId;
use App\Modules\Collaboration\Domain\ValueObjects\CommentBody;
use App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Mappers\CommentMapper;

$comment = Comment::create(
    new CommentId((string) Illuminate\Support\Str::uuid()),
    (string) Illuminate\Support\Str::uuid(),
    (string) Illuminate\Support\Str::uuid(),
    null,
    new CommentBody('test')
);

dump('Entity ID: ' . $comment->id->value);

$model = CommentMapper::toEloquent($comment);
dump('Model ID before save: ' . $model->id);
$model->save();
dump('Model ID after save: ' . $model->id);
