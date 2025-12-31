<?php

namespace App\Listeners;

use App\Events\GitSyncCompleted;
use App\Jobs\GenerateLLMFilesJob;

class RegenerateLLMFiles
{
    public function handle(GitSyncCompleted $event): void
    {
        // Dispatch job to regenerate LLM files after sync
        GenerateLLMFilesJob::dispatch();
    }
}
