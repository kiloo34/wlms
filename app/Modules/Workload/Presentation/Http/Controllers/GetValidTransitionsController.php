<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\UseCases\GetValidTransitionsQuery;
use Illuminate\Http\JsonResponse;

final class GetValidTransitionsController
{
    public function __construct(private readonly GetValidTransitionsQuery $query) {}

    public function __invoke(string $issueId): JsonResponse
    {
        return response()->json(['data' => $this->query->execute($issueId)]);
    }
}
