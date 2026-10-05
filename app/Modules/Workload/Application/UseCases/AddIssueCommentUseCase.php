<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\AddIssueCommentInput;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueCommentModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AddIssueCommentUseCase
{
    public function execute(AddIssueCommentInput $input): IssueCommentModel
    {
        return DB::transaction(function () use ($input) {
            return IssueCommentModel::create([
                'id' => Str::uuid()->toString(),
                'issue_id' => $input->issueId,
                'author_id' => $input->authorId,
                'body' => $input->body,
            ]);
        });
    }
}
