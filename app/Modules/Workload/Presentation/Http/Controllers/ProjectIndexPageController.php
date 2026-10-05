<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\UseCases\GetProjectLookupsQuery;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Response;

final class ProjectIndexPageController extends Controller
{
    public function __construct(
        private readonly GetProjectLookupsQuery $getProjectLookupsQuery
    ) {}

    public function __invoke(Request $request): Response
    {
        $lookups = $this->getProjectLookupsQuery->getPrioritiesOnly();

        return inertia('Projects/Index', [
            'lookups' => $lookups,
        ]);
    }
}
