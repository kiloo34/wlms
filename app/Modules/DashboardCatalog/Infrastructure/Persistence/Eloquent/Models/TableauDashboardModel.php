<?php

declare(strict_types=1);

namespace App\Modules\DashboardCatalog\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Katalog Dashboard Tableau.
 *
 * Satu baris = satu dashboard yang terdaftar di Tableau Server, lengkap dengan
 * definisi, project, mapping bisnis, user permission, user mapping dan owner.
 */
class TableauDashboardModel extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $table = 'tableau_dashboards';

    protected $fillable = [
        'number',
        'name',
        'definition',
        'contents',
        'purpose',
        'tableau_project',
        'mapping',
        'user_permission',
        'user_mapping',
        'owner',
        'notes',
    ];

    protected $casts = [
        'id' => 'string',
        'number' => 'integer',
    ];
}
