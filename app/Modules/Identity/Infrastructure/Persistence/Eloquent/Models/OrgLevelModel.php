<?php

namespace App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrgLevelModel extends Model
{
    use HasUuids;

    protected $table = 'org_levels';

    protected $fillable = [
        'name',
        'slug',
        'depth',
        'is_leaf',
        'can_own_workspace',
        'is_active',
    ];

    protected $casts = [
        'is_leaf' => 'boolean',
        'can_own_workspace' => 'boolean',
        'is_active' => 'boolean',
        'depth' => 'integer',
    ];

    /**
     * @return HasMany<OrgUnitModel, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(OrgUnitModel::class, 'org_level_id');
    }
}
