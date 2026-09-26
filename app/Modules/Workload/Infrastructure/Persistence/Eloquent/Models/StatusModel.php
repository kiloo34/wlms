<?php
declare(strict_types=1);
namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class StatusModel extends Model
{
    use HasUuids;
    protected $table = 'statuses';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id','name','slug','category','color'];

    public function issues(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(IssueModel::class, 'status_id');
    }
}
