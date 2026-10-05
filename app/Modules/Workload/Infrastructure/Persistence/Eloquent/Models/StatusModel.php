<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string $category
 * @property string|null $color
 */
final class StatusModel extends Model
{
    use HasUuids;

    protected $table = 'statuses';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'slug', 'category', 'color'];

    /**
     * @return HasMany<IssueModel, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(IssueModel::class, 'status_id');
    }
}
