<?php
declare(strict_types=1);
namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class PriorityModel extends Model
{
    use HasUuids;
    protected $table = 'priorities';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id','name','slug','level','icon','color','is_active'];

    public function issues(): HasMany
    {
        return $this->hasMany(IssueModel::class, 'priority_id');
    }
}
