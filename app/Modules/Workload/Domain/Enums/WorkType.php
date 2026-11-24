<?php

declare(strict_types=1);

namespace App\Modules\Workload\Domain\Enums;

/**
 * Tipe pekerjaan / jobdesc sebuah issue. Menentukan jadwal pengingat default.
 */
enum WorkType: string
{
    case DAILY = 'daily';
    case WEEKLY = 'weekly';
    case MONTHLY = 'monthly';
    case ANNUALLY = 'annually';
    case INCIDENTAL = 'incidental';
    case PROJECT = 'project';

    public const DEFAULT = self::PROJECT;

    public function label(): string
    {
        return match ($this) {
            self::DAILY => 'Harian',
            self::WEEKLY => 'Mingguan',
            self::MONTHLY => 'Bulanan',
            self::ANNUALLY => 'Tahunan',
            self::INCIDENTAL => 'Insidentil',
            self::PROJECT => 'Project',
        };
    }

    /**
     * Tipe berulang diingatkan berdasarkan kalender (jam/hari/tanggal tertentu),
     * sedangkan project & insidentil diingatkan berdasarkan tenggat masing-masing.
     */
    public function isRecurring(): bool
    {
        return in_array($this, [self::DAILY, self::WEEKLY, self::MONTHLY, self::ANNUALLY], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }

    public static function labelFor(?string $value): string
    {
        return (self::tryFrom((string) $value) ?? self::DEFAULT)->label();
    }
}
