<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Queries;

use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueCommentModel;
use Illuminate\Database\Eloquent\Collection;

final class GetIssueCommentsQuery
{
    /**
     * @return Collection<int, IssueCommentModel>
     */
    public function execute(string $issueId): Collection
    {
        return IssueCommentModel::with(['author'])
            ->where('issue_id', $issueId)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
