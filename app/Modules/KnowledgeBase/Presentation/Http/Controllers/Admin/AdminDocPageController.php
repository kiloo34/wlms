<?php

namespace App\Modules\KnowledgeBase\Presentation\Http\Controllers\Admin;

use App\Modules\KnowledgeBase\Application\UseCases\DocPageAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class AdminDocPageController
{
    public function __construct(
        private readonly DocPageAdminService $docPageAdminService
    ) {}

    public function index(): Response
    {
        return inertia('Admin/Docs/Index', [
            'pages' => $this->docPageAdminService->listAllPages(),
        ]);
    }

    public function edit(string $id): Response
    {
        return inertia('Admin/Docs/Edit', [
            'page' => $this->docPageAdminService->findPageOrFail($id),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $validated = $request->validate([
            'content' => 'required|array',
        ]);

        $this->docPageAdminService->updatePageContent($id, $validated['content']);

        return back()->with('success', 'Tersimpan');
    }
}
