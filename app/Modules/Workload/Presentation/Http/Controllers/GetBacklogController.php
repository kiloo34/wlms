<?php
declare(strict_types=1);
namespace App\Modules\Workload\Presentation\Http\Controllers;
use App\Modules\Workload\Application\UseCases\GetBacklogIssuesQuery;
use Illuminate\Http\JsonResponse;
final class GetBacklogController
{
    public function __construct(private readonly GetBacklogIssuesQuery $query) {}
    public function __invoke(string $projectId): JsonResponse
    {
        return response()->json(['data' => $this->query->execute($projectId)]);
    }
}
