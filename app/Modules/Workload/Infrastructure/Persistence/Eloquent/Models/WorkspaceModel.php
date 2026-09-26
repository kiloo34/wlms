<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function members(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            \App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel::class,
            'workspace_members',
            'workspace_id',
            'user_id'
        )->withPivot(['role', 'daily_capacity_hours'])->withTimestamps();
    }
}
