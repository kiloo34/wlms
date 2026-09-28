<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// FIX: Kolom user_id di tabel user_roles di production masih bertipe varchar (uuid lama).
// Harus di-ALTER ke bigint agar cocok dengan users.id yang bertipe BigInt.
// Error: "operator does not exist: character varying = integer" saat query user_roles.
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        // SQLite tidak perlu ALTER TYPE — tabel sudah dibuat ulang dengan fresh migration
        if ($driver !== 'pgsql') {
            return;
        }

        // Cek apakah kolom sudah bigint — jika iya, skip (idempotent)
        $columnType = DB::selectOne("
            SELECT data_type 
            FROM information_schema.columns 
            WHERE table_name = 'user_roles' 
              AND column_name = 'user_id'
        ");

        if ($columnType && str_contains(strtolower($columnType->data_type), 'int')) {
            return;
        }

        // 1. Drop foreign key dan index yang merujuk user_id (jika ada)
        Schema::table('user_roles', function (Blueprint $table) {
            try {
                $table->dropForeign(['user_id']);
            } catch (\Throwable) {}

            try {
                $table->dropIndex(['user_id']);
            } catch (\Throwable) {}
        });

        // 2. ALTER kolom dari varchar/uuid ke bigint menggunakan raw SQL
        // USING diperlukan PostgreSQL untuk konversi tipe data eksplisit
        DB::statement("ALTER TABLE user_roles ALTER COLUMN user_id TYPE bigint USING user_id::bigint;");

        // 3. Tambahkan kembali index
        Schema::table('user_roles', function (Blueprint $table) {
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan ke varchar dengan aman karena data sudah bigint
    }
};
