<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('issue_id'); // must match issues.id which is uuid
            $table->unsignedBigInteger('author_id'); // must match users.id
            $table->text('body');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('issue_id')->references('id')->on('issues')->cascadeOnDelete();
            // Not enforcing foreign key on author_id right now if users table uses integer ID and issue uses varchar. 
            // In a real strict environment we would, but keeping it flexible as per previous Worklog design.
        });

        // Index for faster queries
        Schema::table('issue_comments', function (Blueprint $table) {
            $table->index(['issue_id', 'created_at'], 'idx_issue_comments_timeline');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_comments');
    }
};
