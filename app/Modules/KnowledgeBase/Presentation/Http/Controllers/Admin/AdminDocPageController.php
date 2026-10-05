<?php

namespace App\Modules\KnowledgeBase\Presentation\Http\Controllers\Admin;

use App\Modules\KnowledgeBase\Infrastructure\Persistence\Eloquent\Models\DocPageModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class AdminDocPageController
{
    public function index(): Response
    {
        $pages = DocPageModel::with('category')->orderBy('order')->get();

        return inertia('Admin/Docs/Index', ['pages' => $pages]);
    }

    public function edit(string $id): Response
    {
        $page = DocPageModel::findOrFail($id);

        return inertia('Admin/Docs/Edit', [
            'page' => $page,
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'content' => 'required|array',
        ]);

        /** @var DocPageModel $page */
        $page = DocPageModel::findOrFail($id);
        $page->update([
            'content' => $request->input('content'),
        ]);

        return back()->with('success', 'Tersimpan');
    }
}
