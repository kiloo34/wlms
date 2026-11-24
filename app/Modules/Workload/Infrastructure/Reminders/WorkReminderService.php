<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Reminders;

use App\Modules\Notification\Domain\Events\NewNotification;
use App\Modules\Notification\Infrastructure\Persistence\Eloquent\Models\NotificationModel;
use App\Modules\Workload\Application\Services\WorkSchedule;
use App\Modules\Workload\Domain\Enums\WorkType;
use App\Modules\Workload\Infrastructure\Config\ReminderSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mengirim pengingat pekerjaan sesuai tipe (harian, mingguan, bulanan, tahunan,
 * project, insidentil) ke owner tugas (assignee) dan owner project (lead).
 *
 * Idempoten: setiap pengingat dicatat di `work_reminder_logs`, jadi aman dipanggil
 * berulang kali dari scheduler maupun saat user membuka notifikasi.
 */
final class WorkReminderService
{
    /**
     * Tipe berulang yang jatuh jadwal pada tanggal tertentu.
     *
     * @param  array<string, mixed>  $config
     * @return list<WorkType>
     */
    public static function recurringTypesDueOn(CarbonImmutable $date, array $config): array
    {
        $types = [];

        if ($config['daily']['enabled']) {
            $types[] = WorkType::DAILY;
        }

        if ($config['weekly']['enabled'] && in_array($date->dayOfWeekIso, $config['weekly']['weekdays'], true)) {
            $types[] = WorkType::WEEKLY;
        }

        if ($config['monthly']['enabled']) {
            $byDate = in_array($date->day, $config['monthly']['days_of_month'], true);
            $byFirstMonday = $config['monthly']['first_monday'] && self::isFirstMondayOfMonth($date);
            if ($byDate || $byFirstMonday) {
                $types[] = WorkType::MONTHLY;
            }
        }

        if ($config['annually']['enabled']) {
            $month = (int) $config['annually']['month'];
            // Tanggal di luar panjang bulan (mis. 31 Nov) dipindah ke hari terakhir bulan itu.
            $day = min((int) $config['annually']['day'], $date->setDate($date->year, $month, 1)->daysInMonth);
            if ($date->month === $month && $date->day === $day) {
                $types[] = WorkType::ANNUALLY;
            }
        }

        return $types;
    }

    public static function isFirstMondayOfMonth(CarbonImmutable $date): bool
    {
        return $date->dayOfWeekIso === 1 && $date->day <= 7;
    }

    /**
     * Jalankan pengingat untuk semua user yang punya pekerjaan aktif.
     */
    public function runForAll(?CarbonImmutable $now = null): int
    {
        $assignees = DB::table('issues')
            ->join('statuses', 'statuses.id', '=', 'issues.status_id')
            ->whereNull('issues.deleted_at')
            ->whereNotNull('issues.assignee_id')
            ->where('statuses.category', '!=', 'DONE')
            ->distinct()
            ->pluck('issues.assignee_id');

        $leads = DB::table('projects')
            ->whereNull('deleted_at')
            ->where('status', 'ACTIVE')
            ->whereNotNull('lead_id')
            ->distinct()
            ->pluck('lead_id');

        $userIds = DB::table('users')
            ->whereIn('id', $assignees->merge($leads)->map(fn ($id): int => (int) $id)->unique()->values()->all())
            ->where('status', 'ACTIVE')
            ->pluck('id');

        $sent = 0;
        foreach ($userIds as $userId) {
            try {
                $sent += $this->runForUser((int) $userId, $now);
            } catch (Throwable $e) {
                Log::warning('Work reminder failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            }
        }

        return $sent;
    }

    /**
     * @return int jumlah notifikasi yang dibuat
     */
    public function runForUser(int $userId, ?CarbonImmutable $now = null): int
    {
        $config = ReminderSettings::forUser($userId);
        $now = ($now ?? CarbonImmutable::now())->setTimezone((string) $config['timezone']);

        [$hour, $minute] = array_map('intval', explode(':', (string) $config['time']));
        if ($now->lt($now->setTime($hour, $minute))) {
            return 0;
        }

        $today = $now->startOfDay();
        $todayStr = $today->toDateString();
        $sent = 0;

        foreach (self::recurringTypesDueOn($today, $config) as $type) {
            $sent += $this->remindRecurring($userId, $type, $todayStr);
        }

        foreach ([WorkType::PROJECT, WorkType::INCIDENTAL] as $type) {
            $typeConfig = $config[$type->value];
            if (! $typeConfig['enabled']) {
                continue;
            }

            $sent += $this->remindDeadlines($userId, $type, $typeConfig['days_before_due'], $today);
            if ($typeConfig['on_start_date']) {
                $sent += $this->remindStarts($userId, $type, $todayStr);
            }
        }

        if ($config['project']['enabled']) {
            $sent += $this->remindProjectOwner($userId, $config['project']['days_before_due'], $today);
        }

        return $sent;
    }

    private function remindRecurring(int $userId, WorkType $type, string $todayStr): int
    {
        $query = $this->openIssues($userId)->where('issues.work_type', $type->value);
        $count = (clone $query)->count();
        if ($count === 0 || ! $this->claim($userId, "{$type->value}:{$todayStr}")) {
            return 0;
        }

        $items = $query
            ->orderByRaw('CASE WHEN issues.due_date IS NULL THEN 1 ELSE 0 END, issues.due_date ASC')
            ->limit(5)
            ->get(['issues.id', 'issues.number', 'issues.title', 'issues.due_date', 'issues.project_id', 'projects.key as project_key'])
            ->map(fn ($row): array => [
                'issue_id' => (string) $row->id,
                'issue_number' => $row->project_key.'-'.$row->number,
                'title' => (string) $row->title,
                'project_id' => (string) $row->project_id,
                'due_date' => $row->due_date !== null ? substr((string) $row->due_date, 0, 10) : null,
            ])
            ->all();

        $this->notify($userId, 'reminder.work_type', [
            'work_type' => $type->value,
            'work_type_label' => $type->label(),
            'count' => $count,
            'date' => $todayStr,
            'items' => $items,
        ]);

        return 1;
    }

    /**
     * @param  list<int>  $daysBefore
     */
    private function remindDeadlines(int $userId, WorkType $type, array $daysBefore, CarbonImmutable $today): int
    {
        if ($daysBefore === []) {
            return 0;
        }

        $dates = array_map(fn (int $d): string => $today->addDays($d)->toDateString(), $daysBefore);
        $rows = $this->openIssues($userId)
            ->where('issues.work_type', $type->value)
            ->where(fn (Builder $q) => $this->whereDateIn($q, 'issues.due_date', $dates))
            ->get(['issues.id', 'issues.number', 'issues.title', 'issues.due_date', 'issues.project_id', 'projects.key as project_key']);

        $sent = 0;
        foreach ($rows as $row) {
            $due = substr((string) $row->due_date, 0, 10);
            if (! $this->claim($userId, "deadline:{$row->id}:{$today->toDateString()}")) {
                continue;
            }

            $this->notify($userId, 'reminder.deadline', [
                'work_type' => $type->value,
                'work_type_label' => $type->label(),
                'issue_id' => (string) $row->id,
                'issue_number' => $row->project_key.'-'.$row->number,
                'project_id' => (string) $row->project_id,
                'title' => (string) $row->title,
                'due_date' => $due,
                'days_left' => WorkSchedule::remainingDays($due, $today),
            ]);
            $sent++;
        }

        return $sent;
    }

    private function remindStarts(int $userId, WorkType $type, string $todayStr): int
    {
        $rows = $this->openIssues($userId)
            ->where('issues.work_type', $type->value)
            ->whereDate('issues.start_date', $todayStr)
            ->get(['issues.id', 'issues.number', 'issues.title', 'issues.due_date', 'issues.project_id', 'projects.key as project_key']);

        $sent = 0;
        foreach ($rows as $row) {
            if (! $this->claim($userId, "start:{$row->id}:{$todayStr}")) {
                continue;
            }

            $this->notify($userId, 'reminder.start', [
                'work_type' => $type->value,
                'work_type_label' => $type->label(),
                'issue_id' => (string) $row->id,
                'issue_number' => $row->project_key.'-'.$row->number,
                'project_id' => (string) $row->project_id,
                'title' => (string) $row->title,
                'due_date' => $row->due_date !== null ? substr((string) $row->due_date, 0, 10) : null,
            ]);
            $sent++;
        }

        return $sent;
    }

    /**
     * @param  list<int>  $daysBefore
     */
    private function remindProjectOwner(int $userId, array $daysBefore, CarbonImmutable $today): int
    {
        if ($daysBefore === []) {
            return 0;
        }

        $dates = array_map(fn (int $d): string => $today->addDays($d)->toDateString(), $daysBefore);
        $rows = DB::table('projects')
            ->where('lead_id', $userId)
            ->where('status', 'ACTIVE')
            ->whereNull('deleted_at')
            ->where(fn (Builder $q) => $this->whereDateIn($q, 'end_date', $dates))
            ->get(['id', 'key', 'name', 'end_date', 'workspace_id']);

        $sent = 0;
        foreach ($rows as $row) {
            if (! $this->claim($userId, "project:{$row->id}:{$today->toDateString()}")) {
                continue;
            }

            $end = substr((string) $row->end_date, 0, 10);
            $this->notify($userId, 'reminder.project_deadline', [
                'project_id' => (string) $row->id,
                'project_key' => (string) $row->key,
                'project_name' => (string) $row->name,
                'workspace_id' => (string) $row->workspace_id,
                'end_date' => $end,
                'days_left' => WorkSchedule::remainingDays($end, $today),
            ]);
            $sent++;
        }

        return $sent;
    }

    /**
     * Kolom tanggal bisa tersimpan sebagai 'Y-m-d' (pgsql) atau 'Y-m-d H:i:s' (sqlite),
     * jadi bandingkan per tanggal, bukan dengan whereIn mentah.
     *
     * @param  list<string>  $dates
     */
    private function whereDateIn(Builder $query, string $column, array $dates): void
    {
        foreach ($dates as $date) {
            $query->orWhereDate($column, $date);
        }
    }

    private function openIssues(int $userId): Builder
    {
        return DB::table('issues')
            ->join('statuses', 'statuses.id', '=', 'issues.status_id')
            ->join('projects', 'projects.id', '=', 'issues.project_id')
            ->where('issues.assignee_id', $userId)
            ->whereNull('issues.deleted_at')
            ->whereNull('projects.deleted_at')
            ->where('projects.status', '!=', 'ARCHIVED')
            ->where('statuses.category', '!=', 'DONE');
    }

    /**
     * Tandai pengingat sudah dikirim. false = sudah pernah dikirim sebelumnya.
     */
    private function claim(int $userId, string $key): bool
    {
        return DB::table('work_reminder_logs')->insertOrIgnore([
            'user_id' => $userId,
            'reminder_key' => mb_substr($key, 0, 191),
            'created_at' => now(),
        ]) > 0;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notify(int $userId, string $type, array $data): void
    {
        $notification = NotificationModel::create([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
        ]);

        try {
            broadcast(new NewNotification(
                id: $notification->id,
                userId: $userId,
                type: $type,
                data: $data,
                createdAt: $notification->created_at->toIso8601String(),
            ));
        } catch (Throwable $e) {
            // Notifikasi sudah tersimpan; kegagalan realtime cukup dicatat.
            Log::info('Reminder broadcast failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }
}
