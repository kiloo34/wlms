<?php
declare(strict_types=1);
namespace App\Modules\Collaboration\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\Collaboration\Domain\Repositories\CommentRepositoryInterface;
use App\Modules\Collaboration\Domain\Repositories\AuditLogRepositoryInterface;
use App\Modules\Collaboration\Infrastructure\Persistence\Repositories\EloquentCommentRepository;
use App\Modules\Collaboration\Infrastructure\Persistence\Repositories\EloquentAuditLogRepository;
use App\Modules\Collaboration\Infrastructure\Listeners\WorkloadEventSubscriber;
use Illuminate\Support\Facades\Event;

class CollaborationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(CommentRepositoryInterface::class, EloquentCommentRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, EloquentAuditLogRepository::class);
    }

    public function boot(): void
    {
        Event::subscribe(WorkloadEventSubscriber::class);
    }
}

