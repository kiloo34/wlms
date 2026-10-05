<?php

declare(strict_types=1);

namespace App\Modules\Collaboration\Presentation\Http\Controllers;

use App\Modules\Collaboration\Application\DTOs\AddCommentInput;
use App\Modules\Collaboration\Application\UseCases\AddCommentToIssueUseCase;
use App\Modules\Collaboration\Presentation\Http\Requests\AddCommentHttpRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class AddCommentController extends Controller
{
    public function __construct(
        private readonly AddCommentToIssueUseCase $useCase
    ) {}

    public function __invoke(AddCommentHttpRequest $request, string $issueId): JsonResponse
    {
        $input = new AddCommentInput(
            commentId: Str::uuid()->toString(),
            issueId: $issueId,
            authorId: (string) $request->user()->id,
            body: $request->validated('body'),
            parentId: $request->validated('parent_id')
        );

        $output = $this->useCase->execute($input);

        return response()->json([
            'message' => 'Comment added successfully.',
            'data' => [
                'id' => $output->id,
                'issue_id' => $output->issueId,
                'author_id' => $output->authorId,
                'parent_id' => $output->parentId,
                'body' => $output->body,
                'is_edited' => $output->isEdited,
                'created_at' => $output->createdAt,
                'updated_at' => $output->updatedAt,
            ],
        ], 201);
    }
}
