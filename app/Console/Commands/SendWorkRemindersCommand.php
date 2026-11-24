<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Modules\Workload\Infrastructure\Reminders\WorkReminderService;
use Illuminate\Console\Command;

/**
 * Kirim pengingat pekerjaan sesuai jadwal tiap tipe (harian 08:00, mingguan Senin,
 * bulanan tgl 1 & 15, tahunan 1 Nov, project/insidentil menjelang tenggat).
 * Dijadwalkan tiap 15 menit di routes/console.php; aman dijalankan berulang.
 */
final class SendWorkRemindersCommand extends Command
{
    protected $signature = 'workload:send-reminders {--user= : Hanya untuk satu user ID}';

    protected $description = 'Kirim pengingat pekerjaan (harian/mingguan/bulanan/tahunan/project/insidentil)';

    public function handle(WorkReminderService $service): int
    {
        $userId = $this->option('user');

        $sent = $userId !== null && $userId !== ''
            ? $service->runForUser((int) $userId)
            : $service->runForAll();

        $this->info("Pengingat terkirim: {$sent}");

        return self::SUCCESS;
    }
}
