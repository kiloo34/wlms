<?php

declare(strict_types=1);

namespace App\Modules\DashboardCatalog\Infrastructure\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Gate untuk Katalog Dashboard.
 *
 * Superadmin selalu lolos (Gate manage-rbac). Role lain membutuhkan permission
 * dashboards:view / dashboards:create / dashboards:edit / dashboards:delete
 * (atau dashboards:manage yang mencakup semuanya) dari modul RBAC.
 */
class DashboardCatalogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            Gate::define("dashboards.{$action}", function (Authenticatable $user) use ($action): bool {
                if (Gate::forUser($user)->allows('manage-rbac')) {
                    return true;
                }

                // Katalog bersifat global: izin dari role yang terikat workspace/project ikut dihitung
                return method_exists($user, 'hasPermissionInAnyContext')
                    && $user->hasPermissionInAnyContext("dashboards:{$action}");
            });
        }
    }
}
