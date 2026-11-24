<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Queries;

use App\Modules\Workload\Application\Services\WorkSchedule;
use App\Modules\Workload\Infrastructure\Config\ReminderSettings;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Kamus rujukan untuk "@" di deskripsi, catatan, komentar, dan pencarian:
 * - project WLMS yang boleh dilihat user;
 * - dashboard di Katalog Dashboard beserta kamusnya (definisi, isi data, tujuan,
 *   project Tableau, mapping bisnis, user mapping, owner).
 *
 * Rujukan disimpan di teks sebagai token `@[Nama](tipe:uuid)`.
 */
final class ReferenceDirectoryQuery
{
    public const TYPES = ['project', 'dashboard'];

    public const TOKEN_PATTERN = '/@\[([^\]\r\n]{1,200})\]\((project|dashboard):([0-9a-fA-F-]{36})\)/u';

    /**
     * Ubah token rujukan menjadi teks biasa "@Nama" (untuk snippet, ekspor, dsb).
     */
    public static function toPlainText(string $text): string
    {
        return preg_replace(self::TOKEN_PATTERN, '@$1', $text) ?? $text;
    }

    /**
     * @param  list<string>  $types
     * @return array{projects: array<int, array<string, mixed>>, dashboards: array<int, array<string, mixed>>}
     */
    public function search(int|string $userId, ?string $orgUnitId, string $term, int $limit = 8, array $types = self::TYPES): array
    {
        $term = trim($term);
        $limit = max(1, min(30, $limit));
        $pattern = '%'.addcslashes(mb_strtolower($term), '\\%_').'%';

        $projects = [];
        if (in_array('project', $types, true)) {
            $query = DB::table('projects')
                ->leftJoin('workspaces', 'workspaces.id', '=', 'projects.workspace_id')
                ->leftJoin('users as owners', 'owners.id', '=', 'projects.lead_id')
                ->whereIn('projects.id', AccessibleProjectsQuery::for($userId, $orgUnitId)->select('projects.id')->toBase())
                ->whereNull('projects.deleted_at')
                ->where('projects.status', '!=', 'ARCHIVED')
                ->orderBy('projects.name')
                ->limit($limit);

            if ($term !== '') {
                $query->where(function (Builder $q) use ($pattern): void {
                    self::orLike($q, 'projects.name', $pattern);
                    self::orLike($q, 'projects.key', $pattern);
                    self::orLike($q, 'projects.description', $pattern);
                });
            }

            $projects = $query->get([
                'projects.id', 'projects.key', 'projects.name', 'projects.description',
                'workspaces.name as workspace_name', 'owners.name as owner_name',
            ])->map(fn ($row): array => [
                'type' => 'project',
                'id' => (string) $row->id,
                'label' => (string) $row->name,
                'subtitle' => trim($row->key.' · '.($row->workspace_name ?? ''), ' ·'),
                'description' => self::excerpt($row->description),
                'owner' => $row->owner_name,
            ])->values()->all();
        }

        $dashboards = [];
        if (in_array('dashboard', $types, true)) {
            $query = DB::table('tableau_dashboards')
                ->whereNull('deleted_at')
                ->orderByRaw('CASE WHEN number IS NULL THEN 1 ELSE 0 END, number ASC')
                ->orderBy('name')
                ->limit($limit);

            if ($term !== '') {
                // Cocokkan nama dan seluruh isi kamus dashboard.
                $query->where(function (Builder $q) use ($pattern): void {
                    self::orLike($q, 'name', $pattern);
                    self::orLike($q, 'tableau_project', $pattern);
                    self::orLike($q, 'mapping', $pattern);
                    self::orLike($q, 'definition', $pattern);
                    self::orLike($q, 'contents', $pattern);
                    self::orLike($q, 'purpose', $pattern);
                    self::orLike($q, 'owner', $pattern);
                });
            }

            $dashboards = $query->get([
                'id', 'number', 'name', 'definition', 'contents', 'purpose', 'tableau_project', 'mapping', 'owner',
            ])->map(fn ($row): array => [
                'type' => 'dashboard',
                'id' => (string) $row->id,
                'label' => (string) $row->name,
                'subtitle' => implode(' · ', array_filter([$row->tableau_project, $row->mapping])),
                'description' => self::excerpt($row->contents ?? $row->definition),
                'owner' => $row->owner,
            ])->values()->all();
        }

        return ['projects' => $projects, 'dashboards' => $dashboards];
    }

    /**
     * Detail kamus satu rujukan. null bila tidak ada atau user tidak berhak melihatnya.
     *
     * @return array<string, mixed>|null
     */
    public function find(int|string $userId, ?string $orgUnitId, string $type, string $id): ?array
    {
        if ($type === 'dashboard') {
            $row = DB::table('tableau_dashboards')->whereNull('deleted_at')->where('id', $id)->first();
            if ($row === null) {
                return null;
            }

            return [
                'type' => 'dashboard',
                'id' => (string) $row->id,
                'label' => (string) $row->name,
                'url' => '/dashboards?dashboard='.$row->id,
                'dictionary' => [
                    ['key' => 'number', 'label' => 'No.', 'value' => $row->number],
                    ['key' => 'tableau_project', 'label' => 'Tableau Project', 'value' => $row->tableau_project],
                    ['key' => 'mapping', 'label' => 'Business Mapping', 'value' => $row->mapping],
                    ['key' => 'definition', 'label' => 'Definition', 'value' => $row->definition],
                    ['key' => 'contents', 'label' => 'Contents / Data', 'value' => $row->contents],
                    ['key' => 'purpose', 'label' => 'Purpose', 'value' => $row->purpose],
                    ['key' => 'user_mapping', 'label' => 'User Mapping', 'value' => $row->user_mapping],
                    ['key' => 'user_permission', 'label' => 'User Permission', 'value' => $row->user_permission],
                    ['key' => 'owner', 'label' => 'Owner', 'value' => $row->owner],
                    ['key' => 'notes', 'label' => 'Notes', 'value' => $row->notes],
                ],
            ];
        }

        if ($type === 'project') {
            $row = DB::table('projects')
                ->leftJoin('workspaces', 'workspaces.id', '=', 'projects.workspace_id')
                ->leftJoin('users as owners', 'owners.id', '=', 'projects.lead_id')
                ->whereIn('projects.id', AccessibleProjectsQuery::for($userId, $orgUnitId)->select('projects.id')->toBase())
                ->whereNull('projects.deleted_at')
                ->where('projects.id', $id)
                ->first([
                    'projects.id', 'projects.key', 'projects.name', 'projects.description', 'projects.status',
                    'projects.start_date', 'projects.end_date', 'workspaces.name as workspace_name', 'owners.name as owner_name',
                ]);
            if ($row === null) {
                return null;
            }

            $start = WorkSchedule::toDateString($row->start_date !== null ? (string) $row->start_date : null);
            $end = WorkSchedule::toDateString($row->end_date !== null ? (string) $row->end_date : null);
            $duration = WorkSchedule::durationDays($start, $end);

            return [
                'type' => 'project',
                'id' => (string) $row->id,
                'label' => (string) $row->name,
                'url' => '/projects/'.$row->id,
                'remaining_days' => WorkSchedule::remainingDays($end, ReminderSettings::today()),
                'dictionary' => [
                    ['key' => 'key', 'label' => 'Key', 'value' => $row->key],
                    ['key' => 'workspace', 'label' => 'Workspace', 'value' => $row->workspace_name],
                    ['key' => 'owner', 'label' => 'Project Owner', 'value' => $row->owner_name],
                    ['key' => 'schedule', 'label' => 'Schedule', 'value' => $start !== null || $end !== null ? ($start ?? '...').' - '.($end ?? '...') : null],
                    ['key' => 'duration', 'label' => 'Duration (days)', 'value' => $duration],
                    ['key' => 'status', 'label' => 'Status', 'value' => $row->status],
                    ['key' => 'description', 'label' => 'Description', 'value' => $row->description !== null ? self::toPlainText((string) $row->description) : null],
                ],
            ];
        }

        return null;
    }

    /**
     * Kolom selalu literal dari kelas ini (bukan input user); hanya pola yang di-bind.
     *
     * @param  literal-string  $column
     */
    private static function orLike(Builder $query, string $column, string $pattern): void
    {
        $query->orWhereRaw("LOWER({$column}) LIKE ? ESCAPE '\\'", [$pattern]);
    }

    private static function excerpt(mixed $text, int $length = 120): ?string
    {
        if ($text === null || $text === '') {
            return null;
        }

        $plain = trim(preg_replace('/\s+/u', ' ', self::toPlainText(strip_tags((string) $text))) ?? '');

        return mb_strlen($plain) > $length ? mb_substr($plain, 0, $length).'…' : $plain;
    }
}
