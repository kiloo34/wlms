<?php

namespace App\Modules\KnowledgeBase\Application\Queries;

use App\Modules\KnowledgeBase\Infrastructure\Persistence\Eloquent\Models\DocCategoryModel;
use Illuminate\Database\Eloquent\Collection;

class GetDocumentationMenuQuery
{
    /**
     * Get all categories with their published pages.
     *
     * @return Collection<int, DocCategoryModel>
     */
    public function execute(): Collection
    {
        return DocCategoryModel::query()
            ->select(['id', 'name', 'slug', 'order'])
            ->with(['pages' => function ($query) {
                $query->select(['id', 'category_id', 'title', 'slug', 'order'])
                    ->where('is_published', true)
                    ->orderBy('order');
            }])
            ->orderBy('order')
            ->get();
    }
}
