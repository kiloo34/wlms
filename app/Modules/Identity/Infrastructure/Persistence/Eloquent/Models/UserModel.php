<?php

namespace App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Factories\UserFactory;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $email
 * @property string|null $org_unit_id
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Collection<int, UserRoleModel> $userRoles
 */
#[Fillable(['name', 'email', 'password', 'uuid', 'org_unit_id', 'locale'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class UserModel extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $table = 'users';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return Factory<UserModel>
     */
    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    /**
     * @return BelongsTo<OrgUnitModel, $this>
     */
    public function orgUnit(): BelongsTo
    {
        return $this->belongsTo(OrgUnitModel::class, 'org_unit_id');
    }

    /**
     * @return HasMany<UserRoleModel, $this>
     */
    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRoleModel::class, 'user_id');
    }

    public function hasRole(string $roleName): bool
    {
        $this->loadMissing('userRoles.role');
        foreach ($this->userRoles as $userRole) {
            if ($userRole->role && strtolower($userRole->role->name) === strtolower($roleName)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $permissionName): bool
    {
        $this->loadMissing('userRoles.role.permissions');
        foreach ($this->userRoles as $userRole) {
            if ($userRole->role) {
                // Return true if Superadmin is detected or exact permission matches
                if (strtolower($userRole->role->name) === 'superadmin') {
                    return true;
                }
                foreach ($userRole->role->permissions as $perm) {
                    if ($perm->name === $permissionName) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @return BelongsToMany<WorkspaceModel, $this>
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(
            WorkspaceModel::class,
            'workspace_members',
            'user_id',
            'workspace_id'
        )->withPivot('role')->withTimestamps();
    }
}
