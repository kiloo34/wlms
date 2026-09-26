<?php

namespace App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function level(): BelongsTo
    {
        return $this->belongsTo(OrgLevelModel::class, 'org_level_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrgUnitModel::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OrgUnitModel::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserModel::class, 'org_unit_id');
    }
}

