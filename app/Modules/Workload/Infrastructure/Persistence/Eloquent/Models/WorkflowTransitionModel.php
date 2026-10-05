<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $from_status_id
 * @property string $to_status_id
 * @property string $name
 */
final class WorkflowTransitionModel extends Model
{
    use HasUuids;

    protected $table = 'workflow_transitions';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'workflow_id', 'from_status_id', 'to_status_id', 'name'];
}
