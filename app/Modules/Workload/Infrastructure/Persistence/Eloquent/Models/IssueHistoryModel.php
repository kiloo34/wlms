<?php
declare(strict_types=1);
namespace App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class IssueHistoryModel extends Model
{
    use HasUuids;
    protected $table = 'issue_histories';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
    protected $fillable = ['id','issue_id','actor_id','field_changed','old_value','new_value','created_at'];
    protected $casts = ['created_at' => 'datetime'];

    public function issue(): BelongsTo
    {
        return $this->belongsTo(IssueModel::class, 'issue_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(UserModel::class, 'actor_id');
    }
}
