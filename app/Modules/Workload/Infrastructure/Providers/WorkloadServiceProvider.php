<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Providers;

use App\Modules\Workload\Domain\Config\WorkspaceSettingsInterface;
use App\Modules\Workload\Domain\Events\WorkspaceCreated;
use App\Modules\Workload\Domain\Repositories\WorkspaceRepositoryInterface;
use App\Modules\Workload\Infrastructure\Config\WorkspaceSettings;
use App\Modules\Workload\Infrastructure\Listeners\WriteWorkspaceAuditLogListener;
use App\Modules\Workload\Infrastructure\Persistence\Repositories\EloquentWorkspaceRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class WorkloadServiceProvider extends ServiceProvider
{
    /**
     * Register any module services.
     */
    public function register(): void
    {
        // Bind Repository
        $this->app->bind(\App\Modules\Workload\Domain\Repositories\WorkspaceRepositoryInterface::class, \App\Modules\Workload\Infrastructure\Persistence\Repositories\EloquentWorkspaceRepository::class);
        $this->app->bind(\App\Modules\Workload\Domain\Repositories\ProjectRepositoryInterface::class, \App\Modules\Workload\Infrastructure\Persistence\Repositories\EloquentProjectRepository::class);
        $this->app->bind(\App\Modules\Workload\Domain\Repositories\SprintRepositoryInterface::class, \App\Modules\Workload\Infrastructure\Persistence\Repositories\EloquentSprintRepository::class);
        $this->app->bind(\App\Modules\Workload\Domain\Repositories\IssueRepositoryInterface::class, \App\Modules\Workload\Infrastructure\Persistence\Repositories\EloquentIssueRepository::class);
        $this->app->bind(\App\Modules\Workload\Domain\Repositories\WorkflowRepositoryInterface::class, \App\Modules\Workload\Infrastructure\Persistence\Repositories\EloquentWorkflowRepository::class);
        $this->app->bind(\App\Modules\Workload\Domain\Repositories\StatusRepositoryInterface::class, \App\Modules\Workload\Infrastructure\Persistence\Repositories\EloquentStatusRepository::class);
        $this->app->bind(WorkspaceSettingsInterface::class, WorkspaceSettings::class);
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Gate::policy(
            \App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel::class,
            \App\Modules\Workload\Infrastructure\Auth\Policies\WorkspacePolicy::class
        );

        // Workspace Events
        Event::listen(WorkspaceCreated::class, WriteWorkspaceAuditLogListener::class);

        // Issue Events
        Event::listen(\App\Modules\Workload\Domain\Events\IssueTransitioned::class, \App\Modules\Workload\Infrastructure\Listeners\WriteIssueAuditLogListener::class);
        Event::listen(\App\Modules\Workload\Domain\Events\IssueAssigned::class, \App\Modules\Workload\Infrastructure\Listeners\WriteIssueAuditLogListener::class);
        // Event::listen(\App\Modules\Workload\Domain\Events\IssueAssigned::class, \App\Modules\Workload\Infrastructure\Listeners\NotifyAssigneeMailListener::class);
        // Event::listen(\App\Modules\Workload\Domain\Events\IssueAssigned::class, \App\Modules\Workload\Infrastructure\Listeners\WriteInAppNotificationListener::class);
    }
}
