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
 * @property int $level
 * @property string|null $icon
 * @property string|null $color
 * @property bool $is_active
 */
final class PriorityModel extends Model
{
    use HasUuids;

    protected $table = 'priorities';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'slug', 'level', 'icon', 'color', 'is_active'];

    /**
     * @return HasMany<IssueModel, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(IssueModel::class, 'priority_id');
    }
}
