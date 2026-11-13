<?php

declare(strict_types=1);

namespace App\Modules\DashboardCatalog\Presentation\Http\Controllers;

use App\Modules\DashboardCatalog\Application\UseCases\TableauDashboardService;
use App\Modules\DashboardCatalog\Presentation\Http\Requests\TableauDashboardRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;

final class TableauDashboardController
{
    public function __construct(
        private readonly TableauDashboardService $service
    ) {}

    public function index(): Response
    {
        return inertia('Dashboards/Index', [
            'dashboards' => $this->service->listAll(),
            'options' => $this->service->listOptions(),
            'nextNumber' => $this->service->nextNumber(),
            'can' => [
                'create' => Gate::allows('dashboards.create'),
                'edit' => Gate::allows('dashboards.edit'),
                'delete' => Gate::allows('dashboards.delete'),
            ],
        ]);
    }

    public function store(TableauDashboardRequest $request): RedirectResponse
    {
        $dashboard = $this->service->create($request->validated());

        return back()->with('success', "Dashboard \"{$dashboard->name}\" berhasil dicatat.");
    }

    public function update(TableauDashboardRequest $request, string $id): RedirectResponse
    {
        $dashboard = $this->service->update($id, $request->validated());

        return back()->with('success', "Dashboard \"{$dashboard->name}\" berhasil diperbarui.");
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->service->delete($id);

        return back()->with('success', 'Dashboard berhasil dihapus dari katalog.');
    }
}
