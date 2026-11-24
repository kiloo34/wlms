<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Throwable;

/**
 * Perhitungan jadwal sederhana untuk project & issue.
 *
 * Durasi dihitung dalam hari kalender dan bersifat inklusif:
 * mulai 1 Des + durasi 1 hari = selesai 1 Des; durasi 5 hari = selesai 5 Des.
 */
final class WorkSchedule
{
    public static function endDateFromDuration(?string $startDate, ?int $durationDays): ?string
    {
        $start = self::parse($startDate);
        if ($start === null || $durationDays === null || $durationDays < 1) {
            return null;
        }

        return $start->addDays($durationDays - 1)->toDateString();
    }

    public static function durationDays(DateTimeInterface|string|null $startDate, DateTimeInterface|string|null $endDate): ?int
    {
        $start = self::parse($startDate);
        $end = self::parse($endDate);
        if ($start === null || $end === null || $end->lt($start)) {
            return null;
        }

        return (int) $start->diffInDays($end) + 1;
    }

    /**
     * Sisa hari sampai tanggal selesai (negatif = lewat tenggat, 0 = hari ini).
     * `$today` sebaiknya ReminderSettings::today() agar mengikuti zona waktu organisasi.
     */
    public static function remainingDays(DateTimeInterface|string|null $endDate, CarbonImmutable $today): ?int
    {
        $end = self::parse($endDate);
        if ($end === null) {
            return null;
        }

        $today = $today->startOfDay();
        $endDay = $today->setDate($end->year, $end->month, $end->day);

        return (int) $today->diffInDays($endDay, false);
    }

    public static function toDateString(DateTimeInterface|string|null $value): ?string
    {
        return self::parse($value)?->toDateString();
    }

    private static function parse(DateTimeInterface|string|null $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $parsed = $value instanceof DateTimeInterface
                ? CarbonImmutable::instance($value)
                : CarbonImmutable::parse(substr($value, 0, 10));

            return $parsed->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
