<?php
declare(strict_types=1);
namespace App\Modules\Collaboration\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AuditLogModel extends Model
{
    use HasUuids;

    protected $table = 'audit_logs';

    public $timestamps = false; // append-only, handled via created_at fillable or default

    protected $fillable = [
        'id',
        'actor_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'created_at'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];
}

