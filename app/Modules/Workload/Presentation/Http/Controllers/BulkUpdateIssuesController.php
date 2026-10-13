<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Workload\Application\DTOs\AssignIssueInput;
use App\Modules\Workload\Application\DTOs\TransitionIssueInput;
use App\Modules\Workload\Application\UseCases\AssignIssueUseCase;
use App\Modules\Workload\Application\UseCases\TransitionIssueStatusUseCase;
use App\Modules\Workload\Domain\Exceptions\InvalidTransitionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class BulkUpdateIssuesController
{
    public function __construct(
        private readonly AssignIssueUseCase $assignUseCase,
        private readonly TransitionIssueStatusUseCase $transitionUseCase,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'issue_ids' => ['required', 'array', 'min:1'],
            'issue_ids.*' => ['required', 'string'],
            'status_id' => ['nullable', 'string'],
            'assignee_id' => ['nullable'],
        ]);

        $user = $request->user();
        $actorId = (string) $user?->id;

        if (array_key_exists('assignee_id', $validated)) {
            if (! $user || ! ($user->hasRole('Workspace Owner') || $user->hasRole('Workspace Admin') || $user->hasRole('Superadmin') || $user->hasPermission('issues:assign'))) {
                abort(403, 'Unauthorized. Only Workspace Owners can assign issues for now.');
            }
        }

        try {
            DB::transaction(function () use ($validated, $actorId): void {
                foreach ($validated['issue_ids'] as $issueId) {
                    if (! empty($validated['status_id'])) {
                        $this->transitionUseCase->execute(new TransitionIssueInput(
                            issueId: (string) $issueId,
                            toStatusId: (string) $validated['status_id'],
                            actorUserId: $actorId,
                        ));
                    }

                    if (array_key_exists('assignee_id', $validated)) {
                        $assigneeId = $validated['assignee_id'];
                        $this->assignUseCase->execute(new AssignIssueInput(
                            issueId: (string) $issueId,
                            assigneeId: ($assigneeId !== null && $assigneeId !== 'unassigned') ? (string) $assigneeId : null,
                            actorUserId: $actorId,
                        ));
                    }
                }
            });
        } catch (InvalidTransitionException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'INVALID_TRANSITION',
            ], 422);
        }

        return response()->json(['message' => 'Issues updated successfully']);
    }
}

