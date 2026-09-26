<?php
declare(strict_types=1);
namespace App\Modules\Collaboration\Presentation\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use App\Modules\Collaboration\Application\UseCases\GetIssueTimelineQuery;

class GetIssueTimelineController extends Controller
{
    public function __construct(
        private readonly GetIssueTimelineQuery $query
    ) {}

    public function __invoke(string $issueId): JsonResponse
    {
        $timeline = $this->query->execute($issueId);

        return response()->json([
            'data' => $timeline
        ]);
    }
}

