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
 * @property string|null $icon
 * @property string|null $color
 * @property int|null $sort_order
 * @property bool $is_active
 */
final class IssueTypeModel extends Model
{
    use HasUuids;

    protected $table = 'issue_types';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'slug', 'icon', 'color', 'sort_order', 'is_active'];

    /**
     * @return HasMany<IssueModel, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(IssueModel::class, 'issue_type_id');
    }
}
