<?php
declare(strict_types=1);
namespace App\Modules\Workload\Domain\Exceptions;
use Exception;
final class WorkspaceCreationException extends Exception
{
    public static function limitReached(string $groupId, int $limit): self
    {
        return new self("Workspace limit of {$limit} reached for group '{$groupId}'.");
    }
}
