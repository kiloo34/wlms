<?php

declare(strict_types=1);

namespace App\Modules\Workload\Application\Queries;

use App\Modules\Workload\Domain\Enums\WorkType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Pencarian teks bebas (mengandung kata kunci, tidak peka huruf besar/kecil) di:
 * nama/kode/deskripsi project, judul/kode/deskripsi issue, dan isi komentar.
 * Hasil dibatasi ke project yang boleh dilihat user.
 */
final class GlobalSearchQuery
{
    public const MIN_LENGTH = 2;

    /**
     * @return array<string, mixed>
     */
    public function execute(int|string $userId, ?string $orgUnitId, string $term, int $limit = 10): array
    {
        $term = trim(preg_replace('/\s+/u', ' ', $term) ?? '');
        $limit = max(1, min(50, $limit));

        if (mb_strlen($term) < self::MIN_LENGTH) {
            return ['query' => $term, 'projects' => [], 'issues' => [], 'comments' => [], 'total' => 0];
        }

        $pattern = '%'.addcslashes(mb_strtolower($term), '\\%_').'%';
        $accessible = AccessibleProjectsQuery::for($userId, $orgUnitId)->select('projects.id');

        $projects = DB::table('projects')
            ->leftJoin('workspaces', 'workspaces.id', '=', 'projects.workspace_id')
            ->leftJoin('users as owners', 'owners.id', '=', 'projects.lead_id')
            ->whereIn('projects.id', $accessible->toBase())
            ->whereNull('projects.deleted_at')
            ->where(function (Builder $q) use ($pattern): void {
                self::orLike($q, 'projects.name', $pattern);
                self::orLike($q, 'projects.key', $pattern);
                self::orLike($q, 'projects.description', $pattern);
            })
            ->orderBy('projects.name')
            ->limit($limit)
            ->get([
                'projects.id', 'projects.key', 'projects.name', 'projects.description', 'projects.status',
                'projects.workspace_id', 'workspaces.name as workspace_name', 'owners.name as owner_name',
            ])
            ->map(fn ($row): array => [
                'id' => (string) $row->id,
                'key' => (string) $row->key,
                'name' => (string) $row->name,
                'status' => (string) $row->status,
                'workspace_id' => (string) $row->workspace_id,
                'workspace_name' => $row->workspace_name,
                'owner_name' => $row->owner_name,
                'matched_in' => $this->matchedField($term, ['name' => $row->name, 'key' => $row->key, 'description' => $row->description]),
                'snippet' => self::snippet((string) ($row->description ?? ''), $term),
            ])
            ->values()
            ->all();

        $issueKeyExpr = "projects.key || '-' || CAST(issues.number AS TEXT)";
        $issues = DB::table('issues')
            ->join('projects', 'projects.id', '=', 'issues.project_id')
            ->leftJoin('statuses', 'statuses.id', '=', 'issues.status_id')
            ->leftJoin('users as assignees', 'assignees.id', '=', 'issues.assignee_id')
            ->whereIn('issues.project_id', $accessible->toBase())
            ->whereNull('issues.deleted_at')
            ->where(function (Builder $q) use ($pattern, $issueKeyExpr): void {
                self::orLike($q, 'issues.title', $pattern);
                self::orLike($q, 'issues.description', $pattern);
                self::orLike($q, $issueKeyExpr, $pattern);
            })
            ->orderByDesc('issues.updated_at')
            ->limit($limit)
            ->get([
                'issues.id', 'issues.number', 'issues.title', 'issues.description', 'issues.work_type',
                'issues.due_date', 'issues.project_id', 'projects.key as project_key', 'projects.name as project_name',
                'projects.workspace_id', 'statuses.name as status_name', 'statuses.category as status_category',
                'assignees.name as assignee_name',
            ])
            ->map(fn ($row): array => [
                'id' => (string) $row->id,
                'key' => $row->project_key.'-'.$row->number,
                'title' => (string) $row->title,
                'work_type' => (string) ($row->work_type ?: WorkType::DEFAULT->value),
                'work_type_label' => WorkType::labelFor($row->work_type),
                'due_date' => $row->due_date !== null ? substr((string) $row->due_date, 0, 10) : null,
                'status' => ['name' => $row->status_name, 'category' => $row->status_category],
                'assignee_name' => $row->assignee_name,
                'project' => [
                    'id' => (string) $row->project_id,
                    'key' => (string) $row->project_key,
                    'name' => (string) $row->project_name,
                    'workspace_id' => (string) $row->workspace_id,
                ],
                'matched_in' => $this->matchedField($term, [
                    'title' => $row->title,
                    'key' => $row->project_key.'-'.$row->number,
                    'description' => $row->description,
                ]),
                'snippet' => self::snippet((string) ($row->description ?? ''), $term),
            ])
            ->values()
            ->all();

        $comments = DB::table('comments')
            ->join('issues', 'issues.id', '=', 'comments.issue_id')
            ->join('projects', 'projects.id', '=', 'issues.project_id')
            ->leftJoin('users as authors', 'authors.id', '=', 'comments.author_id')
            ->whereIn('issues.project_id', $accessible->toBase())
            ->whereNull('comments.deleted_at')
            ->whereNull('issues.deleted_at')
            ->where(fn (Builder $q) => self::orLike($q, 'comments.body', $pattern))
            ->orderByDesc('comments.created_at')
            ->limit($limit)
            ->get([
                'comments.id', 'comments.body', 'comments.created_at', 'issues.id as issue_id', 'issues.number',
                'issues.title as issue_title', 'issues.project_id', 'projects.key as project_key',
                'projects.workspace_id', 'authors.name as author_name',
            ])
            ->map(fn ($row): array => [
                'id' => (string) $row->id,
                'issue_id' => (string) $row->issue_id,
                'issue_key' => $row->project_key.'-'.$row->number,
                'issue_title' => (string) $row->issue_title,
                'project_id' => (string) $row->project_id,
                'workspace_id' => (string) $row->workspace_id,
                'author_name' => $row->author_name,
                'created_at' => (string) $row->created_at,
                'snippet' => self::snippet((string) $row->body, $term),
            ])
            ->values()
            ->all();

        return [
            'query' => $term,
            'projects' => $projects,
            'issues' => $issues,
            'comments' => $comments,
            'total' => count($projects) + count($issues) + count($comments),
        ];
    }

    /**
     * Kolom selalu berasal dari literal di kelas ini (bukan input user); hanya pola yang di-bind.
     *
     * @param  literal-string  $column
     */
    private static function orLike(Builder $query, string $column, string $pattern): void
    {
        $query->orWhereRaw("LOWER({$column}) LIKE ? ESCAPE '\\'", [$pattern]);
    }

    /**
     * Potongan teks di sekitar kata kunci (tag HTML dibuang) untuk ditampilkan di hasil.
     */
    public static function snippet(string $text, string $term, int $radius = 60): ?string
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');
        if ($plain === '') {
            return null;
        }

        $pos = mb_stripos($plain, $term);
        if ($pos === false) {
            return mb_strlen($plain) > $radius * 2 ? mb_substr($plain, 0, $radius * 2).'…' : $plain;
        }

        $start = max(0, $pos - $radius);
        $length = mb_strlen($term) + $radius * 2;
        $excerpt = mb_substr($plain, $start, $length);

        return ($start > 0 ? '…' : '').$excerpt.($start + $length < mb_strlen($plain) ? '…' : '');
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function matchedField(string $term, array $fields): ?string
    {
        foreach ($fields as $name => $value) {
            if ($value !== null && mb_stripos((string) $value, $term) !== false) {
                return $name;
            }
        }

        return null;
    }
}
