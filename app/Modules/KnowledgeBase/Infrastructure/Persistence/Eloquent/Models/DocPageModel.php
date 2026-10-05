<?php

namespace App\Modules\KnowledgeBase\Infrastructure\Persistence\Eloquent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocPageModel extends Model
{
    use HasUuids;

    protected $table = 'doc_pages';

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'content',
        'order',
        'is_published',
    ];

    protected $casts = [
        'id' => 'string',
        'category_id' => 'string',
        'order' => 'integer',
        'is_published' => 'boolean',
        'content' => 'array',
    ];

    /**
     * @return BelongsTo<DocCategoryModel, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocCategoryModel::class, 'category_id');
    }
}
