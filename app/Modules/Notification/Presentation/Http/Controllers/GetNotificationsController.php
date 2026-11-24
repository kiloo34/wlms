<?php

declare(strict_types=1);

namespace App\Modules\Notification\Presentation\Http\Controllers;

use App\Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationModel;
use App\Modules\Workload\Infrastructure\Config\WorkloadSettings;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\IssueModel;
use App\Modules\Workload\Infrastructure\Reminders\WorkReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class GetNotificationsController
{
    public function __invoke(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $this->triggerLazySlaNotifications($userId);
        $this->triggerLazyWorkReminders($userId);

        /** @var LengthAwarePaginator<int, NotificationModel> $paginator */
        $paginator = NotificationModel::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->paginate(20);

        $unreadCount = NotificationModel::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Pengingat per tipe pekerjaan (harian/mingguan/...). Scheduler `workload:send-reminders`
     * adalah jalur utama; ini cadangan agar pengingat tetap muncul walau cron tidak berjalan.
     * Aman dipanggil berulang karena setiap pengingat dicatat di work_reminder_logs.
     */
    private function triggerLazyWorkReminders(int $userId): void
    {
        if (! Cache::add("work_reminders_lazy:{$userId}", true, now()->addMinutes(10))) {
            return;
        }

        try {
            app(WorkReminderService::class)->runForUser($userId);
        } catch (Throwable $e) {
            Log::warning('Lazy work reminder failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Lazy daily SLA evaluation triggered on user activity (zero cronjob / zero cloud worker cost).
     */
    private function triggerLazySlaNotifications(int $userId): void
    {
        $today = now()->toDateString();
        $cacheKey = "sla_lazy_notify:{$userId}:{$today}";

        if (! Cache::add($cacheKey, true, now()->endOfDay())) {
            return;
        }

        $warningLimitDate = now()->addDays(WorkloadSettings::getSlaWarningDays())->toDateString();

        $issues = IssueModel::with(['project:id,key,name', 'status:id,category'])
            ->where('assignee_id', $userId)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $warningLimitDate)
            ->whereHas('status', fn ($q) => $q->where('category', '!=', 'DONE'))
            ->get();

        foreach ($issues as $issue) {
            $dueDateStr = $issue->due_date?->format('Y-m-d');
            if (! $dueDateStr) {
                continue;
            }

            $type = $dueDateStr < $today ? 'issue.overdue' : 'issue.due_soon';
            $issueKey = $issue->project ? "{$issue->project->key}-{$issue->number}" : (string) $issue->number;

            $alreadyNotifiedToday = NotificationModel::where('user_id', $userId)
                ->where('type', $type)
                ->whereDate('created_at', $today)
                ->where('data->issue_id', (string) $issue->id)
                ->exists();

            if (! $alreadyNotifiedToday) {
                NotificationModel::create([
                    'id' => (string) Str::uuid(),
                    'user_id' => $userId,
                    'type' => $type,
                    'data' => [
                        'issue_id' => (string) $issue->id,
                        'project_id' => (string) $issue->project_id,
                        'issue_number' => $issueKey,
                        'title' => $issue->title,
                        'due_date' => $dueDateStr,
                    ],
                ]);
            }
        }
    }
}
