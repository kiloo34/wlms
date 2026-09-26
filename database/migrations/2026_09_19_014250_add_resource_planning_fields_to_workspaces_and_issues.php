<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->json('settings')->nullable()->after('description');
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->unsignedInteger('original_estimate_seconds')->nullable()->after('story_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            $table->dropColumn('original_estimate_seconds');
        });

        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }
};
