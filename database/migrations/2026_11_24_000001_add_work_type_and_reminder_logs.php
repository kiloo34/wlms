<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tipe pekerjaan (jobdesc) per issue/task dan log pengiriman pengingat.
 *
 * - issues.work_type: daily | weekly | monthly | annually | incidental | project
 * - work_reminder_logs: mencegah pengingat yang sama terkirim dua kali
 *   (dipakai oleh scheduler maupun evaluasi lazy saat user membuka notifikasi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            if (! Schema::hasColumn('issues', 'work_type')) {
                $table->string('work_type', 20)->default('project');
                $table->index('work_type', 'issues_work_type_index');
            }
        });

        Schema::create('work_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('reminder_key', 191);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'reminder_key'], 'work_reminder_logs_user_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_reminder_logs');

        Schema::table('issues', function (Blueprint $table) {
            if (Schema::hasColumn('issues', 'work_type')) {
                $table->dropIndex('issues_work_type_index');
                $table->dropColumn('work_type');
            }
        });
    }
};
