<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Config;

use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;

/**
 * Jadwal pengingat per tipe pekerjaan.
 *
 * Ada dua lapis konfigurasi yang disimpan di tabel `settings`:
 * - default organisasi (`workload.reminders`), diatur admin;
 * - override per user (`user.{id}.reminders`), diatur masing-masing user.
 * Bila belum pernah diatur, dipakai DEFAULTS di bawah.
 */
final class ReminderSettings
{
    public const GLOBAL_KEY = 'workload.reminders';

    public const DEFAULTS = [
        'timezone' => 'Asia/Jakarta',
        // Jam pengiriman semua pengingat (format HH:MM, zona waktu di atas).
        'time' => '08:00',
        // Harian: setiap hari.
        'daily' => ['enabled' => true],
        // Mingguan: hari ISO (1 = Senin ... 7 = Minggu).
        'weekly' => ['enabled' => true, 'weekdays' => [1]],
        // Bulanan: tanggal 1 & 15, opsional juga Senin pertama bulan itu.
        'monthly' => ['enabled' => true, 'days_of_month' => [1, 15], 'first_monday' => false],
        // Tahunan: 2 bulan menjelang akhir tahun (1 November).
        'annually' => ['enabled' => true, 'month' => 11, 'day' => 1],
        // Project & insidentil: menyesuaikan tenggat masing-masing tugas.
        'project' => ['enabled' => true, 'days_before_due' => [7, 3], 'on_start_date' => true],
        'incidental' => ['enabled' => true, 'days_before_due' => [3], 'on_start_date' => true],
    ];

    public static function userKey(int|string $userId): string
    {
        return "user.{$userId}.reminders";
    }

    /**
     * @return array<string, mixed>
     */
    public static function global(): array
    {
        return self::normalize(self::merge(self::DEFAULTS, self::read(self::GLOBAL_KEY)));
    }

    /**
     * Konfigurasi efektif untuk satu user (default organisasi + override user).
     * Zona waktu selalu mengikuti default organisasi.
     *
     * @return array<string, mixed>
     */
    public static function forUser(int|string $userId): array
    {
        $global = self::global();
        $effective = self::normalize(self::merge($global, self::read(self::userKey($userId))));
        $effective['timezone'] = $global['timezone'];

        return $effective;
    }

    public static function hasUserOverride(int|string $userId): bool
    {
        return DB::table('settings')->where('key', self::userKey($userId))->exists();
    }

    /**
     * Dipanggil per baris di daftar issue/report, jadi dimemoize per request.
     */
    public static function timezone(): string
    {
        return once(fn (): string => (string) self::global()['timezone']);
    }

    /**
     * Tanggal hari ini menurut zona waktu organisasi (bukan zona waktu server/UTC).
     */
    public static function today(): CarbonImmutable
    {
        return CarbonImmutable::now(self::timezone())->startOfDay();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function saveGlobal(array $data): array
    {
        $value = self::normalize(self::merge(self::global(), $data));
        self::write(self::GLOBAL_KEY, $value, 'workload');

        return $value;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function saveForUser(int|string $userId, array $data): array
    {
        $value = self::normalize(self::merge(self::forUser($userId), $data));
        unset($value['timezone']);
        self::write(self::userKey($userId), $value, 'user_reminders');

        return self::forUser($userId);
    }

    public static function resetUser(int|string $userId): void
    {
        DB::table('settings')->where('key', self::userKey($userId))->delete();
    }

    /**
     * Aturan validasi bersama untuk endpoint pengaturan pengingat.
     *
     * @return array<string, mixed>
     */
    public static function validationRules(): array
    {
        return [
            'timezone' => ['sometimes', 'string', 'timezone:all'],
            'time' => ['sometimes', 'string', 'date_format:H:i'],
            'daily' => ['sometimes', 'array'],
            'daily.enabled' => ['sometimes', 'boolean'],
            'weekly' => ['sometimes', 'array'],
            'weekly.enabled' => ['sometimes', 'boolean'],
            'weekly.weekdays' => ['sometimes', 'array'],
            'weekly.weekdays.*' => ['integer', 'between:1,7'],
            'monthly' => ['sometimes', 'array'],
            'monthly.enabled' => ['sometimes', 'boolean'],
            'monthly.days_of_month' => ['sometimes', 'array'],
            'monthly.days_of_month.*' => ['integer', 'between:1,31'],
            'monthly.first_monday' => ['sometimes', 'boolean'],
            'annually' => ['sometimes', 'array'],
            'annually.enabled' => ['sometimes', 'boolean'],
            'annually.month' => ['sometimes', 'integer', 'between:1,12'],
            'annually.day' => ['sometimes', 'integer', 'between:1,31'],
            'project' => ['sometimes', 'array'],
            'project.enabled' => ['sometimes', 'boolean'],
            'project.days_before_due' => ['sometimes', 'array'],
            'project.days_before_due.*' => ['integer', 'between:0,365'],
            'project.on_start_date' => ['sometimes', 'boolean'],
            'incidental' => ['sometimes', 'array'],
            'incidental.enabled' => ['sometimes', 'boolean'],
            'incidental.days_before_due' => ['sometimes', 'array'],
            'incidental.days_before_due.*' => ['integer', 'between:0,365'],
            'incidental.on_start_date' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Gabungkan per bagian: nilai daftar (mis. weekdays) diganti utuh, bukan per indeks.
     *
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (! array_key_exists($key, self::DEFAULTS)) {
                continue;
            }

            if (is_array(self::DEFAULTS[$key])) {
                $current = is_array($base[$key] ?? null) ? $base[$key] : [];
                $base[$key] = is_array($value) ? array_merge($current, $value) : $current;
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private static function normalize(array $config): array
    {
        $d = self::DEFAULTS;

        $timezone = (string) ($config['timezone'] ?? $d['timezone']);
        if (! in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            $timezone = $d['timezone'];
        }

        $time = (string) ($config['time'] ?? $d['time']);
        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) !== 1) {
            $time = $d['time'];
        }

        $daily = self::section($config, 'daily', $d['daily']);
        $weekly = self::section($config, 'weekly', $d['weekly']);
        $monthly = self::section($config, 'monthly', $d['monthly']);
        $annually = self::section($config, 'annually', $d['annually']);
        $project = self::section($config, 'project', $d['project']);
        $incidental = self::section($config, 'incidental', $d['incidental']);

        return [
            'timezone' => $timezone,
            'time' => $time,
            'daily' => [
                'enabled' => (bool) $daily['enabled'],
            ],
            'weekly' => [
                'enabled' => (bool) $weekly['enabled'],
                'weekdays' => self::intList($weekly['weekdays'], 1, 7),
            ],
            'monthly' => [
                'enabled' => (bool) $monthly['enabled'],
                'days_of_month' => self::intList($monthly['days_of_month'], 1, 31),
                'first_monday' => (bool) $monthly['first_monday'],
            ],
            'annually' => [
                'enabled' => (bool) $annually['enabled'],
                'month' => max(1, min(12, (int) $annually['month'])),
                'day' => max(1, min(31, (int) $annually['day'])),
            ],
            'project' => [
                'enabled' => (bool) $project['enabled'],
                'days_before_due' => self::intList($project['days_before_due'], 0, 365),
                'on_start_date' => (bool) $project['on_start_date'],
            ],
            'incidental' => [
                'enabled' => (bool) $incidental['enabled'],
                'days_before_due' => self::intList($incidental['days_before_due'], 0, 365),
                'on_start_date' => (bool) $incidental['on_start_date'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    private static function section(array $config, string $name, array $default): array
    {
        $value = $config[$name] ?? [];

        return is_array($value) ? array_merge($default, $value) : $default;
    }

    /**
     * @return list<int>
     */
    private static function intList(mixed $value, int $min, int $max): array
    {
        if (! is_array($value)) {
            return [];
        }

        $ints = array_map('intval', array_values($value));
        $ints = array_values(array_unique(array_filter($ints, fn (int $v): bool => $v >= $min && $v <= $max)));
        sort($ints);

        return $ints;
    }

    /**
     * @return array<string, mixed>
     */
    private static function read(string $key): array
    {
        $raw = DB::table('settings')->where('key', $key)->value('value');
        if ($raw === null) {
            return [];
        }

        $decoded = is_string($raw) ? json_decode($raw, true) : $raw;

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private static function write(string $key, array $value, string $group): void
    {
        $now = now();
        $payload = ['value' => json_encode($value), 'type' => 'json', 'group' => $group, 'updated_at' => $now];

        if (DB::table('settings')->where('key', $key)->exists()) {
            DB::table('settings')->where('key', $key)->update($payload);
        } else {
            DB::table('settings')->insert(['key' => $key, 'created_at' => $now] + $payload);
        }
    }
}
