<?php

declare(strict_types=1);

namespace App\Modules\DashboardCatalog\Application\UseCases;

use App\Modules\DashboardCatalog\Infrastructure\Persistence\Eloquent\Models\TableauDashboardModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Use case CRUD Katalog Dashboard Tableau.
 *
 * Mengikuti pola DocPageAdminService (KnowledgeBase): Presentation hanya
 * berbicara dengan service ini, tidak menyentuh Eloquent secara langsung.
 */
class TableauDashboardService
{
    /**
     * @var list<string>
     */
    public const PURPOSES = [
        'View Data Only',
        'View Data & Download Data',
        'Download Data Only',
        'Tidak ada kebutuhan bisnis',
    ];

    /**
     * @var list<string>
     */
    public const USER_MAPPINGS = [
        'Cabang',
        'Divisi',
        'Divisi & Cabang',
        'Admin',
    ];

    /**
     * @return Collection<int, TableauDashboardModel>
     */
    public function listAll(): Collection
    {
        return TableauDashboardModel::query()
            ->orderByRaw('CASE WHEN number IS NULL THEN 1 ELSE 0 END')
            ->orderBy('number')
            ->orderBy('name')
            ->get();
    }

    /**
     * Nilai unik untuk dropdown filter & form (project, mapping, owner, dst).
     *
     * @return array{projects: list<string>, mappings: list<string>, user_mappings: list<string>, purposes: list<string>, owners: list<string>}
     */
    public function listOptions(): array
    {
        $distinct = static function (string $column): array {
            /** @var list<string> $values */
            $values = TableauDashboardModel::query()
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->distinct()
                ->orderBy($column)
                ->pluck($column)
                ->map(fn ($value): string => (string) $value)
                ->values()
                ->all();

            return $values;
        };

        return [
            'projects' => $distinct('tableau_project'),
            'mappings' => $distinct('mapping'),
            'user_mappings' => array_values(array_unique([...self::USER_MAPPINGS, ...$distinct('user_mapping')])),
            'purposes' => array_values(array_unique([...self::PURPOSES, ...$distinct('purpose')])),
            'owners' => $distinct('owner'),
        ];
    }

    public function findOrFail(string $id): TableauDashboardModel
    {
        /** @var TableauDashboardModel */
        return TableauDashboardModel::query()->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TableauDashboardModel
    {
        $attributes = $this->normalize($data);

        if (! isset($attributes['number'])) {
            $attributes['number'] = $this->nextNumber();
        }

        return TableauDashboardModel::create([
            'id' => (string) Str::uuid(),
            ...$attributes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): TableauDashboardModel
    {
        $dashboard = $this->findOrFail($id);
        $dashboard->update($this->normalize($data));

        return $dashboard;
    }

    /**
     * Soft delete (Zero Data Loss): baris tetap ada di DB dengan deleted_at.
     */
    public function delete(string $id): void
    {
        $this->findOrFail($id)->delete();
    }

    public function nextNumber(): int
    {
        return (int) (TableauDashboardModel::query()->withTrashed()->max('number') ?? 0) + 1;
    }

    /**
     * Rapikan input: trim string, string kosong menjadi null, nomor menjadi int.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $allowed = [
            'number', 'name', 'definition', 'contents', 'purpose', 'tableau_project',
            'mapping', 'user_permission', 'user_mapping', 'owner', 'notes',
        ];

        $normalized = [];
        foreach ($allowed as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];

            if ($key === 'number') {
                $normalized[$key] = ($value === null || $value === '') ? null : (int) $value;

                continue;
            }

            if (is_string($value)) {
                $value = trim($value);
            }

            $normalized[$key] = ($value === '' || $value === null) ? null : $value;
        }

        return $normalized;
    }
}
