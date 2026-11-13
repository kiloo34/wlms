<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration: DashboardCatalog Module - Katalog Dashboard Tableau
// Pencatatan seluruh dashboard Tableau (nama, definisi, project, mapping,
// user permission, user mapping, owner) agar dashboard baru selalu
// terdaftar dan terkelola di satu tempat sebelum dipublikasikan.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tableau_dashboards', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Nomor urut pada daftar (kolom "Num" di sheet Daily Check Tableau)
            $table->unsignedSmallInteger('number')->nullable();

            $table->string('name');

            // Dashboard Definition dipecah menjadi 3 bagian: Definisi, Isi, Tujuan
            $table->text('definition')->nullable();
            $table->text('contents')->nullable();
            $table->string('purpose', 100)->nullable();

            // Project Tableau Existing (mis. "Dashboard DPK", "# DEV", "Report")
            $table->string('tableau_project', 150)->nullable();

            // Mapping bisnis (mis. "Business Funding", "Risk", "Report")
            $table->string('mapping', 100)->nullable();

            // Daftar divisi/cabang yang diberi akses (teks bebas, dipisah koma)
            $table->text('user_permission')->nullable();

            // User Mapping: Cabang, Divisi, Divisi & Cabang, Admin
            $table->string('user_mapping', 100)->nullable();

            $table->string('owner')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            // Zero Data Loss: hapus dashboard hanya soft delete
            $table->softDeletes();

            $table->index('number');
            $table->index('tableau_project');
            $table->index('mapping');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tableau_dashboards');
    }
};
