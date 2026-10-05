<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// FIX #1: Migrasi baru untuk menyelesaikan 2 isu sekaligus:
//   Isu #1 — Tabel 'issue_link_types' menggantikan ENUM di issue_links.link_type
//   Isu #7 — Tabel 'sprint_metrics' untuk SprintMetrics entity di domain blueprint
return new class extends Migration
{
    public function up(): void
    {
        // ================================================================
        // FIX ISU #1: TABLE: issue_link_types
        // Menggantikan ENUM('BLOCKS','IS_BLOCKED_BY','RELATES_TO','DUPLICATES','CLONES')
        // pada tabel issue_links.
        //
        // Keuntungan: Admin bisa tambah tipe relasi baru (e.g., 'IMPLEMENTS', 'TESTS')
        // tanpa ALTER TABLE dan tanpa deploy ulang kode.
        // ================================================================
        Schema::create('issue_link_types', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Nama yang ditampilkan di UI (e.g., 'Blocks', 'Relates To')
            $table->string('name', 50);

            // Stable key untuk referensi programatik (bukan ENUM)
            $table->string('slug', 50)->unique(); // e.g., 'blocks', 'relates-to'

            // Nama inbound (kebalikan dari outbound).
            // Contoh: outbound = 'Blocks' → inbound = 'Is Blocked By'
            // Ini penting untuk menampilkan relasi dari perspektif tiket target.
            $table->string('inbound_name', 50)->nullable();
            $table->string('inbound_slug', 50)->nullable()->unique();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('slug');
        });

        // ================================================================
        // FIX ISU #7: TABLE: sprint_metrics
        // Snapshot performa sprint. Append-only record setelah sprint CLOSED.
        // Engine untuk CalculateTeamVelocityUseCase & GenerateBurndownChartUseCase.
        //
        // Mengapa tabel terpisah (bukan query langsung ke issues)?
        // Sprint yang sudah CLOSED tidak boleh dihitung ulang tiap kali laporan dibuka.
        // Snapshot ini memastikan data historis konsisten meskipun issues diedit setelahnya.
        // ================================================================
        Schema::create('sprint_metrics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sprint_id')->unique(); // 1 sprint = 1 snapshot metrik

            // Story Points yang direncanakan di awal sprint (Sprint Planning)
            $table->unsignedInteger('planned_points')->default(0);

            // Story Points yang berhasil diselesaikan (status = DONE) saat sprint close
            $table->unsignedInteger('completed_points')->default(0);

            // Total issues yang ada di sprint saat dimulai
            $table->unsignedSmallInteger('total_issues')->default(0);

            // Total issues yang selesai saat sprint close
            $table->unsignedSmallInteger('completed_issues')->default(0);

            // Berapa persen issues selesai (pre-calculated untuk performa)
            // Disimpan sebagai DECIMAL untuk presisi tanpa floating point error
            $table->decimal('completion_rate', 5, 2)->default(0.00); // e.g., 87.50%

            // Tanggal snapshot diambil (biasanya = sprint.end_date)
            $table->timestamp('snapshot_at')->useCurrent();

            $table->foreign('sprint_id')
                ->references('id')->on('sprints')
                ->onDelete('cascade');
        });

        // ================================================================
        // Juga perbaiki tabel issue_links: drop kolom ENUM, tambah FK ke issue_link_types
        // Catatan: Migrasi ini dijalankan SETELAH issue_link_types dibuat,
        // sehingga ALTER TABLE sudah aman dilakukan.
        //
        // Jika migrasi 100005 sudah pernah dijalankan, gunakan alter.
        // Jika belum pernah dijalankan, edit langsung file 100005.
        //
        // Karena kita masih dalam fase development, kita ALTER di sini
        // untuk tidak mengubah file migrasi sebelumnya yang sudah terdokumentasi.
        // ================================================================
        Schema::table('issue_links', function (Blueprint $table) {
            // Hapus unique index lama yang bergantung pada link_type
            $table->dropUnique('issue_link_unique');

            // Hapus kolom ENUM lama
            $table->dropColumn('link_type');

            // Tambah kolom FK baru ke tabel lookup dinamis
            $table->uuid('link_type_id')->after('target_issue_id');

            $table->foreign('link_type_id')
                ->references('id')->on('issue_link_types')
                ->onDelete('restrict');

            $table->index('link_type_id', 'issue_link_type_idx');

            // Re-create unique index dengan kolom baru
            $table->unique(['source_issue_id', 'target_issue_id', 'link_type_id'], 'issue_link_unique_new');
        });
    }

    public function down(): void
    {
        Schema::table('issue_links', function (Blueprint $table) {
            $table->dropForeign(['link_type_id']);
            $table->dropColumn('link_type_id');
            $table->string('link_type', 20)->default('RELATES_TO');
        });

        Schema::dropIfExists('sprint_metrics');
        Schema::dropIfExists('issue_link_types');
    }
};
