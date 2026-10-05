<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property bool $is_default
 */
final class WorkflowModel extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'workflows';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'description', 'is_default'];

    /**
     * @return HasMany<WorkflowTransitionModel, $this>
     */
    public function transitions(): HasMany
    {
        return $this->hasMany(WorkflowTransitionModel::class, 'workflow_id', 'id');
    }
}
