<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration: Workload Module — Project Priority (Soft Reference)
//
// Arsitektural Note (Database Agent):
// `priority_id` adalah SOFT REFERENCE ke tabel `priorities` (tanpa hard SQL FK).
// Ini mematuhi constraint Modular Monolith: konsistensi dijaga di Application Layer
// melalui validasi `exists:priorities,id` di Form Request.
//
// Indexing Strategy:
// Single-column index untuk mendukung query filter & ORDER BY priority level
// secara efisien tanpa Full Table Scan pada tabel projects.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Nullable: backward-compatible dengan project yang sudah ada
            $table->uuid('priority_id')->nullable()->after('workflow_id');
            $table->index('priority_id', 'projects_priority_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('projects_priority_id_idx');
            $table->dropColumn('priority_id');
        });
    }
};
