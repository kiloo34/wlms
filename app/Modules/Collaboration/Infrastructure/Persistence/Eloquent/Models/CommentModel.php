<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $issue_id
 * @property string|null $parent_id
 * @property string $author_id
 * @property string $body
 * @property bool $is_edited
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class CommentModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'comments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'issue_id',
        'parent_id',
        'author_id',
        'body',
        'is_edited',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
    ];
}
