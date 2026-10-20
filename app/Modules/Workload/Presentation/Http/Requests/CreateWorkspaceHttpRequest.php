<?php

declare(strict_types=1);

namespace App\Modules\Workload\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class CreateWorkspaceHttpRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = auth()->user();
        if (! $user || ! $user->hasPermission('workspaces:manage')) {
            return false;
        }

        if (empty($user->org_unit_id)) {
            abort(422, 'User belum terdaftar pada unit organisasi. Hubungi Administrator.');
        }

        return true;
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
     * Dalam kasus ini, user membuat workspace untuk groupnya sendiri,
     * yang mana datanya ada di properti user, BUKAN dari payload (mencegah IDOR).
     */
    public function getOwnerGroupId(): string
    {
        return (string) auth()->user()->org_unit_id;
    }
}
