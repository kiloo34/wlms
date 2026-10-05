<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IssueCommentModel extends Model
{
    use HasUuids;

    protected $table = 'issue_comments';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'issue_id',
        'author_id',
        'body',
    ];

    /**
     * @return BelongsTo<IssueModel, $this>
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(IssueModel::class, 'issue_id');
    }

    /**
     * @return BelongsTo<UserModel, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'author_id');
    }
}
