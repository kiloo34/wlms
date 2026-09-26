<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration: Security Module - User Access & Navigation Tracking
//
// FILOSOFI DESAIN: Tabel ini adalah "CCTV" dari sistem.
// Berbeda dari audit_logs yang event-driven (dipicu aksi bisnis),
// tabel ini adalah request-driven (dipicu oleh SETIAP HTTP request
// dari authenticated user melalui Middleware).
//
// Gunakan kasus:
//   ✅ "User Budi mengakses halaman apa saja hari Selasa jam 09.00-12.00?"
//   ✅ "Apakah ada user yang mencoba mengakses URL yang tidak berhak dia akses?"
//   ✅ "Berapa lama rata-rata user berada di halaman Board?"
//   ✅ "Dari IP mana saja user ini login hari ini?"
//   ✅ "Siapa saja yang membuka halaman Export Report (meskipun tidak jadi di-klik)?"
//
// ⚠️  CATATAN VOLUME: Tabel ini berpotensi sangat besar.
//     Strategi retensi data WAJIB diterapkan (e.g., auto-purge data > 90 hari).
//     Pertimbangkan partisi tabel per bulan di skala produksi besar.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_access_logs', function (Blueprint $table) {
            // BigInt Auto-increment: Kita SENGAJA pakai ini di sini.
            // Tabel ini bukan entitas domain, tidak perlu UUID.
            // BigInt memberikan INSERT performa paling cepat untuk volume besar.
            $table->id();

            // SOFT REFERENCE ke users.uuid (public identifier, bukan BigInt id)
            // Nullable: juga bisa merekam percobaan akses dari request yang belum terautentikasi
            $table->uuid('user_id')->nullable();

            // Metode HTTP Request
            $table->string('method', 10); // GET, POST, PUT, DELETE, PATCH

            // URL lengkap yang diakses (tanpa query string yang sensitif)
            $table->string('url', 500);

            // Route name dari Laravel Router (lebih stabil daripada URL yang bisa berubah)
            // Contoh: 'workload.boards.index', 'identity.users.show'
            $table->string('route_name', 150)->nullable();

            // HTTP Status Code dari response (200, 403, 404, 500, dll.)
            // 403 berulang = indikasi percobaan akses tidak sah (Security Alert!)
            $table->unsignedSmallInteger('response_code')->nullable();

            // Durasi eksekusi request dalam MILIDETIK.
            // Berguna untuk mendeteksi halaman yang lambat (Performance Monitoring).
            $table->unsignedInteger('duration_ms')->nullable();

            // Konteks jaringan (untuk forensik keamanan)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Referrer (dari halaman mana user datang, berguna untuk UX analysis)
            $table->string('referer', 500)->nullable();

            // ID sesi untuk mengelompokkan rangkaian akses dalam 1 sesi login
            $table->string('session_id', 100)->nullable();

            // Append-only: TIDAK ada updated_at
            $table->timestamp('accessed_at')->useCurrent();

            // ================================================================
            // INDEXING STRATEGY (Disesuaikan Query Pattern Paling Sering)
            // ================================================================

            // Query 1: "Tampilkan semua halaman yang diakses User X"
            $table->index(['user_id', 'accessed_at'], 'access_user_time_idx');

            // Query 2: "Cari semua akses dengan response 403 (Unauthorized)"
            $table->index(['response_code', 'accessed_at'], 'access_response_time_idx');

            // Query 3: "Halaman mana yang paling sering diakses? (Traffic Analysis)"
            $table->index('route_name', 'access_route_idx');

            // Query 4: "Semua akses dari IP tertentu (investigasi insiden)"
            $table->index('ip_address', 'access_ip_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_access_logs');
    }
};
