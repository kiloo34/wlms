<?php

namespace App\Modules\KnowledgeBase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property int $order
 * @property Collection<int, DocPageModel> $pages
 */
class DocCategoryModel extends Model
{
    use HasUuids;

    protected $table = 'doc_categories';

    protected $fillable = [
        'name',
        'slug',
        'order',
    ];

    protected $casts = [
        'id' => 'string',
        'order' => 'integer',
    ];

    /**
     * @return HasMany<DocPageModel, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(DocPageModel::class, 'category_id');
    }
}
