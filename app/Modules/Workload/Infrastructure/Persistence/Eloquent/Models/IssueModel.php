<?php
declare(strict_types=1);
namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class IssueModel extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'issues';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id','project_id','sprint_id','status_id','number','title','description','issue_type_id','priority_id','story_points','original_estimate_seconds','remaining_estimate_seconds','reporter_id','assignee_id','custom_fields'];
    protected $casts = ['number' => 'integer', 'story_points' => 'integer', 'original_estimate_seconds' => 'integer', 'custom_fields' => 'array'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(ProjectModel::class, 'project_id');
    }

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(SprintModel::class, 'sprint_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(StatusModel::class, 'status_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(IssueTypeModel::class, 'issue_type_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(PriorityModel::class, 'priority_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'reporter_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'assignee_id');
    }

    public function worklogs(): HasMany
    {
        return $this->hasMany(WorklogModel::class, 'issue_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(IssueHistoryModel::class, 'issue_id');
    }

    public function linksAsSource(): HasMany
    {
        return $this->hasMany(IssueLinkModel::class, 'source_issue_id');
    }

    public function linksAsTarget(): HasMany
    {
        return $this->hasMany(IssueLinkModel::class, 'target_issue_id');
    }
}
