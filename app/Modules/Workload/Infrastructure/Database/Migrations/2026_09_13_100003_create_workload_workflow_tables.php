<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration: Workload Module - State Machine (Statuses & Workflows)
// HARUS dijalankan sebelum tabel 'issues' karena issues memiliki FK ke statuses.
return new class extends Migration
{
    public function up(): void
    {
        // ================================================================
        // TABLE: statuses
        // Unit status atomik yang digunakan oleh State Machine Workflow.
        // Menggantikan kolom 'varchar status' yang hardcoded.
        // ================================================================
        Schema::create('statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50);
            $table->string('slug', 50)->unique();

            // FIX #2: string menggantikan ENUM — DB-agnostic & zero ALTER TABLE saat extend.
            // Valid values: 'TODO' | 'IN_PROGRESS' | 'DONE'
            // Enforced di Application Layer (tidak di DB agar portabel ke semua RDBMS)
            $table->string('category', 20)->default('TODO');

            $table->string('color', 20)->nullable();
            $table->timestamps();

            $table->index('category');
        });

        // ================================================================
        // TABLE: workflows
        // Sebuah koleksi aturan transisi status yang dapat digunakan oleh Project.
        // ================================================================
        Schema::create('workflows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // ================================================================
        // TABLE: workflow_transitions
        // Mendefinisikan ARAH PERGERAKAN status yang diizinkan.
        // Contoh: 'In Progress' -> 'In QA' diizinkan. 'Done' -> 'In Progress' TIDAK.
        // from_status_id = NULL berarti status awal (saat Issue pertama dibuat).
        // ================================================================
        Schema::create('workflow_transitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->uuid('from_status_id')->nullable(); // Null = initial state
            $table->uuid('to_status_id');
            $table->string('name', 50); // Nama aksi, e.g., 'Start Progress', 'Send to QA'

            $table->timestamps();

            $table->foreign('workflow_id')
                ->references('id')->on('workflows')
                ->onDelete('cascade');

            $table->foreign('from_status_id')
                ->references('id')->on('statuses')
                ->onDelete('cascade');

            $table->foreign('to_status_id')
                ->references('id')->on('statuses')
                ->onDelete('cascade');

            // Mencegah transisi duplikat dalam 1 workflow
            $table->unique(
                ['workflow_id', 'from_status_id', 'to_status_id'],
                'workflow_transition_unique'
            );

            $table->index('workflow_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_transitions');
        Schema::dropIfExists('workflows');
        Schema::dropIfExists('statuses');
    }
};
