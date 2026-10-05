<?php

namespace App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string|null $scope
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, PermissionModel> $permissions
 */
class RoleModel extends Model
{
    use HasUuids;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'description',
        'scope',
    ];

    /**
     * @return BelongsToMany<PermissionModel, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            PermissionModel::class,
            'role_permissions',
            'role_id',
            'permission_id'
        );
    }

    /**
     * @return BelongsToMany<MenuModel, $this>
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(
            MenuModel::class,
            'role_menus',
            'role_id',
            'menu_id'
        );
    }
}
