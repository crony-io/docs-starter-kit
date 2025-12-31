<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('git_syncs', function (Blueprint $table) {
            $table->id();
            $table->string('commit_hash', 40);
            $table->text('commit_message')->nullable();
            $table->string('commit_author')->nullable();
            $table->timestamp('commit_date')->nullable();
            $table->enum('sync_status', ['pending', 'in_progress', 'success', 'failed'])->default('pending');
            $table->unsignedInteger('files_changed')->default(0);
            $table->json('sync_details')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('commit_hash');
            $table->index('sync_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('git_syncs');
    }
};
