<?php
declare(strict_types=1);
namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class IssueTypeModel extends Model
{
    use HasUuids;
    protected $table = 'issue_types';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id','name','slug','icon','color','sort_order','is_active'];

    public function issues(): HasMany
    {
        return $this->hasMany(IssueModel::class, 'issue_type_id');
    }
}
