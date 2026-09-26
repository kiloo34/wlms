<?php

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\LogWorkInput;
use App\Modules\Workload\Application\UseCases\LogWorkUseCase;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
use App\Modules\Workload\Presentation\Http\Requests\LogWorkHttpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LogWorkController
{
    public function __construct(
        private LogWorkUseCase $useCase
    ) {
    }

    public function __invoke(string $id, LogWorkHttpRequest $request): JsonResponse
    {
        // Authorization: only the assignee may log work on the issue.
        $issue = IssueModel::find($id);

        if (!$issue) {
            abort(404, 'Issue not found.');
        }

        $currentUser = Auth::user();
        if ((string) $issue->assignee_id !== (string) $currentUser->id) {
            abort(403, 'Only the assignee can log work on this issue.');
        }

        $input = new LogWorkInput(
            issueId: $id,
            authorUserId: (string) Auth::id(),
            timeSpentSeconds: $request->validated('time_spent_seconds'),
            description: $request->validated('description'),
            startedAt: $request->validated('started_at')
        );

        $this->useCase->execute($input);

        return response()->json([
            'message' => 'Work logged successfully'
        ], 201);
    }
}
