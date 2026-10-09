<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\Jobs\ImportIssuesJob;
use App\Modules\Workload\Application\Jobs\ImportProjectsJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Response;
use InvalidArgumentException;

final class ImportWorkloadController extends Controller
{
    public function index(): Response
    {
        return inertia('Admin/Import/Index');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:projects,issues'],
            'file' => ['required', 'file', 'mimes:csv,txt,xls,xlsx'],
            'workspace_id' => ['required_if:type,projects', 'nullable', 'string', 'uuid'],
        ]);

        $file = $request->file('file');
        if (! $file) {
            throw new InvalidArgumentException('File is required');
        }

        $filePath = $file->store('imports', 'local');
        if (! is_string($filePath)) {
            throw new InvalidArgumentException('Failed to store file');
        }

        if ($validated['type'] === 'projects') {
            ImportProjectsJob::dispatch($filePath, (string) $validated['workspace_id']);
        } elseif ($validated['type'] === 'issues') {
            ImportIssuesJob::dispatch($filePath, (string) $request->user()?->id);
        }

        return back()->with('success', 'Import started successfully');
    }
}
