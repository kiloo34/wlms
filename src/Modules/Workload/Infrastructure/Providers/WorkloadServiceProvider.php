<?php

namespace Modules\Workload\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;

class WorkloadServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind Domain interfaces to Infrastructure implementations
        // $this->app->bind(WorkloadRepositoryInterface::class, EloquentWorkloadRepository::class);
    }

    public function boot(): void
    {
        // Load routes, migrations, views etc.
        // $this->loadRoutesFrom(__DIR__ . '/../../Presentation/Routes/api.php');
        // $this->loadMigrationsFrom(__DIR__ . '/../../Infrastructure/Database/Migrations');
    }
}
