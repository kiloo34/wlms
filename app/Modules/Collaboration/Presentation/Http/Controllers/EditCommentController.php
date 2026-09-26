<?php
declare(strict_types=1);
namespace App\Modules\Collaboration\Presentation\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;
use App\Modules\Collaboration\Presentation\Http\Requests\EditCommentHttpRequest;
use App\Modules\Collaboration\Application\UseCases\EditCommentUseCase;
use App\Modules\Collaboration\Application\DTOs\EditCommentInput;

class EditCommentController extends Controller
{
    public function __construct(
        private readonly EditCommentUseCase $useCase
    ) {}

    public function __invoke(EditCommentHttpRequest $request, string $issueId, string $commentId): JsonResponse
    {
        $input = new EditCommentInput(
            commentId: $commentId,
            authorId: (string) $request->user()->id,
            body: $request->validated('body')
        );

        $output = $this->useCase->execute($input);

        return response()->json([
            'message' => 'Comment edited successfully.',
            'data' => [
                'id' => $output->id,
                'issue_id' => $output->issueId,
                'author_id' => $output->authorId,
                'parent_id' => $output->parentId,
                'body' => $output->body,
                'is_edited' => $output->isEdited,
                'created_at' => $output->createdAt,
                'updated_at' => $output->updatedAt,
            ]
        ]);
    }
}
