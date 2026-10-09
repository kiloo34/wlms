<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $workspace_id
 * @property string|null $workflow_id
 * @property string $priority_id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property string $status
 * @property string|null $lead_id
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
final class ProjectModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'projects';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'workspace_id',
        'workflow_id',
        'priority_id',
        'key',
        'name',
        'description',
        'status',
        'lead_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * @return HasMany<IssueModel, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(IssueModel::class, 'project_id');
    }

    /**
     * @return BelongsTo<PriorityModel, $this>
     */
    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityModel::class, 'priority_id', 'id');
    }

    /**
     * @return BelongsTo<WorkspaceModel, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(WorkspaceModel::class, 'workspace_id');
    }
}
