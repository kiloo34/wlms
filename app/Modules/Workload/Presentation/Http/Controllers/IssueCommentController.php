<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Controllers;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Workload\Application\DTOs\AddIssueCommentInput;
use App\Modules\Workload\Application\Queries\GetIssueCommentsQuery;
use App\Modules\Workload\Application\UseCases\AddIssueCommentUseCase;
use App\Modules\Workload\Presentation\Http\Requests\AddIssueCommentHttpRequest;
use App\Shared\Presentation\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class IssueCommentController extends Controller
{
    public function index(Request $request, string $issueId, GetIssueCommentsQuery $query): JsonResponse
    {
        /** @var UserModel|null $user */
        $user = $request->user();
        if (! $user || ! $user->hasPermission('issues:view')) {
            abort(403, 'Unauthorized.');
        }

        $comments = $query->execute($issueId);

        return response()->json([
            'data' => $comments,
        ]);
    }

    public function store(
        string $issueId,
        AddIssueCommentHttpRequest $request,
        AddIssueCommentUseCase $useCase
    ): JsonResponse {
        $input = new AddIssueCommentInput(
            issueId: $issueId,
            authorId: (string) $request->user()->id, // Assuming standard auth
            body: $request->validated('body'),
        );

        $comment = $useCase->execute($input);

        return response()->json([
            'message' => 'Comment added successfully.',
            'data' => $comment,
        ], 201);
    }
}
