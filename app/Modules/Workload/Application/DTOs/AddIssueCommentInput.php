<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\DTOs;

final readonly class AddIssueCommentInput
{
    public function __construct(
        public string $issueId,
        public string $authorId,
        public string $body,
    ) {
    }
}
