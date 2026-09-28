<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration: Workload Module - Dynamic Lookup Tables
// Menggantikan seluruh ENUM hardcoded pada tabel 'issues'.
//
// Setiap tabel ini adalah "konfigurasi bisnis" yang bisa diubah
// oleh admin melalui panel admin — TANPA perlu deploy atau ALTER TABLE.
return new class extends Migration
{
    public function up(): void
    {
        // ================================================================
        // TABLE: issue_types
        // Menggantikan: ENUM('EPIC','STORY','TASK','BUG')
        //
        // Admin bisa menambah tipe baru (e.g., 'Spike', 'Technical Debt',
        // 'Test Case') tanpa menyentuh kode PHP atau schema database.
        // ================================================================
        Schema::create('issue_types', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Nama yang ditampilkan di UI (e.g., 'Bug', 'User Story')
            $table->string('name', 50);

            // Stable programmatic key. Backend menggunakan ini untuk logika,
            // bukan hardcode string 'BUG' atau 'TASK'.
            $table->string('slug', 50)->unique(); // e.g., 'bug', 'task', 'story'

            // Icon identifier untuk UI (e.g., Lucide icon: 'bug', 'bookmark')
            $table->string('icon', 50)->nullable();

            // Warna label di board (e.g., '#FF0000')
            $table->string('color', 20)->nullable();

            // Urutan tampilan di dropdown
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('slug');
            $table->index(['is_active', 'sort_order'], 'issue_type_active_order_idx');
        });

        // ================================================================
        // TABLE: priorities
        // Menggantikan: ENUM('LOW','MEDIUM','HIGH','CRITICAL')
        //
        // Kolom 'level' (integer) adalah kunci arsitektural:
        // memungkinkan sorting dan perbandingan prioritas secara matematis
        // tanpa switch-case di kode PHP.
        // ================================================================
        Schema::create('priorities', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name', 50);         // e.g., 'Critical', 'High'
            $table->string('slug', 50)->unique(); // e.g., 'critical', 'high'

            // Level numerik untuk sorting & perbandingan (semakin tinggi = semakin urgent)
            // 1=Low, 2=Medium, 3=High, 4=Critical, 5=Blocker
            $table->unsignedTinyInteger('level');

            $table->string('icon', 50)->nullable();   // e.g., 'arrow-up', 'zap'
            $table->string('color', 20)->nullable();  // e.g., '#FF0000' untuk Critical

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('level');  // Tidak boleh ada 2 priority dengan level sama
            $table->index('slug');
        });

        // ================================================================
        // TABLE: notification_channels
        // Menggantikan: ENUM('EMAIL','INAPP','PUSH')
        //
        // Kolom 'driver' menentukan adapter kelas PHP yang digunakan.
        // Menambah kanal Slack/Teams cukup dengan INSERT baris baru
        // dan mendaftarkan adapter kelasnya — nol perubahan schema.
        // ================================================================
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name', 50);           // e.g., 'Email', 'In-App', 'Slack'
            $table->string('slug', 50)->unique(); // e.g., 'email', 'in-app', 'slack'

            // Nama FQCN (Fully Qualified Class Name) adapter pengiriman.
            // Contoh: 'App\Modules\Notification\Infrastructure\Adapters\MailAdapter'
            // Strategy Pattern: runtime memuat adapter ini secara dinamis.
            $table->string('driver_class', 255);

            $table->string('icon', 50)->nullable(); // e.g., 'mail', 'bell', 'slack'

            // Apakah kanal ini aktif di sistem?
            $table->boolean('is_active')->default(true);

            // Apakah user bisa mematikan kanal ini via preferensi?
            // e.g., In-App mungkin selalu aktif (false), Email bisa dimatikan (true)
            $table->boolean('is_user_configurable')->default(true);

            $table->timestamps();

            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
        Schema::dropIfExists('priorities');
        Schema::dropIfExists('issue_types');
    }
};
