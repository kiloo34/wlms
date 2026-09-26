<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration: Workload Module - Core (Workspaces, Projects, Sprints)
return new class extends Migration
{
    public function up(): void
    {
        // ================================================================
        // TABLE: workspaces
        // Container tertinggi di modul Workload.
        // Hanya dimiliki oleh level GROUP (hierarki terendah).
        // TIDAK ada FK ke org_units karena beda bounded context (modul Identity).
        // ================================================================
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('owner_group_id');
            $table->string('name', 150);
            $table->text('description')->nullable();

            // FIX #4a: string menggantikan ENUM. Valid: 'ACTIVE' | 'ARCHIVED'
            $table->string('status', 20)->default('ACTIVE');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_group_id', 'status'], 'workspace_group_status_idx');
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('workspace_id');
            $table->uuid('workflow_id')->nullable();
            $table->string('key', 10)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();

            // FIX #4b: string menggantikan ENUM. Valid: 'ACTIVE' | 'ARCHIVED'
            $table->string('status', 20)->default('ACTIVE');

            $table->uuid('lead_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('workspace_id')
                ->references('id')->on('workspaces')
                ->onDelete('cascade');

            $table->foreign('workflow_id')
                ->references('id')->on('workflows')
                ->onDelete('set null');

            $table->index('workspace_id');
            $table->index('lead_id');
            $table->index('status');
        });

        Schema::create('sprints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->string('name', 100);
            $table->text('goal')->nullable();

            // FIX #4c: string menggantikan ENUM. Valid: 'PENDING' | 'ACTIVE' | 'CLOSED'
            $table->string('state', 20)->default('PENDING');

            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();

            // FIX #7 terkait: Simpan total story points yang committed & completed di sprint ini
            // untuk kalkulasi Velocity tanpa query berat ke tabel issues setiap saat
            $table->unsignedInteger('committed_points')->default(0);
            $table->unsignedInteger('completed_points')->default(0);

            $table->timestamps();

            $table->foreign('project_id')
                ->references('id')->on('projects')
                ->onDelete('cascade');

            $table->index(['project_id', 'state'], 'sprint_project_state_idx');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('sprints');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('workspaces');
    }
};
