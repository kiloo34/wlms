<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Config;

use App\Modules\Workload\Domain\Config\WorkspaceSettingsInterface;
use Illuminate\Support\Facades\DB;

final class WorkspaceSettings implements WorkspaceSettingsInterface
{
    /**
     * Mengambil nilai maksimal workspace per group dari database.
     * Jika tidak ditemukan, default ke 10.
     */
    public function getMaxWorkspacesPerGroup(): int
    {
        $setting = DB::table('settings')->where('key', 'workload.max_workspaces_per_group')->first();
        
        if (!$setting) {
            return 10;
        }

        $value = json_decode($setting->value, true);
        return is_numeric($value) ? (int) $value : 10;
    }
}
