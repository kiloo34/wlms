<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migration: Workload Module - Issues & Advanced JIRA Features
// (Issue Linking, Worklogs, History/Audit Trail, Custom Fields via JSON)
return new class extends Migration
{
    public function up(): void
    {
        // ================================================================
        // TABLE: issues
        // Tiket / Pekerjaan inti (Epic, Story, Task, Bug).
        // Menggunakan state machine dari tabel 'statuses' + 'workflow_transitions'.
        // Custom fields menggunakan JSON column (Anti-EAV pattern).
        // ================================================================
        Schema::create('issues', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id');
            $table->uuid('sprint_id')->nullable(); // Null = tiket masuk Backlog
            $table->uuid('status_id');             // State machine FK ke tabel statuses

            // JIRA Numbering: 'number' dikombinasikan dengan project.key di aplikasi
            // menghasilkan identifier seperti 'WLMS-1', 'WLMS-2', dst.
            $table->unsignedBigInteger('number');

            $table->string('title', 255);
            $table->longText('description')->nullable();

            // DYNAMIC FK: Menggantikan ENUM('EPIC','STORY','TASK','BUG')
            // Admin bisa tambah tipe baru via panel admin tanpa ALTER TABLE
            $table->uuid('issue_type_id');

            // DYNAMIC FK: Menggantikan ENUM('LOW','MEDIUM','HIGH','CRITICAL')
            // Sorting prioritas via kolom 'level' integer di tabel priorities
            $table->uuid('priority_id');

            // Custom Fields: JSON untuk menghindari EAV anti-pattern yang mematikan.
            // Contoh value: {"budget": 5000, "target_date": "2026-10-01", "qa_env": "staging"}
            $table->json('custom_fields')->nullable();

            // FIX #6: Kolom story_points — krusial untuk Burn-down Chart & Team Velocity
            // Fibonacci sequence lazim: 1, 2, 3, 5, 8, 13, 21. Null = belum di-estimate.
            $table->unsignedSmallInteger('story_points')->nullable();

            $table->unsignedBigInteger('reporter_id');
            $table->unsignedBigInteger('assignee_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')
                ->references('id')->on('projects')
                ->onDelete('cascade');

            $table->foreign('sprint_id')
                ->references('id')->on('sprints')
                ->onDelete('set null');

            $table->foreign('status_id')
                ->references('id')->on('statuses')
                ->onDelete('restrict');

            $table->foreign('issue_type_id')
                ->references('id')->on('issue_types')
                ->onDelete('restrict');

            $table->foreign('priority_id')
                ->references('id')->on('priorities')
                ->onDelete('restrict');

            $table->unique(['project_id', 'number'], 'issue_project_number_unique');

            // Board view: filter by project + sprint + status
            $table->index(['project_id', 'sprint_id', 'status_id'], 'issue_board_view_idx');

            // FIX #5: Index yang hilang pada FK lookup tables dinamis
            $table->index('issue_type_id', 'issue_type_lookup_idx');
            $table->index('priority_id', 'issue_priority_lookup_idx');

            $table->index('assignee_id');
            $table->index('reporter_id');
        });

        // ================================================================
        // TABLE: issue_links
        // Dependensi antar tiket ala JIRA (Blocks, Duplicates, Relates To).
        // Self-referential many-to-many pada tabel issues.
        // ================================================================
        Schema::create('issue_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('source_issue_id'); // Tiket yang "melakukan" aksi (e.g., yang memblokir)
            $table->uuid('target_issue_id'); // Tiket yang "dikenai" aksi

            // Tipe relasi logis antar tiket
            $table->enum('link_type', [
                'BLOCKS',        // Source MEMBLOKIR target
                'IS_BLOCKED_BY', // Source DIBLOKIR OLEH target (kebalikan BLOCKS)
                'RELATES_TO',    // Sekedar terkait
                'DUPLICATES',    // Source adalah duplikat dari target
                'CLONES',        // Source adalah clone dari target
            ]);

            $table->unsignedBigInteger('created_by'); // SOFT REF (Who created this link)
            $table->timestamps();

            $table->foreign('source_issue_id')
                ->references('id')->on('issues')
                ->onDelete('cascade');

            $table->foreign('target_issue_id')
                ->references('id')->on('issues')
                ->onDelete('cascade');

            // Mencegah relasi ganda yang persis sama
            $table->unique(
                ['source_issue_id', 'target_issue_id', 'link_type'],
                'issue_link_unique'
            );

            $table->index('source_issue_id');
            $table->index('target_issue_id');
        });

        // ================================================================
        // TABLE: worklogs
        // Time Tracking: Mencatat waktu yang dihabiskan setiap user pada setiap tiket.
        // STANDAR: Selalu simpan durasi dalam DETIK (seconds). Konversi di Frontend.
        // ================================================================
        Schema::create('worklogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('issue_id');

            // SOFT REFERENCE ke Identity Module (users.id)
            $table->unsignedBigInteger('author_id'); // Siapa yang mencatat waktu kerja ini

            // Durasi dalam satuan DETIK. 3600 = 1 jam. Wajib Integer untuk SUM() cepat.
            $table->unsignedInteger('time_spent_seconds');

            $table->text('description')->nullable(); // Catatan apa yang dikerjakan
            $table->dateTime('started_at');          // Kapan pekerjaan ini dimulai

            $table->timestamps();

            $table->foreign('issue_id')
                ->references('id')->on('issues')
                ->onDelete('cascade');

            // Index untuk Burn-down chart: SUM waktu per issue dan per user
            $table->index(['issue_id', 'author_id'], 'worklog_issue_author_idx');
        });

        // ================================================================
        // TABLE: issue_histories
        // Audit Trail immutable. Mencatat SETIAP perubahan field pada tiket.
        // Engine untuk Activity Log, Burn-down Chart, dan Compliance Audit BUMN.
        // ================================================================
        Schema::create('issue_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('issue_id');

            // SOFT REFERENCE ke Identity Module (users.id)
            $table->unsignedBigInteger('actor_id'); // Siapa yang melakukan perubahan

            $table->string('field_changed', 50); // Nama field yang berubah, e.g., 'status_id'
            $table->text('old_value')->nullable(); // Nilai sebelumnya
            $table->text('new_value')->nullable(); // Nilai setelahnya

            // Append-only log: hanya created_at, tidak ada updated_at
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('issue_id')
                ->references('id')->on('issues')
                ->onDelete('cascade');

            // Index untuk menampilkan timeline perubahan per tiket
            $table->index(['issue_id', 'created_at'], 'issue_history_timeline_idx');

            // Index untuk filter history berdasarkan field tertentu (e.g., history perubahan status saja)
            $table->index(['issue_id', 'field_changed'], 'issue_history_field_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_histories');
        Schema::dropIfExists('worklogs');
        Schema::dropIfExists('issue_links');
        Schema::dropIfExists('issues');
    }
};
