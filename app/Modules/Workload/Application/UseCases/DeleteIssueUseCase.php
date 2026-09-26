<?php
declare(strict_types=1);

namespace App\Modules\Workload\Application\UseCases;

use App\Modules\Workload\Domain\Repositories\IssueRepositoryInterface;
use App\Modules\Workload\Domain\ValueObjects\IssueId;
use Illuminate\Support\Facades\DB;
use Exception;

final class DeleteIssueUseCase
{
    public function __construct(
        private readonly IssueRepositoryInterface $issueRepository
    ) {}

    public function execute(string $issueId): void
    {
        DB::transaction(function () use ($issueId) {
            $id = new IssueId($issueId);
            $issue = $this->issueRepository->findById($id);
            if (!$issue) {
                throw new Exception("Issue not found.");
            }

            $this->issueRepository->delete($id);
        });
    }
}
