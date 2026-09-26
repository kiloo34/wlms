<?php
declare(strict_types=1);
namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class WorkflowModel extends Model
{
    use HasUuids, SoftDeletes;
    protected $table = 'workflows';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id','name','description','is_default'];

    public function transitions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkflowTransitionModel::class, 'workflow_id', 'id');
    }
}
