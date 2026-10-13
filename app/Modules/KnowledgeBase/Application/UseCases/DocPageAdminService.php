<?php

declare(strict_types=1);

namespace App\Modules\KnowledgeBase\Application\UseCases;

use App\Modules\KnowledgeBase\Infrastructure\Persistence\Eloquent\Models\DocPageModel;
use Illuminate\Database\Eloquent\Collection;

class DocPageAdminService
{
    /**
     * @return Collection<int, DocPageModel>
     */
    public function listAllPages(): Collection
    {
        return DocPageModel::with('category')->orderBy('order')->get();
    }

    public function findPageOrFail(string $id): DocPageModel
    {
        /** @var DocPageModel */
        return DocPageModel::findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public function updatePageContent(string $id, array $content): void
    {
        /** @var DocPageModel $page */
        $page = DocPageModel::findOrFail($id);
        $page->update(['content' => $content]);
    }
}

