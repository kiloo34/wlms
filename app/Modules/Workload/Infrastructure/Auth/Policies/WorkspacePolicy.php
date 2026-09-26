<?php

declare(strict_types=1);

namespace App\Modules\Workload\Infrastructure\Auth\Policies;

use App\Modules\Identity\Infrastructure\Persistence\Eloquent\Models\UserModel;
use App\Modules\Workload\Infrastructure\Persistence\Eloquent\Models\WorkspaceModel;
use Illuminate\Auth\Access\HandlesAuthorization;

final class WorkspacePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any workspaces.
     */
    public function viewAny(UserModel $user): bool
    {
        // Semua user yang login boleh melihat daftar Workspace
        return true;
    }

    /**
     * Determine whether the user can create workspaces.
     */
    /**
     * Determine whether the user can view the workspace.
     */
    public function view(UserModel $user, WorkspaceModel $workspace): bool
    {
        if ($this->isSuperadmin($user)) {
            return true;
        }

        if ($user->org_unit_id === $workspace->owner_group_id) {
            return true;
        }

        return $workspace->members()->where('user_id', $user->id)->exists();
    }

    public function create(UserModel $user): bool
    {
        // Pengecekan dinamis ke tabel RBAC (tanpa ENUM).
        // Misalnya: pastikan user memiliki org_unit_id atau role pembuat.
        return $user->org_unit_id !== null;
    }

    /**
     * Determine whether the user can update the workspace.
     */
    public function update(UserModel $user, WorkspaceModel $workspace): bool
    {
        // Anti-IDOR (Insecure Direct Object Reference)
        // Hanya Superadmin atau Anggota Org Unit yang sama dengan pemilik Workspace
        // yang diizinkan untuk mengupdate.
        if ($this->isSuperadmin($user)) {
            return true;
        }

        return $user->org_unit_id === $workspace->owner_group_id;
    }

    /**
     * Determine whether the user can delete the workspace.
     */
    public function delete(UserModel $user, WorkspaceModel $workspace): bool
    {
        // Penghapusan dibatasi sangat ketat.
        if ($this->isSuperadmin($user)) {
            return true;
        }

        // Delegasi hak menghapus hanya jika user satu grup dengan owner Workspace.
        return $user->org_unit_id === $workspace->owner_group_id;
    }

    /**
     * Helper sementara untuk validasi bypass RBAC.
     * Nantinya akan dihubungkan dengan tabel `roles` dan `permissions` secara utuh.
     */
    private function isSuperadmin(UserModel $user): bool
    {
        // Mencontohkan Anti-Enum, kita hanya cek email spesifik atau UUID superadmin,
        // atau mengecek relasi $user->roles()->where('name', 'Superadmin')->exists();
        return $user->email === 'superadmin@wlms.com';
    }
}

