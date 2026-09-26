<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration: Patch tabel 'users' default Laravel
// Menambahkan kolom yang diperlukan sistem WLMS Enterprise:
// - Koneksi soft ke hierarki OrgUnit (Identity Module)
// - Status keaktifan user
// - UUID sebagai stable public identifier (untuk cross-module soft reference)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // UUID stabil untuk digunakan sebagai SOFT REFERENCE lintas modul Workload.
            // Berbeda dari BigInt 'id' yang hanya untuk DB internal.
            // Backend harus mengekspos 'uuid' ini ke publik, BUKAN integer 'id'.
            $table->uuid('uuid')->unique()->after('id');

            // SOFT REFERENCE ke Identity Module (org_units.id)
            // Menentukan User ini berada di level & unit apa dalam hierarki.
            // Tidak ada FK constraint karena user bisa exist sebelum di-assign ke OrgUnit.
            $table->uuid('org_unit_id')->nullable()->after('uuid');

            // Status keaktifan. User yang di-deactivate tidak bisa login
            // tapi datanya tidak dihapus (Zero Data Loss principle).
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'SUSPENDED'])->default('ACTIVE')->after('remember_token');

            // Profil tambahan yang sering dibutuhkan sistem BUMN/Korporat
            $table->string('employee_id', 50)->nullable()->unique()->after('status'); // NIP / NIK Karyawan
            $table->string('phone', 20)->nullable()->after('employee_id');
            $table->string('avatar_url')->nullable()->after('phone');

            // Index untuk lookup user berdasarkan unit organisasi (frequent query)
            $table->index('org_unit_id', 'user_org_unit_idx');
            $table->index('status', 'user_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'org_unit_id', 'status', 'employee_id', 'phone', 'avatar_url']);
        });
    }
};
