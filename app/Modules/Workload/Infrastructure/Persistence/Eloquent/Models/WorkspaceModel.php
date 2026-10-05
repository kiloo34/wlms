<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string|null $owner_group_id
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property array<string, mixed>|null $settings
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class WorkspaceModel extends Model
{
    use SoftDeletes;

    protected $table = 'workspaces';

    /**
     * Primary key adalah UUID (string).
     */
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'owner_group_id',
        'name',
        'description',
        'status',
        'settings',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'id' => 'string',
        'owner_group_id' => 'string',
        'settings' => 'array',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    /**
     * @return BelongsToMany<UserModel, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            UserModel::class,
            'workspace_members',
            'workspace_id',
            'user_id'
        )->withPivot(['role', 'daily_capacity_hours'])->withTimestamps();
    }
}
