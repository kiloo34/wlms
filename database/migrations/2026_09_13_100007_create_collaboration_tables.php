<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration: Collaboration Module - Global Audit Trail (audit_logs)
// Ini BERBEDA dengan 'issue_histories' (yang spesifik untuk perubahan field di tiket).
// Tabel ini adalah "Black Box Recorder" untuk SELURUH sistem:
// - Siapa? (actor_id)
// - Melakukan apa? (action: 'created', 'updated', 'deleted', 'login', 'failed_login')
// - Ke entitas mana? (auditable_type, auditable_id)
// - Kapan? (created_at)
// - Dari IP mana? (ip_address)
// - Payload apa yang berubah? (old_values, new_values)
//
// Dibutuhkan untuk: Compliance Audit BUMN, Security Forensics, Kepatuhan IT Governance.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // SOFT REFERENCE ke Identity Module (users.uuid)
            // Nullable: aksi sistem/otomatis tidak punya actor (e.g., scheduled jobs)
            $table->unsignedBigInteger('actor_id')->nullable();

            // Nama class entitas yang dikenai aksi (Polymorphic).
            // Contoh: 'App\Modules\Workload\Domain\Entities\Workspace'
            // Atau versi pendek: 'workspace', 'project', 'user', 'role'
            $table->string('auditable_type', 100);

            // ID entitas yang dikenai aksi (Soft Reference, bisa dari modul mana saja)
            $table->uuid('auditable_id')->nullable(); // Nullable untuk aksi login/logout

            // Aksi yang dilakukan. Gunakan kata kerja pasif (verb):
            // 'created', 'updated', 'deleted', 'restored', 'login_success',
            // 'login_failed', 'permission_denied', 'exported', 'role_assigned'
            $table->string('event', 50);

            // Snapshot nilai SEBELUM perubahan (JSON). Null untuk CREATE event.
            $table->json('old_values')->nullable();

            // Snapshot nilai SESUDAH perubahan (JSON). Null untuk DELETE event.
            $table->json('new_values')->nullable();

            // Konteks request (untuk forensik keamanan)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // URL yang diakses saat event terjadi
            $table->string('url', 500)->nullable();

            // Append-only: hanya ada created_at, TIDAK ada updated_at.
            // Audit log tidak boleh dimodifikasi setelah ditulis.
            $table->timestamp('created_at')->useCurrent();

            // *** INDEXING STRATEGY untuk Audit Log ***
            // Query paling umum untuk compliance officer:
            // 1. "Tampilkan semua aksi oleh User X" -> idx: actor_id
            // 2. "Tampilkan semua history dari Workspace Y" -> idx: auditable_type + auditable_id
            // 3. "Tampilkan semua login_failed hari ini" -> idx: event + created_at
            $table->index('actor_id', 'audit_actor_idx');
            $table->index(['auditable_type', 'auditable_id'], 'audit_morphs_idx');
            $table->index(['event', 'created_at'], 'audit_event_time_idx');
        });

        // ================================================================
        // TABLE: comments
        // Komentar pengguna pada sebuah Issue (dari Modul Collaboration).
        // Mendukung nested comment (thread/reply) via parent_id.
        // ================================================================
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('issue_id');
            $table->uuid('parent_id')->nullable(); // Untuk threaded/reply comments

            // SOFT REFERENCE ke Identity Module (users.uuid)
            $table->unsignedBigInteger('author_id');

            $table->longText('body'); // Mendukung Markdown & @mentions
            $table->boolean('is_edited')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('issue_id')
                ->references('id')->on('issues')
                ->onDelete('cascade');

            $table->index(['issue_id', 'parent_id'], 'comment_issue_thread_idx');
            $table->index('author_id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')->on('comments')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('audit_logs');
    }
};
