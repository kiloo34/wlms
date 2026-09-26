<?php

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Application\DTOs\LogWorkInput;
use App\Modules\Workload\Domain\Repositories\IssueRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\IssueId;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorklogModel;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LogWorkUseCase
{
    public function __construct(
        private IssueRepositoryInterface $issueRepository
    ) {
    }

    public function execute(LogWorkInput $input): void
    {
        DB::transaction(function () use ($input) {
            $issue = $this->issueRepository->findById(new IssueId($input->issueId));
            
            if (!$issue) {
                throw new InvalidArgumentException("Issue not found");
            }

            WorklogModel::create([
                'issue_id' => $input->issueId,
                'author_id' => $input->authorUserId,
                'time_spent_seconds' => $input->timeSpentSeconds,
                'description' => $input->description,
                'started_at' => $input->startedAt,
            ]);

            // Decrement remaining_estimate_seconds
            $issueModel = \App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel::find($input->issueId);
            if ($issueModel && $issueModel->remaining_estimate_seconds !== null) {
                $newRemaining = max(0, $issueModel->remaining_estimate_seconds - $input->timeSpentSeconds);
                $issueModel->update(['remaining_estimate_seconds' => $newRemaining]);
            }
        });
    }
}
