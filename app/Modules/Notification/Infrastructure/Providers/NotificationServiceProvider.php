<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Providers;

use App\Modules\Notification\Infrastructure\Listeners\NotificationEventSubscriber;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::subscribe(NotificationEventSubscriber::class);
    }
}
