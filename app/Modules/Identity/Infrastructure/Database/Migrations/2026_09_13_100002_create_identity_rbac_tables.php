<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration: Identity Module - RBAC (Roles, Permissions, Context-Aware User Roles)
return new class extends Migration
{
    public function up(): void
    {
        // ================================================================
        // TABLE: permissions
        // Atom hak akses. Menggunakan format "resource:action" seperti OAuth scope.
        // Contoh: 'issues:create', 'workspaces:delete', 'sprints:manage'
        // ================================================================
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique(); // e.g. 'issues:create'
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // ================================================================
        // TABLE: roles
        // Kumpulan permissions. Memiliki 'scope' untuk membatasi konteks penggunaan.
        // ================================================================
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50);

            // FIX #3: string menggantikan ENUM — siap jika scope baru perlu ditambah.
            // Valid values: 'GLOBAL' | 'WORKSPACE' | 'PROJECT'
            // GLOBAL: Berlaku di seluruh sistem (e.g., Super Admin)
            // WORKSPACE: Berlaku di 1 workspace tertentu
            // PROJECT: Berlaku di 1 proyek tertentu
            $table->string('scope', 30)->default('GLOBAL');
            $table->timestamps();

            $table->unique(['name', 'scope']);
            $table->index('scope');
        });

        // ================================================================
        // TABLE: role_permissions (Pivot)
        // Menghubungkan Role ke sekumpulan Permissions.
        // ================================================================
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->uuid('permission_id');

            // Composite PK = Covering Index + Integritas Data
            $table->primary(['role_id', 'permission_id']);

            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')->on('permissions')
                ->onDelete('cascade');
        });

        // ================================================================
        // TABLE: user_roles (Context-Aware RBAC Pivot)
        // Engine INTI otorisasi JIRA-like.
        // Seorang user bisa menjadi 'Viewer' di Workspace A,
        // sekaligus 'Project Manager' di Project B dalam Workspace yang sama.
        // ================================================================
        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('role_id');

            // Polymorphic Soft Reference (Tanpa FK lintas modul):
            // context_type = 'WORKSPACE' | 'PROJECT' | null (untuk GLOBAL)
            // context_id   = UUID dari workspace/project (Soft Ref ke modul Workload)
            $table->string('context_type', 50)->nullable();
            $table->uuid('context_id')->nullable();

            $table->timestamps();

            // FK dalam modul Identity (aman)
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->onDelete('cascade');

            // Index untuk lookup cepat: "Role apa yang dimiliki User X di Project Y?"
            $table->index(['user_id', 'context_type', 'context_id'], 'user_context_lookup_idx');

            // Mencegah assignment role yang sama di konteks yang sama (duplikasi)
            $table->unique(
                ['user_id', 'role_id', 'context_type', 'context_id'],
                'user_role_context_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
