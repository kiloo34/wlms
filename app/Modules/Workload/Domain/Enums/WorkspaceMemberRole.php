<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Enums;

enum WorkspaceMemberRole: string
{
    case ADMIN = 'admin';
    case MEMBER = 'member';
    case VIEWER = 'viewer';
    case OWNER = 'owner';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
