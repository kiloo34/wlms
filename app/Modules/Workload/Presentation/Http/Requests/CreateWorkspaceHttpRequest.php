<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class CreateWorkspaceHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Pastikan user sudah login dan memiliki akses membuat workspace
        return auth()->check() && auth()->user()->hasPermission('workspaces:manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Mendapatkan Idempotency Key dari header, atau generate otomatis.
     */
    public function getIdempotencyKey(): string
    {
        return $this->header('X-Idempotency-Key', Str::uuid()->toString());
    }

    /**
     * Mengambil ID actor secara aman dari session/token, BUKAN dari body request.
     */
    public function getActorId(): string
    {
        return (string) auth()->id();
    }

    /**
     * Mengambil ID group pemilik workspace.
     * Dalam kasus ini, kita asumsikan user membuat workspace untuk groupnya sendiri,
     * yang mana datanya ada di properti user, BUKAN dari payload (mencegah IDOR).
     */
    public function getOwnerGroupId(): string
    {
        // Asumsi relasi user ke org_unit ada.
        return (string) auth()->user()->org_unit_id;
    }
}
