<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Exceptions;

use Exception;

final class InvalidTransitionException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?string $fromStatusId = null,
        public readonly ?string $toStatusId = null
    ) {
        parent::__construct($message);
    }
}
