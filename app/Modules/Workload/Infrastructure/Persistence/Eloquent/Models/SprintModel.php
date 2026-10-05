<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $project_id
 * @property string $name
 * @property string|null $goal
 * @property string $state
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property int $committed_points
 * @property int $completed_points
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class SprintModel extends Model
{
    use HasUuids;

    protected $table = 'sprints';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'project_id',
        'name',
        'goal',
        'state',
        'start_date',
        'end_date',
        'committed_points',
        'completed_points',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'committed_points' => 'integer',
        'completed_points' => 'integer',
    ];

    /**
     * @return HasMany<IssueModel, $this>
     */
    public function issues(): HasMany
    {
        return $this->hasMany(IssueModel::class, 'sprint_id');
    }

    /**
     * @return BelongsTo<ProjectModel, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectModel::class, 'project_id');
    }
}
