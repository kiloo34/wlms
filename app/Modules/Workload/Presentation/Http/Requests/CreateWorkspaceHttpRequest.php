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
        if (! $user) {
            return false;
        }

        if (empty($user->org_unit_id)) {
            abort(422, 'User belum terdaftar pada unit organisasi. Hubungi Administrator.');
        }

        if ($user->hasPermission('workspaces:manage')) {
            return true;
        }

        // Cek apakah user memiliki role dengan permission workspaces:manage (misal Workspace Owner)
        $user->loadMissing('userRoles.role.permissions');
        foreach ($user->userRoles as $userRole) {
            if ($userRole->role) {
                if (strtolower($userRole->role->name) === 'superadmin') {
                    return true;
                }
                foreach ($userRole->role->permissions as $perm) {
                    if ($perm->name === 'workspaces:manage') {
                        return true;
                    }
                }
            }
        }

        return false;
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
