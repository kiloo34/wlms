<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration: Identity Module - Dynamic Menu & Navigation System
// Mesin navigasi dinamis berbasis Role. Setiap Role di-mapping ke sekumpulan Menu.
// Dengan ini, tampilan sidebar/navigasi otomatis berbeda untuk setiap user
// berdasarkan role & scope-nya tanpa hardcode di Frontend.
return new class extends Migration
{
    public function up(): void
    {
        // ================================================================
        // TABLE: menus
        // Mendefinisikan struktur navigasi aplikasi secara hierarkis.
        // Mendukung nested menu (parent-child).
        // Contoh: 'Workload' (parent) -> 'My Issues', 'Board', 'Backlog' (children)
        // ================================================================
        Schema::create('menus', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Self-referential untuk nested menu (max 2 level di UI, tapi DB fleksibel)
            $table->uuid('parent_id')->nullable();

            // Nama yang ditampilkan di UI
            $table->string('label', 100);

            // Unique key untuk referensi programatik (bukan untuk URL)
            // Contoh: 'workload.board', 'identity.org-chart', 'report.burndown'
            $table->string('key', 100)->unique();

            // Route atau path UI yang dituju (e.g., '/workspaces/{id}/board')
            $table->string('route', 200)->nullable();

            // Icon identifier (misal Lucide/Heroicon name: 'layout-dashboard', 'users')
            $table->string('icon', 50)->nullable();

            // Urutan tampilan di navigasi
            $table->unsignedSmallInteger('sort_order')->default(0);

            // Menus bisa dinonaktifkan tanpa dihapus (Zero Data Loss)
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('parent_id');
            $table->index(['is_active', 'sort_order'], 'menu_active_order_idx');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('menus')
                ->onDelete('restrict');
        });

        // ================================================================
        // TABLE: role_menus
        // Pivot: Mapping Role ke sekumpulan Menu yang boleh diakses.
        // Menentukan TAMPILAN navigasi. Berbeda dengan 'permissions' yang
        // menentukan AKSI (create, update, delete).
        //
        // Contoh Business Logic:
        //   Role 'Developer'     -> Bisa lihat menu: Board, Backlog, My Issues
        //   Role 'Project Mgr'   -> Bisa lihat semua menu di atas + Sprint Management
        //   Role 'Direksi'       -> Bisa lihat menu Executive Dashboard, Report
        // ================================================================
        Schema::create('role_menus', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->uuid('menu_id');

            // Composite PK = Covering Index sekaligus mencegah duplikasi
            $table->primary(['role_id', 'menu_id']);

            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->onDelete('cascade');

            $table->foreign('menu_id')
                ->references('id')->on('menus')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_menus');
        Schema::dropIfExists('menus');
    }
};
