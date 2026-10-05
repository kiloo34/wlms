<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
