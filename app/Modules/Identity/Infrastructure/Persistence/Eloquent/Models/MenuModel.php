<?php

namespace App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $parent_id
 * @property string $label
 * @property string $key
 * @property string|null $route
 * @property string|null $icon
 * @property int|null $sort_order
 * @property bool $is_active
 */
class MenuModel extends Model
{
    use HasUuids;

    protected $table = 'menus';

    protected $fillable = [
        'parent_id',
        'label',
        'key',
        'route',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
