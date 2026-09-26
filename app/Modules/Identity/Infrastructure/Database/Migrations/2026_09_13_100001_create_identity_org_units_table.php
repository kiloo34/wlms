<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration: Identity Module - Dynamic Org Levels + Org Units (REVISED)
//
// REVISI ARSITEKTUR: Menggantikan ENUM hardcoded 'type' dengan tabel referensi
// dinamis 'org_levels'. Alasan:
//
// ❌ ENUM: ALTER TABLE saat menambah level baru = TABLE LOCK = DOWNTIME
// ❌ ENUM: Tidak bisa menyimpan metadata level (depth, aturan bisnis)
// ❌ ENUM: Tidak bisa dikonfigurasi oleh admin tanpa deploy ulang
//
// ✅ org_levels: Admin bisa tambah level baru tanpa schema change
// ✅ org_levels: Kolom 'depth' untuk ordering hierarki (1=tertinggi)
// ✅ org_levels: Kolom 'can_own_workspace' menggantikan hardcode "hanya GROUP"
// ✅ org_levels: Kolom 'is_leaf' menandai level terendah secara dinamis
return new class extends Migration
{
    public function up(): void
    {
        // ================================================================
        // TABLE: org_levels
        // Konfigurasi dinamis level hierarki organisasi.
        // Data awal di-seed via DatabaseSeeder, bukan hardcoded di schema.
        // ================================================================
        Schema::create('org_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Nama level untuk ditampilkan (e.g., 'Direksi', 'VP', 'Group')
            $table->string('name', 100);

            // Slug unik untuk referensi programatik yang aman (bukan ENUM)
            // Contoh: 'direksi', 'sevp', 'vp', 'subdiv', 'group'
            $table->string('slug', 50)->unique();

            // Kedalaman hierarki. Semakin kecil = semakin tinggi.
            // 1=Direksi, 2=SEVP, 3=VP, 4=SubDiv, 5=Group
            // Digunakan untuk: sorting, validasi parent-child (child.depth HARUS > parent.depth)
            $table->unsignedTinyInteger('depth');

            // Apakah level ini adalah yang paling rendah (leaf node)?
            // Menggantikan hardcode: WHERE type = 'GROUP'
            // Menjadi: WHERE org_level.is_leaf = true
            $table->boolean('is_leaf')->default(false);

            // Aturan bisnis kritis: Level mana yang boleh memiliki Workspace?
            // Menggantikan logika hardcode di application layer.
            // Default hanya level leaf (Group) yang boleh.
            $table->boolean('can_own_workspace')->default(false);

            // Level ini aktif atau sudah dihapus dari struktur organisasi?
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('depth');         // Untuk sorting hierarki
            $table->index('is_leaf');       // Untuk query "temukan semua Group"
            $table->index('slug');          // Untuk lookup programatik
        });

        // ================================================================
        // TABLE: org_units
        // Node individual dalam pohon hierarki organisasi.
        // REVISI: 'type' ENUM diganti 'org_level_id' FK ke org_levels.
        // ================================================================
        Schema::create('org_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();

            // DYNAMIC LEVEL: FK ke org_levels, bukan ENUM hardcoded
            // Ini memungkinkan validasi: parent.level.depth < child.level.depth
            $table->uuid('org_level_id');

            $table->string('name', 150);
            $table->string('code', 30)->nullable()->unique(); // Kode unit, e.g., 'DIV-TI-001'
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // FK ke org_levels (dalam modul Identity yang sama)
            $table->foreign('org_level_id')
                ->references('id')
                ->on('org_levels')
                ->onDelete('restrict'); // Jangan hapus level jika masih ada unit yang menggunakannya

            // FK Self-referential (Adjacency List)
            $table->foreign('parent_id')
                ->references('id')
                ->on('org_units')
                ->onDelete('restrict');

            $table->index('parent_id');
            $table->index('org_level_id');    // Untuk query: "Tampilkan semua unit level VP"
            $table->index(['is_active', 'org_level_id'], 'org_unit_active_level_idx');
        });

        // ================================================================
        // TABLE: org_unit_closures
        // Engine utama hierarchical query (Closure Table Pattern).
        // Tidak berubah dari desain sebelumnya.
        // ================================================================
        Schema::create('org_unit_closures', function (Blueprint $table) {
            $table->uuid('ancestor_id');
            $table->uuid('descendant_id');
            $table->unsignedSmallInteger('depth'); // 0 = self, 1 = parent langsung, dst.

            $table->primary(['ancestor_id', 'descendant_id']);
            $table->index(['descendant_id', 'depth']);

            $table->foreign('ancestor_id')
                ->references('id')->on('org_units')
                ->onDelete('cascade');

            $table->foreign('descendant_id')
                ->references('id')->on('org_units')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('org_unit_closures');
        Schema::dropIfExists('org_units');
        Schema::dropIfExists('org_levels');
    }
};
