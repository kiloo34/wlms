<?php

namespace App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $parent_id
 * @property string $org_level_id
 * @property string $name
 * @property string|null $code
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OrgUnitModel extends Model
{
    use HasUuids;

    protected $table = 'org_units';

    protected $fillable = [
        'parent_id',
        'org_level_id',
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<OrgLevelModel, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(OrgLevelModel::class, 'org_level_id');
    }

    /**
     * @return BelongsTo<OrgUnitModel, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrgUnitModel::class, 'parent_id');
    }

    /**
     * @return HasMany<OrgUnitModel, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(OrgUnitModel::class, 'parent_id');
    }

    /**
     * @return HasMany<UserModel, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(UserModel::class, 'org_unit_id');
    }
}
