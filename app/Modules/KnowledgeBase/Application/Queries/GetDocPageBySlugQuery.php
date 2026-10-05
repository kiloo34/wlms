<?php

namespace App\Modules\KnowledgeBase\Application\Queries;

use App\Modules\KnowledgeBase\Infrastructure\Persistence\Eloquent\Models\DocPageModel;

class GetDocPageBySlugQuery
{
    /**
     * Get a document page by its slug.
     */
    public function execute(string $slug): ?DocPageModel
    {
        return DocPageModel::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();
    }
}
