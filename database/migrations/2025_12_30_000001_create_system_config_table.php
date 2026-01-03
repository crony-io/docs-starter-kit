<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_config', function (Blueprint $table) {
            $table->id();
            $table->enum('content_mode', ['git', 'cms'])->default('cms');
            $table->string('git_repository_url')->nullable();
            $table->string('git_branch')->default('main')->nullable();
            $table->text('git_access_token')->nullable();
            $table->string('git_webhook_secret')->nullable();
            $table->unsignedInteger('git_sync_frequency')->default(15);
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('setup_completed')->default(false);

            // Web-Cron columns (global scheduler)
            $table->boolean('web_cron_enabled')->default(false);
            $table->timestamp('last_web_cron_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_config');
    }
};
