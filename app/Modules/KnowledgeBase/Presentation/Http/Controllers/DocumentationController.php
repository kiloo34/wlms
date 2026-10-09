<?php

namespace App\Modules\KnowledgeBase\Presentation\Http\Controllers;

use App\Modules\KnowledgeBase\Application\Queries\GetDocPageBySlugQuery;
use App\Modules\KnowledgeBase\Application\Queries\GetDocumentationMenuQuery;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class DocumentationController
{
    public function show(
        GetDocumentationMenuQuery $menuQuery,
        GetDocPageBySlugQuery $pageQuery,
        ?string $slug = null
    ): Response|RedirectResponse {
        $menu = $menuQuery->execute();

        if ($slug === null) {
            $firstPage = $menu->flatMap->pages->first();

            if ($firstPage) {
                return redirect()->route('docs.show', ['slug' => $firstPage->slug]);
            }
        }

        $page = $slug ? $pageQuery->execute($slug) : null;

        if ($slug && ! $page) {
            abort(404, 'Documentation page not found.');
        }

        return inertia('Docs/Index', [
            'page' => $page,
            'menu' => $menu,
        ]);
    }
}
