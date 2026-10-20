<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Config;

use App\Modules\Workload\Domain\Config\WorkspaceSettingsInterface;
use Illuminate\Support\Facades\DB;

final class WorkspaceSettings implements WorkspaceSettingsInterface
{
    /**
     * Mengambil nilai maksimal workspace per group dari tabel org_units atau fallback ke settings.
     */
    public function getMaxWorkspacesPerGroup(?string $groupId = null): int
    {
        if ($groupId) {
            $unit = DB::table('org_units')->where('id', $groupId)->first(['max_workspaces']);
            if ($unit !== null && $unit->max_workspaces !== null) {
                return (int) $unit->max_workspaces;
            }
        }

        $setting = DB::table('settings')->where('key', 'workload.max_workspaces_per_group')->first();

        if (! $setting) {
            return 10;
        }

        $value = json_decode((string) $setting->value, true);

        return is_numeric($value) ? (int) $value : 10;
    }
}
